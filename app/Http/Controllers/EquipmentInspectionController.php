<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\EquipmentHistory;
use App\Models\EquipmentInspection;
use App\Models\EquipmentReceivingRegister;
use App\Models\User;
use Illuminate\Http\Request;

class EquipmentInspectionController extends Controller
{
    public function create(EquipmentReceivingRegister $equipmentReceiving)
    {
        $pending = $equipmentReceiving->qty_received - $equipmentReceiving->inspections()->sum('qty_inspected');
        if ($pending <= 0) {
            return redirect()->route('equipment-receiving.show', $equipmentReceiving)
                ->with('error', 'All items in this delivery have already been inspected.');
        }
        $inspectors = User::whereIn('role', ['super_admin', 'hod'])->get();
        return view('equipment-receiving.inspect', compact('equipmentReceiving', 'inspectors', 'pending'));
    }

    public function store(Request $request, EquipmentReceivingRegister $equipmentReceiving)
    {
        $alreadyInspected = $equipmentReceiving->inspections()->sum('qty_inspected');
        $maxQty           = $equipmentReceiving->qty_received - $alreadyInspected;

        $request->validate([
            'qty_inspected'        => "required|integer|min:1|max:{$maxQty}",
            'inspection_status'    => 'required|in:Accepted,Rejected,Pending',
            'inspected_by'         => 'nullable|exists:users,id',
            'inspection_date'      => [
                'required', 'date', 'before_or_equal:today',
                'after_or_equal:'.$equipmentReceiving->date_received->format('Y-m-d'),
            ],
            'physical_condition'     => 'nullable|string|max:200',
            'functional_test_result' => 'nullable|string|max:100',
            'accessories_checked'    => 'nullable|string',
            'inspector_signature'    => 'nullable|string',
            'remarks'                => 'nullable|string',
        ]);

        $inspection = EquipmentInspection::create([
            ...$request->only([
                'qty_inspected', 'inspection_status', 'inspected_by', 'inspection_date',
                'physical_condition', 'functional_test_result', 'accessories_checked',
                'inspector_signature', 'remarks',
            ]),
            'inspection_ref_no'              => EquipmentInspection::generateRefNo(),
            'equipment_receiving_register_id' => $equipmentReceiving->id,
            'created_by'                     => auth()->id(),
        ]);

        $identifier = $equipmentReceiving->serial_number;
        if ($identifier) {
            EquipmentHistory::record(
                'serial_number', $identifier,
                'Inspection Recorded',
                "{$request->qty_inspected} item(s) inspected — {$request->inspection_status}. Ref: {$inspection->inspection_ref_no}",
                'equipment_inspections', $inspection->id
            );
        }

        AuditTrail::log(
            'create', 'Equipment Inspection',
            "Inspection {$inspection->inspection_ref_no} recorded for {$equipmentReceiving->cross_ref_no} ({$request->qty_inspected} items, {$request->inspection_status})",
            'success', $inspection->id
        );

        return redirect()->route('equipment-receiving.show', $equipmentReceiving)
            ->with('success', "Inspection {$inspection->inspection_ref_no} recorded. {$request->qty_inspected} item(s) deducted from pending stock.");
    }
}
