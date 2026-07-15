<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family: DejaVu Sans, sans-serif; font-size: 8px; color: #1e293b; }
    .header { background: #1e3a5f; color: #fff; padding: 10px 14px; margin-bottom: 10px; }
    .header h1 { font-size: 13px; font-weight: bold; }
    .header p  { font-size: 8px; margin-top: 3px; color: #bfdbfe; }
    .filters { background: #f1f5f9; border: 1px solid #e2e8f0; padding: 6px 10px; font-size: 7.5px; margin-bottom: 8px; border-radius: 4px; }
    .filters span { margin-right: 14px; }
    table { width: 100%; border-collapse: collapse; font-size: 7.5px; }
    thead tr { background: #1e3a5f; color: #fff; }
    thead th { padding: 5px 6px; text-align: left; border: 1px solid #1e4976; white-space: nowrap; }
    tbody tr:nth-child(even) { background: #f8fafc; }
    tbody td { padding: 4px 6px; border: 1px solid #e2e8f0; vertical-align: top; }
    .badge { display: inline-block; padding: 1px 5px; border-radius: 3px; font-size: 7px; font-weight: bold; }
    .badge-urgent  { background:#fee2e2; color:#b91c1c; }
    .badge-high    { background:#fef3c7; color:#92400e; }
    .badge-medium  { background:#dbeafe; color:#1d4ed8; }
    .badge-low     { background:#f1f5f9; color:#475569; }
    .badge-collected, .badge-completed { background:#dcfce7; color:#15803d; }
    .badge-progress { background:#dbeafe; color:#1d4ed8; }
    .badge-pending  { background:#fef9c3; color:#ca8a04; }
    .footer { margin-top: 10px; font-size: 7px; color: #94a3b8; text-align: right; border-top: 1px solid #e2e8f0; padding-top: 5px; }
    .summary { margin-bottom: 8px; font-size: 7.5px; color: #475569; }
    .summary strong { color: #1e293b; }
</style>
</head>
<body>

<div class="header">
    <h1>{{ $title }}</h1>
    <p>Generated: {{ now()->format('d M Y H:i') }} &nbsp;&bull;&nbsp; Total records: {{ $data->count() }}</p>
</div>

@if(array_filter($filters))
<div class="filters">
    <strong>Filters applied:</strong>
    @if(!empty($filters['status']))   <span>Status: <strong>{{ $filters['status'] }}</strong></span> @endif
    @if(!empty($filters['priority'])) <span>Priority: <strong>{{ $filters['priority'] }}</strong></span> @endif
    @if(!empty($filters['search']))   <span>Search: <strong>{{ $filters['search'] }}</strong></span> @endif
</div>
@endif

<table>
    <thead>
        <tr>
            <th>Job No.</th>
            <th>Date Received</th>
            <th>Equipment Type</th>
            <th>Brand / Model</th>
            <th>Serial / Asset Tag</th>
            <th>Department</th>
            <th>Contact</th>
            <th>Nature of Fault</th>
            <th>Priority</th>
            <th>Technician</th>
            <th>Status</th>
            <th>Due Date</th>
        </tr>
    </thead>
    <tbody>
    @forelse($data as $job)
    <tr>
        <td><strong>{{ $job->entry_job_number }}</strong></td>
        <td>{{ $job->date_time_received?->format('d/m/Y H:i') }}</td>
        <td>{{ $job->equipment_type }}</td>
        <td>{{ $job->brand_make_model ?? '—' }}</td>
        <td>{{ $job->serial_number_asset_tag ?? '—' }}</td>
        <td>{{ $job->department ?? '—' }}</td>
        <td>{{ $job->contact_person ?? '—' }}</td>
        <td>{{ $job->nature_of_fault }}</td>
        <td>
            @php $pl = strtolower($job->priority_level); @endphp
            <span class="badge badge-{{ $pl }}">{{ $job->priority_level }}</span>
        </td>
        <td>{{ trim(($job->technician?->firstname ?? '') . ' ' . ($job->technician?->lastname ?? '')) ?: '—' }}</td>
        <td>
            @php $st = strtolower(str_replace(' ', '', $job->status)); @endphp
            <span class="badge badge-{{ $st }}">{{ $job->status }}</span>
        </td>
        <td>{{ $job->due_date?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="12" style="text-align:center;padding:10px;color:#94a3b8;">No records found.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="footer">
    ICT Workshop Equipment Register &nbsp;&bull;&nbsp; Developed by GMB ICT Department
</div>
</body>
</html>
