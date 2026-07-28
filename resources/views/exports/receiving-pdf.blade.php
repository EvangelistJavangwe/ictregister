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
    .badge-full    { background:#dcfce7; color:#15803d; }
    .badge-partial { background:#fef9c3; color:#ca8a04; }
    .badge-pending { background:#f1f5f9; color:#475569; }
    .num { text-align:center; font-weight:bold; }
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
    @if(!empty($filters['status'])) <span>Stock Status: <strong>{{ $filters['status'] }}</strong></span> @endif
    @if(!empty($filters['search'])) <span>Search: <strong>{{ $filters['search'] }}</strong></span> @endif
</div>
@endif

<table>
    <thead>
        <tr>
            <th>Ref No.</th>
            <th>Date</th>
            <th>Supplier</th>
            <th>Item Description</th>
            <th>Brand / Model</th>
            <th class="num">Cost (USD)</th>
            <th class="num">Rcvd</th>
            <th class="num">Insp.</th>
            <th class="num">Redist.</th>
            <th class="num">Avail.</th>
            <th>Stock Status</th>
            <th>Serial Numbers</th>
        </tr>
    </thead>
    <tbody>
    @forelse($data as $r)
    @php
        $inspected     = (int)($r->inspections_sum_qty_inspected ?? 0);
        $redistributed = (int)($r->redistributions_sum_qty_redistributed ?? 0);
        $available     = max(0, $r->qty_received - $redistributed);
        if ($inspected === 0) { $status = 'Pending Inspection'; $badgeClass = 'badge-pending'; }
        elseif ($inspected < $r->qty_received) { $status = 'Partially Inspected'; $badgeClass = 'badge-partial'; }
        else { $status = 'Fully Inspected'; $badgeClass = 'badge-full'; }
    @endphp
    <tr>
        <td><strong>{{ $r->cross_ref_no }}</strong></td>
        <td>{{ $r->date_received?->format('d/m/Y') }}</td>
        <td>{{ $r->supplier }}</td>
        <td>{{ $r->item_description }}</td>
        <td>{{ $r->brand_model ?? '—' }}</td>
        <td class="num">{{ $r->cost_price !== null ? number_format($r->cost_price, 2) : '—' }}</td>
        <td class="num">{{ $r->qty_received }}</td>
        <td class="num">{{ $inspected }}</td>
        <td class="num">{{ $redistributed }}</td>
        <td class="num">{{ $available }}</td>
        <td><span class="badge {{ $badgeClass }}">{{ $status }}</span></td>
        <td style="max-width:180px;word-break:break-all;">{{ implode(', ', $r->serial_numbers ?? []) ?: '—' }}</td>
    </tr>
    @empty
    <tr><td colspan="11" style="text-align:center;padding:10px;color:#94a3b8;">No records found.</td></tr>
    @endforelse
    </tbody>
</table>

<div class="footer">
    Helpdesk Administrator &nbsp;&bull;&nbsp; Developed by GMB ICT Department
</div>
</body>
</html>
