<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\EquipmentHistory;
use App\Models\EquipmentReceivingRegister;
use App\Models\TaskComment;
use App\Models\User;
use App\Models\WorkshopEquipmentRegister;
use App\Models\WorkshopJobDevice;
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

        if ($request->filled('date_from')) {
            $query->whereDate('date_time_received', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('date_time_received', '<=', $request->date_to);
        }

        if ($request->filled('depot')) {
            $query->where('depot_name', $request->depot);
        }

        if ($request->filled('technician')) {
            $query->where('technician_assigned', $request->technician);
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

        $sortable = ['job_number' => 'entry_job_number', 'date_received' => 'date_time_received'];
        $sort = $request->query('sort');
        $direction = $request->query('direction') === 'desc' ? 'desc' : 'asc';

        if ($sort && isset($sortable[$sort])) {
            $query->orderBy($sortable[$sort], $direction);
        } else {
            $query->oldest();
        }

        $jobs = $query->paginate(15);
        $technicians = User::where('role', 'technician')->get();
        $depots = WorkshopEquipmentRegister::whereNotNull('depot_name')
            ->where('depot_name', '!=', '')
            ->distinct()
            ->orderBy('depot_name')
            ->pluck('depot_name');

        return view('workshop.index', compact('jobs', 'overdue', 'technicians', 'depots', 'statusCounts'));
    }

    public function lookupSerial(Request $request)
    {
        $serial = trim($request->query('serial', ''));
        if (strlen($serial) < 3) {
            return response()->json(['found' => false]);
        }

        // Always check for a prior workshop job on this serial — regardless of whether it's
        // also in the Helpdesk Registry — so a technician entering a new job is warned if
        // someone already logged this exact device, and told who handled it. Every device
        // on a job (including the first) lives in workshop_job_devices, so this one lookup
        // covers device 1 and any additional device alike, with that device's own status.
        $priorDevice = WorkshopJobDevice::where('serial_number_asset_tag', $serial)
            ->with('job.technician')
            ->latest()
            ->first();
        $priorJob = $priorDevice?->job;

        $priorWorkshopJob = $priorJob ? [
            'job_number'      => $priorJob->entry_job_number,
            'technician'      => $priorJob->technician
                ? trim($priorJob->technician->firstname . ' ' . $priorJob->technician->lastname)
                : 'Unassigned',
            'status'          => $priorDevice->status,
            'date_received'   => $priorJob->date_time_received?->format('d M Y'),
            'nature_of_fault' => $priorJob->nature_of_fault,
        ] : null;

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
                'found'              => true,
                'source'             => 'registry',
                'equipment_type'     => $record->item_description,
                'brand_model'        => $record->brand_model ?? '',
                'assigned_to'        => $latestRedist ? $latestRedist->recipient_name : null,
                'depot'              => $latestRedist ? $latestRedist->depot_department : null,
                'prior_workshop_job' => $priorWorkshopJob,
            ]);
        }

        // Not part of the Helpdesk Registry — fall back to the prior Workshop job itself
        // for auto-fill, so equipment logged directly at the workshop (never received via
        // Helpdesk intake) is still recognised on a repeat visit.
        if (!$priorJob) {
            return response()->json(['found' => false]);
        }

        return response()->json([
            'found'              => true,
            'source'             => 'workshop',
            'equipment_type'     => $priorDevice->equipment_type,
            'brand_model'        => $priorDevice->brand_make_model ?? '',
            'assigned_to'        => $priorJob->contact_person,
            'depot'              => $priorJob->department,
            'last_job'           => $priorJob->entry_job_number,
            'prior_workshop_job' => $priorWorkshopJob,
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
            'contact_person'           => 'required|digits:4',
            'phone_number'             => 'required|string|max:20',
            'brand_make_model'         => 'required|string|max:150',
            'serial_number_asset_tag'  => 'required|string|max:100',
            'depot_name'               => 'required|string|max:100',
            'final_depot'              => 'nullable|string|max:100',
            'department'               => 'required|string|max:100',
            'physical_condition_on_receipt' => 'required|string|max:200',
            'accessories_received'     => 'required|string',
            'technician_assigned'      => 'nullable|exists:users,id',
            'due_date'                 => 'nullable|date|after_or_equal:today',
            'cross_ref_form208'        => 'required|string|max:100',
            'additional_devices'                          => 'nullable|array',
            'additional_devices.*.equipment_type'         => 'required|string|max:150',
            'additional_devices.*.brand_make_model'       => 'required|string|max:150',
            'additional_devices.*.serial_number_asset_tag' => 'required|string|max:100',
            'additional_devices.*.physical_condition_on_receipt' => 'required|string|max:200',
        ]);

        $user = auth()->user();
        // A technician creating their own job is implicitly the one it's for.
        $technicianAssigned = $user->isTechnician() ? $user->id : $request->technician_assigned;

        $job = WorkshopEquipmentRegister::create([
            ...$request->only([
                'date_time_received', 'equipment_type', 'nature_of_fault', 'priority_level',
                'contact_person', 'phone_number', 'brand_make_model', 'serial_number_asset_tag',
                'depot_name', 'final_depot', 'department', 'physical_condition_on_receipt', 'accessories_received',
                'due_date', 'cross_ref_form208',
            ]),
            'technician_assigned' => $technicianAssigned,
            'entry_job_number'    => WorkshopEquipmentRegister::generateJobNumber(),
            // Every job starts Pending — only the technician handling it moves it to In Progress.
            'status'              => 'Pending',
            'created_by'          => auth()->id(),
        ]);

        // Device 1 mirrors the job's own equipment columns, so every device on the
        // job — including the first — can be found via the devices table uniformly.
        WorkshopJobDevice::create([
            'workshop_equipment_register_id' => $job->id,
            'equipment_type'                 => $job->equipment_type,
            'brand_make_model'                => $job->brand_make_model,
            'serial_number_asset_tag'         => $job->serial_number_asset_tag,
            'physical_condition_on_receipt'   => $job->physical_condition_on_receipt,
        ]);

        if ($job->serial_number_asset_tag) {
            EquipmentHistory::record(
                'serial_number', $job->serial_number_asset_tag,
                'Workshop Received', "Received at workshop. Job: {$job->entry_job_number}. Fault: {$job->nature_of_fault}",
                'workshop_equipment_registers', $job->id
            );
        }

        foreach ($request->input('additional_devices', []) as $device) {
            $extra = WorkshopJobDevice::create([
                'workshop_equipment_register_id' => $job->id,
                'equipment_type'                 => $device['equipment_type'],
                'brand_make_model'                => $device['brand_make_model'],
                'serial_number_asset_tag'         => $device['serial_number_asset_tag'],
                'physical_condition_on_receipt'   => $device['physical_condition_on_receipt'],
            ]);

            EquipmentHistory::record(
                'serial_number', $extra->serial_number_asset_tag,
                'Workshop Received', "Received at workshop (additional device on job {$job->entry_job_number}). Fault: {$job->nature_of_fault}",
                'workshop_job_devices', $extra->id
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
        $workshop->load('technician', 'creator', 'comments.commenter', 'devices');
        $technicians = User::where('role', 'technician')->get();
        return view('workshop.show', compact('workshop', 'technicians'));
    }

    public function edit(WorkshopEquipmentRegister $workshop)
    {
        $workshop->load('devices');
        $technicians = User::where('role', 'technician')->get();
        return view('workshop.edit', compact('workshop', 'technicians'));
    }

    public function update(Request $request, WorkshopEquipmentRegister $workshop)
    {
        $workshop->load('devices');

        $request->validate([
            'technician_assigned'        => 'nullable|exists:users,id',
            'time_taken_value'           => 'nullable|integer|min:1',
            'time_taken_unit'            => 'nullable|in:Minutes,Hours,Days,Weeks,Months',
            'cross_ref_form208_outgoing' => 'nullable|string|max:100',
            'remarks_comments'           => 'nullable|string',
            'final_depot'                => 'nullable|string|max:100',
            'devices'                                => 'required|array|min:1',
            'devices.*.status'                       => 'required|in:Pending,In Progress,Completed,Collected',
            'devices.*.repair_action_taken'          => 'nullable|string|required_if:devices.*.status,Completed,Collected',
            'devices.*.date_repair_completed'        => [
                'nullable', 'date', 'before_or_equal:today',
                'after_or_equal:'.$workshop->date_time_received->format('Y-m-d'),
            ],
            'devices.*.date_collected'               => [
                'nullable', 'date', 'before_or_equal:today',
                'after_or_equal:'.$workshop->date_time_received->format('Y-m-d'),
            ],
            'devices.*.collector_name'               => 'nullable|string|max:100',
            'devices.*.collector_signature'          => 'nullable|string',
        ]);

        $oldTechnicianAssigned = $workshop->technician_assigned;

        // Each device's status is set independently — e.g. a CPU can be marked
        // Collected while a mouse and monitor on the same job remain In Progress.
        foreach ($request->input('devices', []) as $deviceId => $data) {
            $device = $workshop->devices->firstWhere('id', (int) $deviceId);
            if (!$device) continue;

            $deviceOldStatus = $device->status;
            $status = $data['status'] ?? $device->status;

            // Auto-promote to Collected when collection details are provided for this device
            if (!empty($data['date_collected']) && !empty($data['collector_name'])) {
                $status = 'Collected';
            }

            $device->update([
                'status'                 => $status,
                'repair_action_taken'    => $data['repair_action_taken'] ?? null,
                'date_repair_completed'  => $data['date_repair_completed'] ?? null,
                'date_collected'         => $data['date_collected'] ?? null,
                'collector_name'         => $data['collector_name'] ?? null,
                'collector_signature'    => $data['collector_signature'] ?? null,
            ]);

            if ($deviceOldStatus !== $device->status && $device->serial_number_asset_tag) {
                EquipmentHistory::record(
                    'serial_number', $device->serial_number_asset_tag,
                    'Status Changed', "Workshop job {$workshop->entry_job_number} — {$device->equipment_type} status changed from {$deviceOldStatus} to {$device->status}",
                    'workshop_job_devices', $device->id
                );
            }
        }

        $workshop->load('devices');
        $primaryDevice = $workshop->devices->first();

        $workshop->update([
            ...$request->only([
                'technician_assigned', 'time_taken_value', 'time_taken_unit',
                'cross_ref_form208_outgoing', 'remarks_comments', 'final_depot',
            ]),
            'status' => $workshop->computeAggregateStatus(),
            // Mirror the primary (first) device so job-level consumers that only know
            // about these columns — CSV export, dashboard, PDF — keep showing something
            // meaningful; the devices table above is the source of truth going forward.
            'repair_action_taken'   => $primaryDevice?->repair_action_taken,
            'date_repair_completed' => $primaryDevice?->date_repair_completed,
            'date_collected'        => $primaryDevice?->date_collected,
            'collector_name'        => $primaryDevice?->collector_name,
            'collector_signature'   => $primaryDevice?->collector_signature,
            'updated_by'            => auth()->id(),
        ]);

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
