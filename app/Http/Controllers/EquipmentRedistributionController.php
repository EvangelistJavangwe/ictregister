<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\EquipmentDisposal;
use App\Models\EquipmentHistory;
use App\Models\EquipmentReceivingRegister;
use App\Models\EquipmentRedistribution;
use App\Models\WorkshopEquipmentRegister;
use Illuminate\Http\Request;

class EquipmentRedistributionController extends Controller
{
    public function create(EquipmentReceivingRegister $equipmentReceiving, Request $request)
    {
        if (!$equipmentReceiving->hasSerialNumbers()) {
            return redirect()->route('equipment-receiving.show', $equipmentReceiving)
                ->with('error', 'Serial numbers must be recorded for all '.$equipmentReceiving->qty_received.' unit(s) before redistribution is allowed.');
        }

        $equipmentReceiving->load('redistributions');

        $availableSerials   = $equipmentReceiving->availableSerials();
        $deployedWithHolder = $equipmentReceiving->deployedSerialsWithHolder();
        $filterSerial       = null;

        // If coming from the tracker with a specific serial, show only that one
        $filterSerial = $request->input('serial');
        if ($filterSerial) {
            $allReceived = $equipmentReceiving->serial_numbers ?? [];
            if (in_array($filterSerial, $allReceived)) {
                if (array_key_exists($filterSerial, $deployedWithHolder)) {
                    $availableSerials   = [];
                    $deployedWithHolder = [$filterSerial => $deployedWithHolder[$filterSerial]];
                } else {
                    $availableSerials   = [$filterSerial];
                    $deployedWithHolder = [];
                }
            }
        }

        $allSerials = array_merge($availableSerials, array_keys($deployedWithHolder));

        $workshopLockedSerials = WorkshopEquipmentRegister::whereIn('serial_number_asset_tag', $allSerials)
            ->whereNotIn('status', ['Collected'])
            ->get(['serial_number_asset_tag', 'entry_job_number', 'status'])
            ->keyBy('serial_number_asset_tag')
            ->toArray();

        $disposalLockedSerials = EquipmentDisposal::whereIn('asset_tag_serial_no', $allSerials)
            ->whereNotIn('status', ['Disposed', 'Rejected'])
            ->get(['asset_tag_serial_no', 'disposal_ref_no', 'status'])
            ->keyBy('asset_tag_serial_no')
            ->toArray();

        return view('equipment-receiving.redistribute', compact(
            'equipmentReceiving', 'availableSerials', 'deployedWithHolder', 'filterSerial',
            'workshopLockedSerials', 'disposalLockedSerials'
        ));
    }

