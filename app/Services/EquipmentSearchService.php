<?php

namespace App\Services;

use App\Models\EquipmentDisposal;
use App\Models\EquipmentReceivingRegister;
use App\Models\EquipmentRedistribution;
use App\Models\WorkshopEquipmentRegister;
use Illuminate\Support\Collection;

class EquipmentSearchService
{
    /**
     * Search equipment by serial number, asset tag, item name, brand, recipient, etc.
     * Returns a ranked, deduplicated list of {identifier_type, identifier_value, label, meta}.
     */
    public function suggest(string $query, int $limit = 8): array
    {
        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $hits = collect()
            ->merge($this->searchReceiving($query))
            ->merge($this->searchRedistributions($query))
            ->merge($this->searchWorkshop($query))
            ->merge($this->searchDisposals($query));

        $ranked = $hits
            ->unique(fn ($h) => $h['identifier_type'] . '|' . $h['identifier_value'])
            ->sortByDesc(fn ($h) => $this->relevance($h['identifier_value'], $query))
            ->values();

        if ($ranked->isEmpty()) {
            $ranked = $this->fuzzyFallback($query, $limit);
        }

        return $ranked->take($limit)->values()->all();
    }

    /** Best single match for a query, or null if nothing found. */
    public function resolveBest(string $query): ?array
    {
        return $this->suggest($query, 1)[0] ?? null;
    }

    /**
     * Exact (non-fuzzy) existence check for a serial/asset tag across every equipment
     * source. Use this for duplicate detection — suggest()/resolveBest() rank by
     * relevance and can surface a substring/fuzzy match even when nothing is an exact
     * duplicate, which would incorrectly flag brand-new equipment as already existing.
     */
    public function existsExact(string $serial): ?array
    {
        $serial = trim($serial);
        if ($serial === '') {
            return null;
        }

        $receiving = EquipmentReceivingRegister::where('serial_number', $serial)
            ->orWhereJsonContains('serial_numbers', $serial)
            ->first();
        if ($receiving) {
            return [
                'identifier_type' => 'serial_number',
                'identifier_value' => $serial,
                'label' => "{$serial} — {$receiving->item_description}",
                'meta' => "Receiving register — Ref {$receiving->cross_ref_no}",
            ];
        }

        $workshop = WorkshopEquipmentRegister::where('serial_number_asset_tag', $serial)->first();
        if ($workshop) {
            return [
                'identifier_type' => 'serial_number',
                'identifier_value' => $serial,
                'label' => "{$serial} — {$workshop->equipment_type}",
                'meta' => "Workshop job {$workshop->entry_job_number} — {$workshop->status}",
            ];
        }

        $redistribution = EquipmentRedistribution::whereJsonContains('serial_numbers', $serial)->first();
        if ($redistribution) {
            return [
                'identifier_type' => 'serial_number',
                'identifier_value' => $serial,
                'label' => "{$serial} — assigned to {$redistribution->recipient_name}",
                'meta' => $redistribution->depot_department ?: $redistribution->redistribution_type,
            ];
        }

        $disposal = EquipmentDisposal::where('asset_tag_serial_no', $serial)->first();
        if ($disposal) {
            return [
                'identifier_type' => 'serial_number',
                'identifier_value' => $serial,
                'label' => "{$serial} — {$disposal->asset_description}",
                'meta' => "Disposal — {$disposal->status}",
            ];
        }

        return null;
    }

    private function relevance(string $value, string $query): int
    {
        $value = mb_strtolower($value);
        $q = mb_strtolower($query);
        return match (true) {
            $value === $q => 100,
            str_starts_with($value, $q) => 80,
            str_contains($value, $q) => 60,
            default => 40,
        };
    }

    private function searchReceiving(string $query): array
    {
        $like = "%{$query}%";
        $rows = EquipmentReceivingRegister::where(function ($q) use ($like) {
            $q->where('cross_ref_no', 'like', $like)
                ->orWhere('item_description', 'like', $like)
                ->orWhere('brand_model', 'like', $like)
                ->orWhere('serial_number', 'like', $like)
                ->orWhereRaw("JSON_SEARCH(serial_numbers, 'one', ?) IS NOT NULL", [$like]);
        })->with('redistributions')->latest()->limit(20)->get();

        $out = [];
        foreach ($rows as $r) {
            $serials = array_filter($r->serial_numbers ?? ($r->serial_number ? [$r->serial_number] : []));

            if (empty($serials)) {
                $out[] = [
                    'identifier_type' => 'cross_ref_no',
                    'identifier_value' => $r->cross_ref_no,
                    'label' => "{$r->cross_ref_no} — {$r->item_description}" . ($r->brand_model ? " ({$r->brand_model})" : ''),
                    'meta' => 'Receiving record — no serials recorded',
                ];
                continue;
            }

            // A row can match on its own fields (name/brand/ref) or on one specific serial inside
            // its JSON array. Only keep serials that are actually relevant to this match, so a
            // serial-only match doesn't drag in every other unit received on the same record.
            $rowFieldsMatch = str_contains(mb_strtolower($r->cross_ref_no ?? ''), mb_strtolower($query))
                || str_contains(mb_strtolower($r->item_description ?? ''), mb_strtolower($query))
                || str_contains(mb_strtolower($r->brand_model ?? ''), mb_strtolower($query));

            $holders = $r->deployedSerialsWithHolder();
            foreach ($serials as $sn) {
                if (!$rowFieldsMatch && !str_contains(mb_strtolower($sn), mb_strtolower($query))) {
                    continue;
                }
                $holder = $holders[$sn] ?? null;
                $out[] = [
                    'identifier_type' => 'serial_number',
                    'identifier_value' => $sn,
                    'label' => "{$sn} — {$r->item_description}" . ($r->brand_model ? " ({$r->brand_model})" : ''),
                    'meta' => $holder
                        ? 'Currently: ' . $holder['recipient'] . ($holder['depot'] ? ", {$holder['depot']}" : '')
                        : 'In stock — not yet assigned',
                ];
            }
        }

        return $out;
    }

