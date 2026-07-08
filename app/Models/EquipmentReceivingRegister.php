<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class EquipmentReceivingRegister extends Model
{
    use HasFactory;

    protected $fillable = [
        'cross_ref_no', 'date_received', 'supplier', 'delivery_note_no', 'po_no',
        'item_description', 'brand_model', 'serial_number', 'serial_numbers', 'qty_received',
        'accessories_included', 'user_signature', 'inspector_signature',
        'status', 'remarks', 'created_by', 'updated_by',
        'warranty_start_date', 'warranty_end_date', 'warranty_notes', 'warranty_expiry_logged',
    ];

    protected function casts(): array
    {
        return [
            'date_received'          => 'date',
            'qty_received'           => 'integer',
            'serial_numbers'         => 'array',
            'warranty_start_date'    => 'date',
            'warranty_end_date'      => 'date',
            'warranty_expiry_logged' => 'boolean',
        ];
    }

    public function warrantyStatus(): string
    {
        if (!$this->warranty_end_date) return 'none';
        if (now()->startOfDay()->greaterThan($this->warranty_end_date)) return 'expired';
        if (now()->startOfDay()->greaterThan($this->warranty_end_date->copy()->subDays(30))) return 'expiring';
        return 'active';
    }

    public function warrantyDaysLeft(): ?int
    {
        if (!$this->warranty_end_date) return null;
        return (int) now()->startOfDay()->diffInDays($this->warranty_end_date, false);
    }

    public function checkAndLogWarrantyExpiry(): void
    {
        if (!$this->warranty_end_date || $this->warranty_expiry_logged) return;
        if (!now()->startOfDay()->greaterThan($this->warranty_end_date)) return;

        $serials = array_filter($this->serial_numbers ?? ($this->serial_number ? [$this->serial_number] : []));

        if (!empty($serials)) {
            foreach ($serials as $sn) {
                EquipmentHistory::create([
                    'identifier_type'   => 'serial_number',
                    'identifier_value'  => $sn,
                    'event_type'        => 'Warranty Expired',
                    'event_description' => "Warranty expired on {$this->warranty_end_date->format('d M Y')}. Item: {$this->item_description}" . ($this->brand_model ? " ({$this->brand_model})" : '') . ". Ref: {$this->cross_ref_no}",
                    'related_table'     => 'equipment_receiving_registers',
                    'related_id'        => $this->id,
                    'performed_by'      => null,
                    'performed_by_name' => 'System (Auto)',
                    'event_date'        => $this->warranty_end_date,
                ]);
            }
        } else {
            EquipmentHistory::create([
                'identifier_type'   => 'cross_ref_no',
                'identifier_value'  => $this->cross_ref_no,
                'event_type'        => 'Warranty Expired',
                'event_description' => "Warranty expired on {$this->warranty_end_date->format('d M Y')}. Item: {$this->item_description}" . ($this->brand_model ? " ({$this->brand_model})" : ''),
                'related_table'     => 'equipment_receiving_registers',
                'related_id'        => $this->id,
                'performed_by'      => null,
                'performed_by_name' => 'System (Auto)',
                'event_date'        => $this->warranty_end_date,
            ]);
        }

        $this->updateQuietly(['warranty_expiry_logged' => true]);
    }

    public static function generateCrossRefNo(): string
    {
        $year = now()->year;
        $last = self::whereYear('created_at', $year)->orderByDesc('id')->first();
        $seq  = $last ? ((int) substr($last->cross_ref_no, -3)) + 1 : 1;
        return 'REC-' . $year . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function inspections()
    {
        return $this->hasMany(EquipmentInspection::class, 'equipment_receiving_register_id');
    }

    public function redistributions()
    {
        return $this->hasMany(EquipmentRedistribution::class, 'equipment_receiving_register_id');
    }

    public function totalInspected(): int
    {
        return (int) ($this->inspections_sum_qty_inspected ?? $this->inspections()->sum('qty_inspected'));
    }

    public function pendingInspection(): int
    {
        return max(0, $this->qty_received - $this->totalInspected());
    }

    /** True only when ALL received units have serial numbers recorded */
    public function hasSerialNumbers(): bool
    {
        return !empty($this->serial_numbers) && count($this->serial_numbers) === $this->qty_received;
    }

    /** Serials that have NEVER appeared in any redistribution (truly unassigned stock) */
    public function availableSerials(): array
    {
        $received     = $this->serial_numbers ?? [];
        $everAssigned = $this->redistributions
            ->flatMap(fn($r) => $r->serial_numbers ?? [])
            ->unique()
            ->toArray();
        return array_values(array_diff($received, $everAssigned));
    }

    /**
     * For each serial that HAS been redistributed at least once,
     * return its current holder (most recent redistribution).
     * Keyed by serial number.
     */
    public function deployedSerialsWithHolder(): array
    {
        $received = $this->serial_numbers ?? [];
        // Sort by id descending so the most recently created redistribution wins per serial
        $sorted = $this->redistributions->sortByDesc('id');

        $deployed = [];
        foreach ($sorted as $r) {
            foreach ($r->serial_numbers ?? [] as $sn) {
                if (in_array($sn, $received) && !array_key_exists($sn, $deployed)) {
                    $deployed[$sn] = [
                        'recipient' => $r->recipient_name,
                        'depot'     => $r->depot_department,
                        'type'      => $r->redistribution_type,
                        'date'      => $r->redistribution_date,
                        'ref'       => $r->redistribution_ref_no,
                        'asset_tag' => $r->asset_tags[$sn] ?? null,
                    ];
                }
            }
        }
        return $deployed;
    }

    public function totalRedistributed(): int
    {
        return (int) ($this->redistributions_sum_qty_redistributed ?? $this->redistributions()->sum('qty_redistributed'));
    }

    /** Unique serials currently deployed (assigned to someone) */
    public function deployedCount(): int
    {
        if ($this->hasSerialNumbers()) {
            return count($this->deployedSerialsWithHolder());
        }
        return $this->totalRedistributed();
    }

    /** Serials never yet assigned */
    public function availableStock(): int
    {
        if ($this->hasSerialNumbers()) {
            return count($this->availableSerials());
        }
        return max(0, $this->qty_received - $this->totalRedistributed());
    }

    public function stockStatus(): string
    {
        $inspected = $this->totalInspected();
        if ($inspected === 0) return 'Pending Inspection';
        if ($inspected < $this->qty_received) return 'Partially Inspected';
        return 'Fully Inspected';
    }
}