    public function store(Request $request, EquipmentReceivingRegister $equipmentReceiving)
    {
        if (!$equipmentReceiving->hasSerialNumbers()) {
            return redirect()->route('equipment-receiving.show', $equipmentReceiving)
                ->with('error', 'Serial numbers must be recorded before redistribution.');
        }

        $equipmentReceiving->load('redistributions');

        $request->validate([
            'redistribution_date' => [
                'required', 'date', 'before_or_equal:today',
                'after_or_equal:'.$equipmentReceiving->date_received->format('Y-m-d'),
            ],
            'redistribution_type' => 'required|in:Individual,Depot/Department',
            'recipient_name'      => 'required|string|max:200',
            'depot_department'    => 'nullable|string|max:200',
            'selected_serials'    => 'required|array|min:1',
            'selected_serials.*'  => 'required|string',
            'asset_tags'          => 'nullable|array',
            'asset_tags.*'        => 'nullable|string|max:100',
            'cross_ref_form208'   => 'required|string|max:100',
            'issued_by'           => 'required|string|max:200',
            'receiver_signature'  => 'nullable|string',
            'issuer_signature'    => 'nullable|string',
            'remarks'             => 'required|string',
        ]);

        $selectedSerials = $request->input('selected_serials', []);
        $allReceived     = $equipmentReceiving->serial_numbers ?? [];

        // Validate all selected serials belong to this delivery
        $invalid = array_diff($selectedSerials, $allReceived);
        if (!empty($invalid)) {
            return back()->withInput()
                ->withErrors(['selected_serials' => 'Invalid serial number(s): '.implode(', ', $invalid)]);
        }

        // Block redistribution for serials currently in an active workshop job
        $workshopLocked = WorkshopEquipmentRegister::whereIn('serial_number_asset_tag', $selectedSerials)
            ->whereNotIn('status', ['Collected'])
            ->get(['serial_number_asset_tag', 'entry_job_number', 'status']);

        if ($workshopLocked->isNotEmpty()) {
            $detail = $workshopLocked->map(fn($w) => "{$w->serial_number_asset_tag} (Job: {$w->entry_job_number}, Status: {$w->status})")->implode('; ');
            return back()->withInput()
                ->withErrors(['selected_serials' => "Cannot redistribute — the following serial(s) are currently in the workshop: {$detail}. They must be collected before redistribution."]);
        }

        // Block redistribution for serials with an active disposal request
        $disposalLocked = EquipmentDisposal::whereIn('asset_tag_serial_no', $selectedSerials)
            ->whereNotIn('status', ['Disposed', 'Rejected'])
            ->get(['asset_tag_serial_no', 'disposal_ref_no', 'status']);

        if ($disposalLocked->isNotEmpty()) {
            $detail = $disposalLocked->map(fn($d) => "{$d->asset_tag_serial_no} (Ref: ".($d->disposal_ref_no ?? 'pending').", Status: {$d->status})")->implode('; ');
            return back()->withInput()
                ->withErrors(['selected_serials' => "Cannot redistribute — the following serial(s) have an active disposal request: {$detail}. The disposal must be completed or rejected first."]);
        }

        // Build asset_tags map keyed by serial number
        $assetTagsRaw = $request->input('asset_tags', []);
        $assetTagsMap = [];
        foreach ($selectedSerials as $sn) {
            $tag = trim($assetTagsRaw[$sn] ?? '');
            if ($tag !== '') $assetTagsMap[$sn] = $tag;
        }

        // Determine if this is a fresh assignment or a transfer
        $deployedWithHolder = $equipmentReceiving->deployedSerialsWithHolder();
        $transfers = array_intersect($selectedSerials, array_keys($deployedWithHolder));
        $fresh     = array_diff($selectedSerials, array_keys($deployedWithHolder));

        $redistribution = EquipmentRedistribution::create([
            'redistribution_ref_no'           => EquipmentRedistribution::generateRefNo(),
            'equipment_receiving_register_id'  => $equipmentReceiving->id,
            'redistribution_date'             => $request->redistribution_date,
            'redistribution_type'             => $request->redistribution_type,
            'recipient_name'                  => $request->recipient_name,
            'depot_department'                => $request->depot_department,
            'qty_redistributed'               => count($selectedSerials),
            'serial_numbers'                  => $selectedSerials,
            'asset_tags'                      => !empty($assetTagsMap) ? $assetTagsMap : null,
            'cross_ref_form208'               => $request->cross_ref_form208,
            'issued_by'                       => $request->issued_by,
            'receiver_signature'              => $request->receiver_signature,
            'issuer_signature'                => $request->issuer_signature,
            'remarks'                         => $request->remarks,
            'created_by'                      => auth()->id(),
        ]);

        // Record history for every serial — note transfers show previous holder
        foreach ($selectedSerials as $sn) {
            $assetTag = $assetTagsMap[$sn] ?? null;

            if (isset($deployedWithHolder[$sn])) {
                $prev = $deployedWithHolder[$sn];
                $desc = "Transferred from {$prev['recipient']}"
                    .($prev['depot'] ? " ({$prev['depot']})" : '')
                    ." to {$request->recipient_name}"
                    .($request->depot_department ? " ({$request->depot_department})" : '').'.';
            } else {
                $desc = "Assigned to {$request->recipient_name}"
                    .($request->depot_department ? " ({$request->depot_department})" : '').'.';
            }

            if ($assetTag) $desc .= " Asset Tag: {$assetTag}.";
            $desc .= " Ref: {$redistribution->redistribution_ref_no}";

            EquipmentHistory::record('serial_number', $sn,
                !empty($transfers) && isset($deployedWithHolder[$sn]) ? 'Equipment Transferred' : 'Equipment Redistributed',
                $desc, 'equipment_redistributions', $redistribution->id);
        }

        $transferCount = count($transfers);
        $freshCount    = count($fresh);
        $detail = [];
        if ($freshCount)    $detail[] = "{$freshCount} newly assigned";
        if ($transferCount) $detail[] = "{$transferCount} transferred";

        AuditTrail::log('create', 'Equipment Redistribution',
            implode(', ', $detail)." → {$request->recipient_name}. Ref: {$redistribution->redistribution_ref_no}",
            'success', $redistribution->id);

        return redirect()->route('equipment-receiving.show', $equipmentReceiving)
            ->with('success', "Redistribution {$redistribution->redistribution_ref_no} saved. ".implode(', ', $detail).".");
    }
}
