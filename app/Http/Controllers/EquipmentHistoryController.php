<?php

namespace App\Http\Controllers;

use App\Models\EquipmentDisposal;
use App\Models\EquipmentHistory;
use App\Models\EquipmentReceivingRegister;
use App\Models\EquipmentRedistribution;
use App\Services\ClaudeSearchAssistant;
use App\Services\EquipmentSearchService;
use Illuminate\Http\Request;

class EquipmentHistoryController extends Controller
{
    public function index(Request $request)
    {
        $history           = null;
        $searchValue       = null;
        $searchType        = null;
        $redistMap         = collect();
        $currentAssignment = null;
        $receivingRegister = null;
        $activeDisposal    = null;

        if ($request->filled('identifier')) {
            $searchValue = trim($request->identifier);
            $searchType  = $request->type ?: 'serial_number';

            if ($searchType === 'auto') {
                $searchType = $this->resolveType($searchValue);
            }

            // Find the receiving register that holds this serial number
            $receivingRegister = EquipmentReceivingRegister::whereJsonContains('serial_numbers', $searchValue)->first();
            if (!$receivingRegister && $searchType === 'serial_number') {
                // Fallback: match against the single serial_number column
                $receivingRegister = EquipmentReceivingRegister::where('serial_number', $searchValue)->first();
            }

            $history = EquipmentHistory::where('identifier_type', $searchType)
                ->where('identifier_value', 'like', "%{$searchValue}%")
                ->with('performer')
                ->orderBy('event_date', 'desc')
                ->get();

            // Load all redistribution records referenced in the history
            $redistIds = $history
                ->where('related_table', 'equipment_redistributions')
                ->pluck('related_id')
                ->filter()
                ->unique();

            if ($redistIds->isNotEmpty()) {
                $redistMap = EquipmentRedistribution::whereIn('id', $redistIds)->get()->keyBy('id');

                // Current assignment = most recent redistribution event
                $latestRedistEvent = $history
                    ->where('related_table', 'equipment_redistributions')
                    ->first(); // already sorted desc by event_date

                if ($latestRedistEvent && $redistMap->has($latestRedistEvent->related_id)) {
                    $currentAssignment = $redistMap->get($latestRedistEvent->related_id);
                }
            }

            // Check if this asset has an active (non-completed) disposal in progress
            $activeDisposal = EquipmentDisposal::where('asset_tag_serial_no', $searchValue)
                ->whereNotIn('status', ['Disposed', 'Rejected'])
                ->latest()
                ->first();

            // While disposal is active, suppress the current assignment display
            if ($activeDisposal) {
                $currentAssignment = null;
            }
        }

        return view('equipment-history.index', compact(
            'history', 'searchValue', 'searchType', 'redistMap', 'currentAssignment', 'receivingRegister', 'activeDisposal'
        ));
    }

    /** Live-suggest endpoint backing the smart search box (serial/asset tag/name/brand/recipient). */
    public function suggest(Request $request, EquipmentSearchService $search)
    {
        $query = trim((string) $request->query('q', ''));
        if (mb_strlen($query) < 2) {
            return response()->json(['results' => []]);
        }

        return response()->json(['results' => $search->suggest($query)]);
    }

    /** Natural-language "Ask AI" endpoint: resolves a plain-English query to equipment and summarizes its history. */
    public function ask(Request $request, EquipmentSearchService $search, ClaudeSearchAssistant $ai)
    {
        $request->validate(['query' => 'required|string|max:500']);
        $userQuery = $request->input('query');

        $term  = $ai->extractSearchTerm($userQuery) ?? $userQuery;
        $match = $search->resolveBest($term);

        if (!$match) {
            return redirect()->route('equipment-history.index')
                ->with('error', "Couldn't find any equipment matching \"{$userQuery}\".");
        }

        $history = EquipmentHistory::where('identifier_type', $match['identifier_type'])
            ->where('identifier_value', $match['identifier_value'])
            ->orderBy('event_date', 'desc')
            ->get();

        $redistIds = $history->where('related_table', 'equipment_redistributions')->pluck('related_id')->filter()->unique();
        $redistMap = $redistIds->isNotEmpty()
            ? EquipmentRedistribution::whereIn('id', $redistIds)->get()->keyBy('id')
            : collect();

        $events = $history->map(function ($event) use ($redistMap) {
            $redist = ($event->related_table === 'equipment_redistributions' && $redistMap->has($event->related_id))
                ? $redistMap->get($event->related_id)
                : null;

            return [
                'date' => optional($event->event_date)->format('d M Y'),
                'event_type' => $event->event_type,
                'description' => $event->event_description,
                'recipient' => $redist?->recipient_name,
            ];
        })->all();

        $summary = $ai->summarizeHistory($match['label'], $events);

        return redirect()->route('equipment-history.index', [
            'type' => $match['identifier_type'],
            'identifier' => $match['identifier_value'],
        ])->with('ai_summary', $summary)
          ->with('ai_summary_unavailable', $summary === null);
    }

    /** Best-guess identifier_type for a value typed directly into the search box without picking a suggestion. */
    private function resolveType(string $identifier): string
    {
        if (EquipmentHistory::where('identifier_type', 'cross_ref_no')->where('identifier_value', $identifier)->exists()
            && !EquipmentHistory::where('identifier_type', 'serial_number')->where('identifier_value', $identifier)->exists()) {
            return 'cross_ref_no';
        }

        return 'serial_number';
    }
}
