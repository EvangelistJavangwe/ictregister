<?php

namespace App\Http\Controllers;

use App\Models\EquipmentDisposal;
use App\Models\EquipmentHistory;
use App\Models\EquipmentReceivingRegister;
use App\Models\EquipmentRedistribution;
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
            $searchType  = $request->type ?? 'serial_number';

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
}
