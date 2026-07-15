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
    .badge-workshop { background:#dbeafe; color:#1d4ed8; }
    .badge-registry { background:#f3e8ff; color:#7c3aed; }
    .footer { margin-top: 10px; font-size: 7px; color: #94a3b8; text-align: right; border-top: 1px solid #e2e8f0; padding-top: 5px; }
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
    @if(!empty($filters['type']) && $filters['type'] !== 'all') <span>View: <strong>{{ ucfirst($filters['type']) }}</strong></span> @endif
    @if(!empty($filters['search'])) <span>Search: <strong>{{ $filters['search'] }}</strong></span> @endif
</div>
@endif

<table>
    <thead>
        <tr>
            <th>Source</th>
            <th>Ref No.</th>
            <th>Item / Equipment</th>
            <th>Brand / Model</th>
            <th>Serial / Asset Tag</th>
            <th>Status</th>
            <th>Available</th>
            <th>Technician / Supplier</th>
            <th>Date</th>
        </tr>
    </thead>
    <tbody>
    @forelse($data as $row)
    <tr>
        <td><span class="badge badge-{{ strtolower($row->source) }}">{{ $row->source }}</span></td>
        <td><strong>{{ $row->ref }}</strong></td>
        <td>{{ $row->item }}</td>
        <td>{{ $row->brand ?? '—' }}</td>
        <td>{{ $row->serial ?? '—' }}</td>
        <td>{{ $row->status }}</td>
        <td>{{ $row->available ?? '—' }}</td>
        <td>{{ $row->handler ?? '—' }}</td>
        <td>{{ $row->date?->format('d/m/Y') ?? '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="9" style="text-align:center;padding:10px;color:#94a3b8;">No records found.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="footer">
    ICT Equipment — Workshop &amp; Registry &nbsp;&bull;&nbsp; Developed by GMB ICT Department
</div>
</body>
</html>
