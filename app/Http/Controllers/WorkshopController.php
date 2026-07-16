<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\EquipmentHistory;
use App\Models\EquipmentReceivingRegister;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\WorkshopEquipmentRegister;
use App\Services\EquipmentSearchService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class WorkshopController extends Controller
{
    /** Maps normalized (trimmed, uppercased) template headers to internal row keys. */
    private const IMPORT_HEADER_MAP = [
        'DATE RECEIVED' => 'date_received',
        'FROM' => 'from',
        'TO' => 'to',
        'FAULT' => 'fault',
        'MODEL' => 'model',
        'TYPE' => 'type',
        'RECEIVED BY' => 'received_by',
        'STATUS' => 'status',
        'COLLECTED/CARTED BY' => 'collected_by',
        'TECHNICIAN' => 'technician',
        'DEPARTURE DATE' => 'departure_date',
        'SERIAL NO' => 'serial_no',
        '208 NUMBER' => 'form208',
    ];

    public function index(Request $request)
    {
        $user = auth()->user();
        $query = WorkshopEquipmentRegister::with('technician', 'creator');

        if ($user->isTechnician()) {
            $query->where('technician_assigned', $user->id);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority')) {
            $query->where('priority_level', $request->priority);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sq) use ($q) {
                $sq->where('entry_job_number', 'like', "%{$q}%")
                    ->orWhere('equipment_type', 'like', "%{$q}%")
                    ->orWhere('serial_number_asset_tag', 'like', "%{$q}%")
                    ->orWhere('depot_name', 'like', "%{$q}%")
                    ->orWhere('department', 'like', "%{$q}%")
                    ->orWhere('contact_person', 'like', "%{$q}%");
            });
        }

        $overdue = (clone $query)->where('due_date', '<', now())
            ->whereNotIn('status', ['Completed', 'Collected'])->count();

        // Status counts (unfiltered by status so the summary always shows totals)
        $baseQuery = WorkshopEquipmentRegister::query();
        if ($user->isTechnician()) {
            $baseQuery->where('technician_assigned', $user->id);
        }
        $statusCounts = (clone $baseQuery)->selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $jobs = $query->oldest()->paginate(15);
        $technicians = User::where('role', 'technician')->get();

        return view('workshop.index', compact('jobs', 'overdue', 'technicians', 'statusCounts'));
    }

    public function lookupSerial(Request $request)
    {
        $serial = trim($request->query('serial', ''));
        if (strlen($serial) < 3) {
            return response()->json(['found' => false]);
        }

        // Search in the serial_numbers JSON array (exact match, case-insensitive)
        $record = EquipmentReceivingRegister::where(function ($q) use ($serial) {
            $q->whereJsonContains('serial_numbers', $serial)
              ->orWhere('serial_number', $serial);
        })->latest()->first();

        if ($record) {
            // Find current holder from most recent redistribution containing this serial
            $latestRedist = $record->redistributions()
                ->latest()
                ->get()
                ->first(fn($r) => in_array($serial, $r->serial_numbers ?? []));

            return response()->json([
                'found'          => true,
                'source'         => 'registry',
                'equipment_type' => $record->item_description,
                'brand_model'    => $record->brand_model ?? '',
                'assigned_to'    => $latestRedist ? $latestRedist->recipient_name : null,
                'depot'          => $latestRedist ? $latestRedist->depot_department : null,
            ]);
        }

        // Not part of the Helpdesk Registry — fall back to a prior Workshop job for
        // this serial, so equipment that was logged directly at the workshop (never
        // received via Helpdesk intake) is still recognised on a repeat visit.
        $priorJob = WorkshopEquipmentRegister::where('serial_number_asset_tag', $serial)
            ->latest()
            ->first();

        if (!$priorJob) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found'          => true,
            'source'         => 'workshop',
            'equipment_type' => $priorJob->equipment_type,
            'brand_model'    => $priorJob->brand_make_model ?? '',
            'assigned_to'    => $priorJob->contact_person,
            'depot'          => $priorJob->department,
            'last_job'       => $priorJob->entry_job_number,
        ]);
    }

    public function create()
    {
        $technicians = User::where('role', 'technician')->get();
        return view('workshop.create', compact('technicians'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'date_time_received'       => 'required|date|before_or_equal:now',
            'equipment_type'           => 'required|string|max:150',
            'nature_of_fault'          => 'required|string',
            'priority_level'           => 'required|in:Low,Medium,High,Urgent',
            'contact_person'           => 'nullable|string|max:100',
            'phone_number'             => 'nullable|string|max:20',
            'brand_make_model'         => 'nullable|string|max:150',
            'serial_number_asset_tag'  => 'required|string|max:100',
            'depot_name'               => 'nullable|string|max:100',
            'department'               => 'nullable|string|max:100',
            'physical_condition_on_receipt' => 'nullable|string|max:200',
            'accessories_received'     => 'nullable|string',
            'technician_assigned'      => 'nullable|exists:users,id',
            'due_date'                 => 'nullable|date|after_or_equal:today',
            'cross_ref_form208'        => 'nullable|string|max:100',
        ]);

        $user = auth()->user();
        // A technician creating their own job is implicitly the one it's for.
        $technicianAssigned = $user->isTechnician() ? $user->id : $request->technician_assigned;

        $job = WorkshopEquipmentRegister::create([
            ...$request->only([
                'date_time_received', 'equipment_type', 'nature_of_fault', 'priority_level',
                'contact_person', 'phone_number', 'brand_make_model', 'serial_number_asset_tag',
                'depot_name', 'department', 'physical_condition_on_receipt', 'accessories_received',
                'due_date', 'cross_ref_form208',
            ]),
            'technician_assigned' => $technicianAssigned,
            'entry_job_number'    => WorkshopEquipmentRegister::generateJobNumber(),
            // Every job starts Pending — only the technician handling it moves it to In Progress.
            'status'              => 'Pending',
            'created_by'          => auth()->id(),
        ]);

        if ($job->serial_number_asset_tag) {
            EquipmentHistory::record(
                'serial_number', $job->serial_number_asset_tag,
                'Workshop Received', "Received at workshop. Job: {$job->entry_job_number}. Fault: {$job->nature_of_fault}",
                'workshop_equipment_registers', $job->id
            );
        }

        if ($job->technician_assigned) {
            $this->notifyTechnicianAssignment($job, $job->technician);
        }

        AuditTrail::log('create', 'Workshop', "Created workshop job: {$job->entry_job_number}", 'success', $job->id);

        return redirect()->route('workshop.show', $job)
            ->with('success', "Job {$job->entry_job_number} created successfully.");
    }

    public function show(WorkshopEquipmentRegister $workshop)
    {
        $workshop->load('technician', 'creator', 'comments.commenter');
        $technicians = User::where('role', 'technician')->get();
        return view('workshop.show', compact('workshop', 'technicians'));
    }

    public function edit(WorkshopEquipmentRegister $workshop)
    {
        $technicians = User::where('role', 'technician')->get();
        return view('workshop.edit', compact('workshop', 'technicians'));
    }

    public function update(Request $request, WorkshopEquipmentRegister $workshop)
    {
        $request->validate([
            'repair_action_taken'      => 'nullable|string|required_if:status,Completed,Collected',
            'date_repair_completed'    => [
                'nullable', 'date', 'before_or_equal:today',
                'after_or_equal:'.$workshop->date_time_received->format('Y-m-d'),
            ],
            'status'                   => 'required|in:Pending,In Progress,Completed,Collected',
            'technician_assigned'      => 'nullable|exists:users,id',
            'time_taken_value'         => 'nullable|integer|min:1',
            'time_taken_unit'          => 'nullable|in:Minutes,Hours,Days,Weeks,Months',
            'cross_ref_form208_outgoing' => 'nullable|string|max:100',
            'date_collected'           => [
                'nullable', 'date', 'before_or_equal:today',
                'after_or_equal:'.$workshop->date_time_received->format('Y-m-d'),
            ],
            'collector_name'           => 'nullable|string|max:100',
            'collector_signature'      => 'nullable|string',
            'remarks_comments'         => 'nullable|string',
        ]);

        $old = $workshop->status;
        $oldTechnicianAssigned = $workshop->technician_assigned;

        // Status is set by whoever is handling the job, not derived from assignment.
        $status = $request->status;

        // Auto-promote to Collected when collection details are provided
        if ($request->filled('date_collected') && $request->filled('collector_name')) {
            $status = 'Collected';
        }

        $workshop->update([
            ...$request->only([
                'repair_action_taken', 'date_repair_completed',
                'technician_assigned', 'time_taken_value', 'time_taken_unit',
                'cross_ref_form208_outgoing', 'date_collected', 'collector_name',
                'collector_signature', 'remarks_comments',
            ]),
            'status'     => $status,
            'updated_by' => auth()->id(),
        ]);

        if ($old !== $workshop->status && $workshop->serial_number_asset_tag) {
            EquipmentHistory::record(
                'serial_number', $workshop->serial_number_asset_tag,
                'Status Changed', "Workshop job {$workshop->entry_job_number} status changed from {$old} to {$workshop->status}",
                'workshop_equipment_registers', $workshop->id
            );
        }

        if ($workshop->technician_assigned && $workshop->technician_assigned != $oldTechnicianAssigned) {
            $this->notifyTechnicianAssignment($workshop, $workshop->technician);
        }

        AuditTrail::log('update', 'Workshop', "Updated workshop job: {$workshop->entry_job_number}", 'success', $workshop->id);

        return redirect()->route('workshop.show', $workshop)->with('success', 'Job updated successfully.');
    }

    public function addComment(Request $request, WorkshopEquipmentRegister $workshop)
    {
        $user = auth()->user();
        if ($user->isTechnician()) abort(403);

        $request->validate(['comment' => 'required|string|max:1000']);

        TaskComment::create([
            'workshop_equipment_id' => $workshop->id,
            'commented_by'          => $user->id,
            'comment'               => $request->comment,
            'is_overdue_comment'    => $workshop->isOverdue(),
        ]);

        AuditTrail::log('comment', 'Workshop', "Comment added to job: {$workshop->entry_job_number}", 'info', $workshop->id);

        return back()->with('success', 'Comment added.');
    }

    public function assignSelf(WorkshopEquipmentRegister $workshop)
    {
        $user = auth()->user();
        if (!$user->isAdminOrHod()) abort(403);

        $workshop->update([
            'technician_assigned' => $user->id,
            'updated_by' => $user->id,
        ]);

        $this->notifyTechnicianAssignment($workshop, $user);

        AuditTrail::log('assign_self', 'Workshop', "{$user->username} self-assigned job: {$workshop->entry_job_number}", 'info', $workshop->id);

        return back()->with('success', 'Task assigned to yourself.');
    }

    public function importForm()
    {
        return view('workshop.import');
    }

    public function import(Request $request, EquipmentSearchService $search)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls,csv']);

        $sheet = IOFactory::load($request->file('file')->getRealPath())->getActiveSheet();
        $rows  = $sheet->toArray(null, true, true, false);

        if (empty($rows)) {
            return back()->withErrors(['file' => 'The uploaded file has no rows.']);
        }

        $headerMap = $this->buildImportHeaderMap(array_shift($rows));

        $imported  = [];
        $duplicates = [];
        $invalid   = [];

        foreach ($rows as $i => $row) {
            $rowNumber = $i + 2; // +1 for 0-index, +1 for the header row already shifted off
            $data = $this->mapImportRow($row, $headerMap);

            // Fully blank spacer row — nothing to report, nothing to do.
            if ($data['type'] === '' && $data['serial_no'] === '') {
                continue;
            }

            $serial = trim($data['serial_no']);
            if ($serial === '') {
                $invalid[] = ['row' => $rowNumber, 'reason' => 'Missing serial number'];
                continue;
            }

            $existing = $search->existsExact($serial);
            if ($existing) {
                $duplicates[] = [
                    'row' => $rowNumber,
                    'serial' => $serial,
                    'label' => $existing['label'],
                    'meta' => $existing['meta'],
                    'identifier_type' => $existing['identifier_type'],
                ];
                continue;
            }

            $remarks = $data['technician'] !== ''
                ? "Imported technician note: {$data['technician']}"
                : null;

            $job = WorkshopEquipmentRegister::create([
                'entry_job_number'       => WorkshopEquipmentRegister::generateJobNumber(),
                'cross_ref_form208'      => $data['form208'] ?: null,
                'date_time_received'     => $this->parseImportDate($data['date_received']) ?? now(),
                'depot_name'             => $data['from'] ?: null,
                'contact_person'         => $data['received_by'] ?: null,
                'equipment_type'         => $data['type'] ?: 'Unspecified',
                'brand_make_model'       => $data['model'] ?: null,
                'serial_number_asset_tag' => $serial,
                'nature_of_fault'        => $data['fault'] ?: 'Not specified',
                'technician_assigned'    => null,
                'status'                 => $this->normalizeImportStatus($data['status']),
                'collector_name'         => $data['collected_by'] ?: null,
                'date_collected'         => $this->parseImportDate($data['departure_date']),
                'remarks_comments'       => $remarks,
                'priority_level'         => 'Medium',
                'created_by'             => auth()->id(),
            ]);

            EquipmentHistory::record(
                'serial_number', $serial,
                'Workshop Received', "Received at workshop (bulk import). Job: {$job->entry_job_number}.",
                'workshop_equipment_registers', $job->id
            );

            $imported[] = ['row' => $rowNumber, 'job' => $job];
        }

        AuditTrail::log(
            'import', 'Workshop',
            'Bulk imported ' . count($imported) . ' job(s), ' . count($duplicates) . ' duplicate(s) skipped, '
                . count($invalid) . ' invalid row(s), from ' . $request->file('file')->getClientOriginalName(),
            'success'
        );

        return view('workshop.import-results', compact('imported', 'duplicates', 'invalid'));
    }

    /** Build column-index => canonical-key map from the header row, trimmed/uppercased so column order doesn't matter. */
    private function buildImportHeaderMap(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $index => $header) {
            $key = self::IMPORT_HEADER_MAP[mb_strtoupper(trim((string) $header))] ?? null;
            if ($key) {
                $map[$key] = $index;
            }
        }
        return $map;
    }

    /** Extract a canonical-keyed, string-trimmed row from a raw spreadsheet row using the header map. */
    private function mapImportRow(array $row, array $headerMap): array
    {
        $data = [];
        foreach (self::IMPORT_HEADER_MAP as $key) {
            $index = $headerMap[$key] ?? null;
            $value = $index !== null ? ($row[$index] ?? '') : '';
            $data[$key] = trim((string) $value);
        }
        return $data;
    }

    private function normalizeImportStatus(string $status): string
    {
        $valid = ['Pending', 'In Progress', 'Completed', 'Collected'];
        foreach ($valid as $option) {
            if (mb_strtolower($status) === mb_strtolower($option)) {
                return $option;
            }
        }
        return 'Pending';
    }

    private function parseImportDate(string $value): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value));
            } catch (\Throwable) {
                return null;
            }
        }

        foreach (['d/m/Y', 'd/m/y', 'Y-m-d', 'd-m-Y'] as $format) {
            try {
                // These formats carry no time component — Carbon would otherwise default
                // the time-of-day to "now" rather than midnight.
                return Carbon::createFromFormat($format, $value)->startOfDay();
            } catch (\Throwable) {
                continue;
            }
        }

        return null;
    }

    /**
     * Email the assigned technician about a workshop job — including self-assignment.
     * Only sends to accounts on the organization's Entra domain (same allow-list already
     * used for Microsoft SSO), per the requirement to restrict this to gmbdura.co.zw staff.
     */
    private function notifyTechnicianAssignment(WorkshopEquipmentRegister $job, ?User $technician): void
    {
        if (!$technician || !$technician->email) {
            return;
        }

        $allowedDomain = config('services.microsoft.allowed_domain');
        $email = mb_strtolower(trim($technician->email));

        if (!$allowedDomain || !str_ends_with($email, '@' . mb_strtolower($allowedDomain))) {
            return;
        }

        try {
            Mail::raw(
                "Hello {$technician->firstname},\n\n"
                . "You have been assigned to workshop job {$job->entry_job_number}.\n\n"
                . "Equipment: {$job->equipment_type}" . ($job->brand_make_model ? " ({$job->brand_make_model})" : '') . "\n"
                . "Serial/Asset Tag: " . ($job->serial_number_asset_tag ?: '—') . "\n"
                . "Nature of Fault: {$job->nature_of_fault}\n"
                . "Priority: {$job->priority_level}\n"
                . ($job->due_date ? "Due Date: {$job->due_date->format('d M Y H:i')}\n" : '')
                . "\nPlease log in to the ICT Register System to view full details.",
                function ($message) use ($technician, $job) {
                    $message->to($technician->email)->subject("Workshop Job Assigned: {$job->entry_job_number}");
                }
            );
        } catch (\Exception $e) {
            // Silently fail — matches this app's existing mail-sending convention elsewhere.
        }
    }
}