    private function searchRedistributions(string $query): array
    {
        $like = "%{$query}%";
        $rows = EquipmentRedistribution::where(function ($q) use ($like) {
            $q->where('recipient_name', 'like', $like)
                ->orWhere('depot_department', 'like', $like)
                ->orWhereRaw("JSON_SEARCH(serial_numbers, 'one', ?) IS NOT NULL", [$like])
                ->orWhereRaw("JSON_SEARCH(asset_tags, 'one', ?) IS NOT NULL", [$like]);
        })->latest()->limit(20)->get();

        $out = [];
        foreach ($rows as $r) {
            $rowFieldsMatch = str_contains(mb_strtolower($r->recipient_name ?? ''), mb_strtolower($query))
                || str_contains(mb_strtolower($r->depot_department ?? ''), mb_strtolower($query));

            foreach (($r->serial_numbers ?? []) as $sn) {
                if (!$rowFieldsMatch && !str_contains(mb_strtolower($sn), mb_strtolower($query))) {
                    continue;
                }
                $out[] = [
                    'identifier_type' => 'serial_number',
                    'identifier_value' => $sn,
                    'label' => "{$sn} — assigned to {$r->recipient_name}",
                    'meta' => $r->depot_department ?: $r->redistribution_type,
                ];
            }
        }

        return $out;
    }

    private function searchWorkshop(string $query): array
    {
        $like = "%{$query}%";
        $rows = WorkshopEquipmentRegister::where(function ($q) use ($like) {
            $q->where('entry_job_number', 'like', $like)
                ->orWhere('equipment_type', 'like', $like)
                ->orWhere('brand_make_model', 'like', $like)
                ->orWhere('serial_number_asset_tag', 'like', $like)
                ->orWhere('contact_person', 'like', $like)
                ->orWhere('department', 'like', $like);
        })->latest()->limit(20)->get();

        $out = [];
        foreach ($rows as $r) {
            if (!$r->serial_number_asset_tag) {
                continue;
            }
            $out[] = [
                'identifier_type' => 'serial_number',
                'identifier_value' => $r->serial_number_asset_tag,
                'label' => "{$r->serial_number_asset_tag} — {$r->equipment_type}" . ($r->brand_make_model ? " ({$r->brand_make_model})" : ''),
                'meta' => "Workshop job {$r->entry_job_number} — {$r->status}",
            ];
        }

        return $out;
    }

    private function searchDisposals(string $query): array
    {
        $like = "%{$query}%";
        $rows = EquipmentDisposal::where(function ($q) use ($like) {
            $q->where('asset_tag_serial_no', 'like', $like)
                ->orWhere('asset_description', 'like', $like)
                ->orWhere('department_user', 'like', $like);
        })->latest()->limit(20)->get();

        $out = [];
        foreach ($rows as $r) {
            if (!$r->asset_tag_serial_no) {
                continue;
            }
            $out[] = [
                'identifier_type' => 'serial_number',
                'identifier_value' => $r->asset_tag_serial_no,
                'label' => "{$r->asset_tag_serial_no} — {$r->asset_description}",
                'meta' => "Disposal — {$r->status}",
            ];
        }

        return $out;
    }

    /** Typo-tolerant fallback when no substring match is found. */
    private function fuzzyFallback(string $query, int $limit): Collection
    {
        $candidates = collect();
        $q = mb_strtolower($query);

        foreach (EquipmentReceivingRegister::latest()->limit(300)->get() as $r) {
            $serials = array_filter($r->serial_numbers ?? ($r->serial_number ? [$r->serial_number] : []));
            $text = trim(($r->item_description ?? '') . ' ' . ($r->brand_model ?? '') . ' ' . ($r->cross_ref_no ?? '') . ' ' . implode(' ', $serials));
            if ($text === '') {
                continue;
            }
            similar_text($q, mb_strtolower($text), $pct);
            if ($pct < 45) {
                continue;
            }
            foreach ($serials ?: [null] as $sn) {
                $candidates->push([
                    'identifier_type' => $sn ? 'serial_number' : 'cross_ref_no',
                    'identifier_value' => $sn ?: $r->cross_ref_no,
                    'label' => ($sn ?: $r->cross_ref_no) . " — {$r->item_description}",
                    'meta' => 'Fuzzy match',
                    '_score' => $pct,
                ]);
            }
        }

        foreach (WorkshopEquipmentRegister::latest()->limit(300)->get() as $r) {
            if (!$r->serial_number_asset_tag) {
                continue;
            }
            $text = trim(($r->equipment_type ?? '') . ' ' . ($r->brand_make_model ?? '') . ' ' . $r->serial_number_asset_tag);
            similar_text($q, mb_strtolower($text), $pct);
            if ($pct < 45) {
                continue;
            }
            $candidates->push([
                'identifier_type' => 'serial_number',
                'identifier_value' => $r->serial_number_asset_tag,
                'label' => "{$r->serial_number_asset_tag} — {$r->equipment_type}",
                'meta' => 'Fuzzy match — workshop',
                '_score' => $pct,
            ]);
        }

        return $candidates
            ->unique(fn ($h) => $h['identifier_type'] . '|' . $h['identifier_value'])
            ->sortByDesc('_score')
            ->take($limit)
            ->map(function ($h) {
                unset($h['_score']);
                return $h;
            })
            ->values();
    }
}
