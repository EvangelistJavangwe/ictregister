<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\Depot;
use App\Models\EquipmentDisposal;
use App\Models\EquipmentHistory;
use App\Models\EquipmentReceivingRegister;
use App\Models\WorkshopEquipmentRegister;
use Illuminate\Http\Request;

class DisposalController extends Controller
{
    public function lookupSerial(Request $request)
    {
        $serial = trim($request->query('serial', ''));
        if (strlen($serial) < 3) {
            return response()->json(['found' => false]);
        }

        $record = EquipmentReceivingRegister::where(function ($q) use ($serial) {
            $q->whereJsonContains('serial_numbers', $serial)
              ->orWhere('serial_number', $serial);
        })->latest()->first();

        if ($record) {
            // Most recent redistribution containing this serial
            $latestRedist = $record->redistributions()
                ->latest()
                ->get()
                ->first(fn($r) => in_array($serial, $r->serial_numbers ?? []));

            $departmentUser = null;
            if ($latestRedist) {
                $departmentUser = $latestRedist->recipient_name;
                if ($latestRedist->depot_department) {
                    $departmentUser .= ' — ' . $latestRedist->depot_department;
                }
            }

            return response()->json([
                'found'             => true,
                'source'            => 'registry',
                'asset_description' => $record->item_description,
                'model_brand'       => $record->brand_model ?? '',
                'department_user'   => $departmentUser ?? '',
                'date_acquired'     => $record->date_received?->format('Y-m-d') ?? '',
            ]);
        }

        // Not part of the Helpdesk Registry — fall back to a Workshop job for this serial,
        // so equipment that was only ever logged directly at the workshop (never formally
        // received) can still be recognised when it finally comes up for disposal.
        $job = WorkshopEquipmentRegister::where('serial_number_asset_tag', $serial)->latest()->first();

        if (!$job) {
            return response()->json(['found' => false]);
        }

        $departmentUser = trim($job->department . ($job->contact_person ? " (Ext. {$job->contact_person})" : ''));

        return response()->json([
            'found'             => true,
            'source'            => 'workshop',
            'asset_description' => $job->equipment_type,
            'model_brand'       => $job->brand_make_model ?? '',
            'department_user'   => $departmentUser ?: ($job->depot_name ?? ''),
            'date_acquired'     => $job->date_time_received?->format('Y-m-d') ?? '',
        ]);
    }

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = EquipmentDisposal::with('requester', 'approver');

