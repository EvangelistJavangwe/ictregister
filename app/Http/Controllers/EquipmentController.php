<?php

namespace App\Http\Controllers;

use App\Models\EquipmentReceivingRegister;
use App\Models\WorkshopEquipmentRegister;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class EquipmentController extends Controller
{
    public function index(Request $request)
    {
        $rows = $this->buildRows($request);

        return view('equipment.index', [
            'rows' => $rows,
            'type' => $request->input('type', 'all'),
        ]);
    }

    public static function buildRows(Request $request): Collection
    {
        $type   = $request->input('type', 'all');
        $search = $request->input('search');

        $rows = collect();

        if ($type === 'all' || $type === 'workshop') {
            // Once collected, the equipment has physically left the workshop —
            // it no longer counts as stock "in workshop".
            $query = WorkshopEquipmentRegister::with('technician')
                ->where('status', '!=', 'Collected');
            if ($search) {
                $query->where(fn($q) => $q
                    ->where('entry_job_number', 'like', "%{$search}%")
                    ->orWhere('equipment_type', 'like', "%{$search}%")
                    ->orWhere('serial_number_asset_tag', 'like', "%{$search}%"));
            }
            foreach ($query->latest()->get() as $job) {
                $rows->push((object) [
                    'source'      => 'Workshop',
                    'ref'         => $job->entry_job_number,
                    'item'        => $job->equipment_type,
                    'brand'       => $job->brand_make_model,
                    'serial'      => $job->serial_number_asset_tag,
                    'status'      => $job->status,
                    'handler'     => trim(($job->technician?->firstname ?? '') . ' ' . ($job->technician?->lastname ?? '')) ?: '— Unassigned —',
                    'date'        => $job->date_time_received,
                    'view_route'  => 'workshop.show',
                    'view_id'     => $job->id,
                ]);
            }
        }

        if ($type === 'all' || $type === 'registry') {
            $query = EquipmentReceivingRegister::with('redistributions')
                ->withSum('redistributions', 'qty_redistributed');
            if ($search) {
                $query->where(fn($q) => $q
                    ->where('cross_ref_no', 'like', "%{$search}%")
                    ->orWhere('item_description', 'like', "%{$search}%")
                    ->orWhere('serial_number', 'like', "%{$search}%"));
            }
            foreach ($query->latest()->get() as $record) {
                $available = $record->availableStock();

                // Once every unit from this record has been redistributed, it no
                // longer represents stock on hand — drop it from the registry view.
                if ($available <= 0) {
                    continue;
                }

                $rows->push((object) [
                    'source'      => 'Registry',
                    'ref'         => $record->cross_ref_no,
                    'item'        => $record->item_description,
                    'brand'       => $record->brand_model,
                    'serial'      => $record->serial_number ?? implode(', ', $record->serial_numbers ?? []),
                    'status'      => $record->stockStatus(),
                    'available'   => $available,
                    'handler'     => $record->supplier,
                    'date'        => $record->date_received,
                    'view_route'  => 'equipment-receiving.show',
                    'view_id'     => $record->id,
                ]);
            }
        }

        return $rows->sortByDesc('date')->values();
    }
}
