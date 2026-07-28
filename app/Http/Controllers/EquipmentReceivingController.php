<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\EquipmentHistory;
use App\Models\EquipmentReceivingRegister;
use App\Models\User;
use Illuminate\Http\Request;

class EquipmentReceivingController extends Controller
{
    public function index(Request $request)
    {
        $query = EquipmentReceivingRegister::with('creator')
            ->withSum('inspections', 'qty_inspected')
            ->withSum('redistributions', 'qty_redistributed');

        if ($request->filled('status')) {
            // Filter by computed stock status using a WHERE-clause subquery (not HAVING —
            // comparing a plain column like qty_received inside HAVING without GROUP BY
            // fails under MySQL's ONLY_FULL_GROUP_BY mode).
            $inspectedSub = '(select coalesce(sum(qty_inspected), 0) from equipment_inspections '
                . 'where equipment_inspections.equipment_receiving_register_id = equipment_receiving_registers.id)';
            match ($request->status) {
                'pending'   => $query->whereRaw("{$inspectedSub} = 0"),
                'partial'   => $query->whereRaw("{$inspectedSub} > 0 AND {$inspectedSub} < equipment_receiving_registers.qty_received"),
                'inspected' => $query->whereRaw("{$inspectedSub} >= equipment_receiving_registers.qty_received"),
                default     => null,
            };
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sq) use ($q) {
                $sq->where('cross_ref_no', 'like', "%{$q}%")
                    ->orWhere('supplier', 'like', "%{$q}%")
                    ->orWhere('item_description', 'like', "%{$q}%")
                    ->orWhere('serial_number', 'like', "%{$q}%");
            });
        }

        $records = $query->latest()->paginate(15);
        return view('equipment-receiving.index', compact('records'));
    }

    public function create()
    {
        return view('equipment-receiving.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'date_received'        => 'required|date|before_or_equal:today',
            'supplier'             => 'required|string|max:200',
            'item_description'     => 'required|string|max:255',
            'qty_received'         => 'required|integer|min:1',
            'delivery_note_no'     => 'required|string|max:100',
            'po_no'                => 'required|string|max:100',
            'brand_model'          => 'required|string|max:150',
            'cost_price'           => 'nullable|numeric|min:0',
            'serial_numbers'       => [
                'required', 'array', 'min:1',
                function ($attribute, $value, $fail) use ($request) {
                    if (count($value) < (int) $request->qty_received) {
                        $fail('Please provide a serial number for every unit received.');
                    }
                },
            ],
            'serial_numbers.*'     => 'required|string|max:100',
            'accessories_included' => 'required|string',
            'user_signature'       => 'nullable|string',
            'inspector_signature'  => 'nullable|string',
            'remarks'              => 'required|string',
            'warranty_start_date'  => 'nullable|date',
            'warranty_end_date'    => 'nullable|date|after_or_equal:warranty_start_date',
            'warranty_notes'       => 'nullable|string|max:500',
        ]);

        $serials = array_values(array_filter($request->input('serial_numbers', []), fn($s) => trim($s) !== ''));

        $record = EquipmentReceivingRegister::create([
            ...$request->only([
                'date_received', 'supplier', 'item_description', 'qty_received',
                'delivery_note_no', 'po_no', 'brand_model', 'cost_price',
                'accessories_included', 'user_signature', 'inspector_signature', 'remarks',
                'warranty_start_date', 'warranty_end_date', 'warranty_notes',
            ]),
            'serial_number'  => $serials[0] ?? null,
            'serial_numbers' => !empty($serials) ? $serials : null,
            'cross_ref_no'   => EquipmentReceivingRegister::generateCrossRefNo(),
            'status'         => 'Received',
            'created_by'     => auth()->id(),
        ]);

        foreach ($serials as $sn) {
            EquipmentHistory::record(
                'serial_number', $sn,
                'Equipment Received',
                "Received from {$record->supplier}. Ref: {$record->cross_ref_no}",
                'equipment_receiving_registers', $record->id
            );
        }

        if ($record->warranty_end_date) {
            $wStart       = $record->warranty_start_date?->format('d M Y') ?? 'N/A';
            $warrantyDesc = "Warranty period: {$wStart} – {$record->warranty_end_date->format('d M Y')}";
            if (!empty($serials)) {
                foreach ($serials as $sn) {
                    EquipmentHistory::record('serial_number', $sn, 'Warranty Registered', $warrantyDesc, 'equipment_receiving_registers', $record->id);
                }
            } else {
                EquipmentHistory::create([
                    'identifier_type'   => 'cross_ref_no',
                    'identifier_value'  => $record->cross_ref_no,
                    'event_type'        => 'Warranty Registered',
                    'event_description' => $warrantyDesc,
                    'related_table'     => 'equipment_receiving_registers',
                    'related_id'        => $record->id,
                    'performed_by'      => auth()->id(),
                    'performed_by_name' => auth()->user()->full_name ?? auth()->user()->username,
                    'event_date'        => now(),
                ]);
            }
        }

        AuditTrail::log('create', 'Equipment Receiving', "Created delivery record: {$record->cross_ref_no}", 'success', $record->id);

        return redirect()->route('equipment-receiving.show', $record)
            ->with('success', "Delivery record {$record->cross_ref_no} created. You can now add inspection records.");
    }

    public function show(EquipmentReceivingRegister $equipmentReceiving)
    {
        $equipmentReceiving->load([
            'creator', 'updater',
            'inspections.inspector', 'inspections.creator',
            'redistributions.creator',
        ]);

        // Auto-log warranty expiry the first time we detect it
        $equipmentReceiving->checkAndLogWarrantyExpiry();

        $inspectors = User::whereIn('role', ['super_admin', 'hod'])->get();
        return view('equipment-receiving.show', compact('equipmentReceiving', 'inspectors'));
    }

    public function edit(EquipmentReceivingRegister $equipmentReceiving)
    {
        return view('equipment-receiving.edit', compact('equipmentReceiving'));
    }

    public function update(Request $request, EquipmentReceivingRegister $equipmentReceiving)
    {
        $request->validate([
            'date_received'       => 'required|date|before_or_equal:today',
            'supplier'            => 'required|string|max:200',
            'item_description'    => 'required|string|max:255',
            'qty_received'        => 'required|integer|min:1',
            'delivery_note_no'    => 'required|string|max:100',
            'po_no'               => 'required|string|max:100',
            'brand_model'         => 'required|string|max:150',
            'cost_price'          => 'nullable|numeric|min:0',
            'accessories_included' => 'required|string',
            'remarks'             => 'required|string',
            'warranty_start_date' => 'nullable|date',
            'warranty_end_date'   => 'nullable|date|after_or_equal:warranty_start_date',
            'warranty_notes'      => 'nullable|string|max:500',
        ]);

        $oldWarrantyEnd = $equipmentReceiving->warranty_end_date?->toDateString();
        $newWarrantyEnd = $request->input('warranty_end_date');

        $equipmentReceiving->update([
            ...$request->only([
                'date_received', 'supplier', 'item_description', 'qty_received',
                'delivery_note_no', 'po_no', 'brand_model', 'cost_price', 'serial_number',
                'accessories_included', 'remarks',
                'warranty_start_date', 'warranty_end_date', 'warranty_notes',
            ]),
            'warranty_expiry_logged' => $newWarrantyEnd !== $oldWarrantyEnd ? false : $equipmentReceiving->warranty_expiry_logged,
            'updated_by'             => auth()->id(),
        ]);

        // Log a warranty update event in history when the warranty end date changes
        if ($newWarrantyEnd && $newWarrantyEnd !== $oldWarrantyEnd) {
            $warrantyDesc = "Warranty updated: {$request->warranty_start_date} – {$newWarrantyEnd}";
            $serials = array_filter($equipmentReceiving->serial_numbers ?? ($equipmentReceiving->serial_number ? [$equipmentReceiving->serial_number] : []));
            if (!empty($serials)) {
                foreach ($serials as $sn) {
                    EquipmentHistory::record('serial_number', $sn, 'Warranty Updated', $warrantyDesc, 'equipment_receiving_registers', $equipmentReceiving->id);
                }
            } else {
                EquipmentHistory::create([
                    'identifier_type'   => 'cross_ref_no',
                    'identifier_value'  => $equipmentReceiving->cross_ref_no,
                    'event_type'        => 'Warranty Updated',
                    'event_description' => $warrantyDesc,
                    'related_table'     => 'equipment_receiving_registers',
                    'related_id'        => $equipmentReceiving->id,
                    'performed_by'      => auth()->id(),
                    'performed_by_name' => auth()->user()->full_name ?? auth()->user()->username,
                    'event_date'        => now(),
                ]);
            }
        }

        AuditTrail::log('update', 'Equipment Receiving', "Updated delivery record: {$equipmentReceiving->cross_ref_no}", 'success', $equipmentReceiving->id);

        return redirect()->route('equipment-receiving.show', $equipmentReceiving)
            ->with('success', 'Delivery record updated successfully.');
    }
}