        if ($user->isTechnician()) {
            $query->where('requested_by', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sq) use ($q) {
                $sq->where('disposal_ref_no', 'like', "%{$q}%")
                    ->orWhere('asset_description', 'like', "%{$q}%")
                    ->orWhere('asset_tag_serial_no', 'like', "%{$q}%");
            });
        }

        $records = $query->latest()->paginate(15);
        return view('disposal.index', compact('records'));
    }

    public function create()
    {
        $user = auth()->user();
        $depots = Depot::names();
        return view('disposal.create', compact('user', 'depots'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'asset_description'    => 'required|string|max:255',
            'asset_tag_serial_no'  => 'required|string|max:100',
            'model_brand'          => 'required|string|max:150',
            'department_user'      => 'required|string|max:200',
            'date_acquired'        => 'nullable|date|before_or_equal:today',
            'condition_at_disposal'=> 'required|string|max:200',
            'reason_for_disposal'  => 'required|string',
            'disposal_method'      => 'nullable|string|max:100',
            'disposal_value'       => 'nullable|numeric|min:0',
            'data_wiped_destroyed' => 'nullable|boolean',
            'remarks'              => 'required|string',
        ]);

        $isTechnician = $user->isTechnician();

        $record = EquipmentDisposal::create([
            ...$request->only([
                'asset_description', 'asset_tag_serial_no', 'model_brand',
                'department_user', 'date_acquired', 'condition_at_disposal',
                'reason_for_disposal', 'disposal_method', 'disposal_value',
                'data_wiped_destroyed', 'remarks',
            ]),
            'data_wiped_destroyed' => $request->boolean('data_wiped_destroyed'),
            'status'       => $isTechnician ? 'Requested' : 'Pending Approval',
            'requested_by' => $user->id,
            'disposal_ref_no' => $isTechnician ? null : EquipmentDisposal::generateDisposalRefNo(),
        ]);

        if ($record->asset_tag_serial_no) {
            EquipmentHistory::record(
                'serial_number', $record->asset_tag_serial_no,
                'Disposal Requested',
                "Disposal request submitted. Reason: {$record->reason_for_disposal}",
                'equipment_disposals', $record->id
            );
        }

        Depot::rememberIfNew($record->department_user);

        AuditTrail::log('create', 'Disposal', "Disposal request created for: {$record->asset_description}", 'info', $record->id);

        return redirect()->route('disposal.show', $record)->with('success', 'Disposal request submitted successfully.');
    }

    public function show(EquipmentDisposal $disposal)
    {
        $disposal->load('requester', 'approver');
        return view('disposal.show', compact('disposal'));
    }

    public function edit(EquipmentDisposal $disposal)
    {
        if (auth()->user()->isTechnician()) abort(403);
        $depots = Depot::names();
        return view('disposal.edit', compact('disposal', 'depots'));
    }

    public function update(Request $request, EquipmentDisposal $disposal)
    {
        if (auth()->user()->isTechnician()) abort(403);

        $request->validate([
            'asset_description'    => 'required|string|max:255',
            'asset_tag_serial_no'  => 'required|string|max:100',
            'model_brand'          => 'required|string|max:150',
            'department_user'      => 'required|string|max:200',
            'condition_at_disposal'=> 'required|string|max:200',
            'reason_for_disposal'  => 'required|string',
            'disposal_method'      => 'nullable|string|max:100',
            'date_acquired'        => 'nullable|date|before_or_equal:today',
            'date_disposed'        => array_filter([
                'nullable', 'date', 'before_or_equal:today',
                $request->filled('date_acquired') ? 'after_or_equal:'.$request->date_acquired : null,
            ]),
            'disposal_value'       => 'nullable|numeric|min:0',
            'data_wiped_destroyed' => 'nullable|boolean',
            'remarks'              => 'required|string',
        ]);

        $disposal->update([
            ...$request->only([
                'asset_description', 'asset_tag_serial_no', 'model_brand',
                'department_user', 'date_acquired', 'condition_at_disposal',
                'reason_for_disposal', 'disposal_method', 'date_disposed',
                'disposal_value', 'remarks',
            ]),
            'data_wiped_destroyed' => $request->boolean('data_wiped_destroyed'),
        ]);

        Depot::rememberIfNew($disposal->department_user);

        AuditTrail::log('update', 'Disposal', "Updated disposal record: {$disposal->asset_description}", 'success', $disposal->id);

        return redirect()->route('disposal.show', $disposal)->with('success', 'Disposal record updated.');
    }

    public function approve(Request $request, EquipmentDisposal $disposal)
    {
        if (!auth()->user()->isAdminOrHod()) abort(403);

        $request->validate([
            'approved_signature' => 'nullable|string',
            'review_note'        => 'nullable|string',
        ]);

        $disposal->update([
            'status'             => 'Approved',
            'approved_by'        => auth()->id(),
            'disposal_ref_no'    => $disposal->disposal_ref_no ?? EquipmentDisposal::generateDisposalRefNo(),
            'approved_signature' => $request->approved_signature,
            'review_note'        => $request->review_note,
        ]);

        if ($disposal->asset_tag_serial_no) {
            EquipmentHistory::record(
                'serial_number', $disposal->asset_tag_serial_no,
                'Disposal Approved',
                "Disposal approved. Ref: {$disposal->disposal_ref_no}",
                'equipment_disposals', $disposal->id
            );
        }

        AuditTrail::log('approve_disposal', 'Disposal', "Approved disposal: {$disposal->disposal_ref_no}", 'success', $disposal->id);

        return back()->with('success', 'Disposal approved and reference number assigned.');
    }

    public function reject(Request $request, EquipmentDisposal $disposal)
    {
        if (!auth()->user()->isAdminOrHod()) abort(403);

        $request->validate(['review_note' => 'required|string']);

        $disposal->update([
            'status'      => 'Rejected',
            'review_note' => $request->review_note,
            'approved_by' => auth()->id(),
        ]);

        AuditTrail::log('reject_disposal', 'Disposal', "Rejected disposal request for: {$disposal->asset_description}", 'warning', $disposal->id);

        return back()->with('success', 'Disposal request rejected.');
    }

    public function markDisposed(Request $request, EquipmentDisposal $disposal)
    {
        if (!auth()->user()->isAdminOrHod()) abort(403);

        $request->validate([
            'date_disposed'    => array_filter([
                'required', 'date', 'before_or_equal:today',
                $disposal->date_acquired ? 'after_or_equal:'.$disposal->date_acquired->format('Y-m-d') : null,
            ]),
            'disposal_method'  => 'required|string|max:100',
        ]);

        $disposal->update([
            'status'           => 'Disposed',
            'date_disposed'    => $request->date_disposed,
            'disposal_method'  => $request->disposal_method,
        ]);

        if ($disposal->asset_tag_serial_no) {
            EquipmentHistory::record(
                'serial_number', $disposal->asset_tag_serial_no,
                'Equipment Disposed',
                "Equipment disposed on {$request->date_disposed}. Method: {$request->disposal_method}. Ref: {$disposal->disposal_ref_no}",
                'equipment_disposals', $disposal->id
            );
        }

        AuditTrail::log('dispose', 'Disposal', "Equipment disposed: {$disposal->disposal_ref_no}", 'warning', $disposal->id);

        return back()->with('success', 'Equipment marked as disposed.');
    }
}
