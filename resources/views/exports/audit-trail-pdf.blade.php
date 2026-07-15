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
    .badge-success { background:#dcfce7; color:#15803d; }
    .badge-failed  { background:#fee2e2; color:#b91c1c; }
    .badge-warning { background:#fef9c3; color:#ca8a04; }
    .badge-info    { background:#dbeafe; color:#1d4ed8; }
    .mono { font-family: DejaVu Sans Mono, monospace; font-size:7px; }
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
    @if(!empty($filters['username']))     <span>Username: <strong>{{ $filters['username'] }}</strong></span> @endif
    @if(!empty($filters['module']))       <span>Module: <strong>{{ $filters['module'] }}</strong></span> @endif
    @if(!empty($filters['badge_status'])) <span>Status: <strong>{{ ucfirst($filters['badge_status']) }}</strong></span> @endif
    @if(!empty($filters['date_from']))    <span>From: <strong>{{ $filters['date_from'] }}</strong></span> @endif
    @if(!empty($filters['date_to']))      <span>To: <strong>{{ $filters['date_to'] }}</strong></span> @endif
</div>
@endif

<table>
    <thead>
        <tr>
            <th>Date / Time</th>
            <th>Username</th>
            <th>Action</th>
            <th>Module</th>
            <th>Description</th>
            <th>IP Address</th>
            <th>MAC Address</th>
            <th>Computer Name</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
    @forelse($data as $trail)
    <tr>
        <td class="mono" style="white-space:nowrap;">{{ $trail->created_at?->format('d/m/Y H:i:s') }}</td>
        <td><strong>{{ $trail->username }}</strong></td>
        <td>{{ $trail->action }}</td>
        <td>{{ $trail->module }}</td>
        <td style="max-width:180px;">{{ $trail->description }}</td>
        <td class="mono">{{ $trail->ip_address ?? '—' }}</td>
        <td class="mono">{{ $trail->mac_address ?? '—' }}</td>
        <td>{{ $trail->computer_name ?? '—' }}</td>
        <td><span class="badge badge-{{ $trail->badge_status }}">{{ ucfirst($trail->badge_status) }}</span></td>
    </tr>
    @empty
    <tr><td colspan="9" style="text-align:center;padding:10px;color:#94a3b8;">No records found.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="footer">
    System Audit Trail &nbsp;&bull;&nbsp; Developed by GMB ICT Department
</div>
</body>
</html>
