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
    .badge { display:inline-block; padding:1px 5px; border-radius:3px; font-size:7px; font-weight:bold; }
    .badge-disposed  { background:#fee2e2; color:#b91c1c; }
    .badge-approved  { background:#dcfce7; color:#15803d; }
    .badge-rejected  { background:#f1f5f9; color:#475569; }
    .badge-requested, .badge-pending { background:#fef9c3; color:#ca8a04; }
    .footer { margin-top:10px; font-size:7px; color:#94a3b8; text-align:right; border-top:1px solid #e2e8f0; padding-top:5px; }
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
    @if(!empty($filters['status'])) <span>Status: <strong>{{ $filters['status'] }}</strong></span> @endif
    @if(!empty($filters['search'])) <span>Search: <strong>{{ $filters['search'] }}</strong></span> @endif
</div>
@endif

<table>
    <thead>
        <tr>
            <th>Disposal Ref.</th>
            <th>Asset Description</th>
            <th>Model / Brand</th>
            <th>Asset Tag / Serial</th>
            <th>Dept / User</th>
            <th>Condition</th>
            <th>Reason</th>
            <th>Method</th>
            <th>Status</th>
            <th>Requested By</th>
            <th>Approved By</th>
            <th>Date Disposed</th>
        </tr>
    </thead>
    <tbody>
    @forelse($data as $r)
    @php
        $st = strtolower(str_replace(' ', '', $r->status));
    @endphp
    <tr>
        <td><strong>{{ $r->disposal_ref_no ?? '—' }}</strong></td>
        <td>{{ $r->asset_description }}</td>
        <td>{{ $r->model_brand ?? '—' }}</td>
        <td>{{ $r->asset_tag_serial_no ?? '—' }}</td>
        <td>{{ $r->department_user ?? '—' }}</td>
        <td>{{ $r->condition_at_disposal ?? '—' }}</td>
        <td style="max-width:120px;">{{ $r->reason_for_disposal }}</td>
        <td>{{ $r->disposal_method ?? '—' }}</td>
        <td><span class="badge badge-{{ $st }}">{{ $r->status }}</span></td>
        <td>{{ trim(($r->requester?->firstname ?? '') . ' ' . ($r->requester?->lastname ?? '')) ?: '—' }}</td>
        <td>{{ trim(($r->approver?->firstname ?? '') . ' ' . ($r->approver?->lastname ?? '')) ?: '—' }}</td>
        <td>{{ $r->date_disposed?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="12" style="text-align:center;padding:10px;color:#94a3b8;">No records found.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="footer">
    ICT Equipment Disposal Register &nbsp;&bull;&nbsp; Developed by GMB ICT Department
</div>
</body>
</html>
