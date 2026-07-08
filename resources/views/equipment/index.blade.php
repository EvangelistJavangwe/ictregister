@extends('layouts.app')
@section('title', 'All Equipment')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-boxes"></i> All Equipment</h2>
</div>

<form method="GET" class="filter-bar" id="equipment-filter-form">
    <input type="hidden" name="type" value="{{ $type }}">
    <div class="form-group">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Ref no, item, serial...">
    </div>
    <div class="form-group" style="align-self:flex-end;display:flex;gap:6px;flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        <a href="{{ route('equipment.index', ['type' => $type]) }}" class="btn btn-secondary">Clear</a>
        <div style="position:relative;display:inline-block;">
            <button type="button" class="btn btn-success" onclick="toggleExportMenu('equipment-export-menu')">
                <i class="fas fa-download"></i> Export
            </button>
            <div id="equipment-export-menu" style="display:none;position:absolute;top:calc(100% + 4px);left:0;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 6px 16px rgba(0,0,0,.1);z-index:200;min-width:150px;overflow:hidden;">
                @foreach(['csv'=>'CSV Spreadsheet','excel'=>'Excel (.xlsx)','word'=>'Word Document','pdf'=>'PDF Report'] as $fmt => $label)
                <a href="javascript:void(0)" onclick="doExport('{{ route('equipment.export') }}', '{{ $fmt }}', 'equipment-filter-form')"
                   style="display:flex;align-items:center;gap:8px;padding:9px 14px;font-size:.82rem;color:#1e293b;text-decoration:none;transition:background .12s;"
                   onmouseover="this.style.background='#f1f5f9'" onmouseout="this.style.background=''">
                    <i class="fas fa-{{ $fmt==='csv'?'file-csv':($fmt==='excel'?'file-excel':($fmt==='word'?'file-word':'file-pdf')) }}"
                       style="width:16px;color:{{ $fmt==='csv'?'#16a34a':($fmt==='excel'?'#15803d':($fmt==='word'?'#1d4ed8':'#dc2626')) }};"></i>
                    {{ $label }}
                </a>
                @endforeach
            </div>
        </div>
    </div>
</form>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Source</th><th>Ref No.</th><th>Item / Equipment</th><th>Serial / Asset Tag</th>
                    <th>Status</th><th style="text-align:center;">Available</th><th>Technician / Supplier</th><th>Date</th><th></th>
                </tr>
            </thead>
            <tbody>
            @forelse($rows as $row)
            <tr>
                <td>
                    @if($row->source === 'Workshop')
                        <span class="badge badge-info"><i class="fas fa-tools" style="font-size:.65rem;margin-right:3px;"></i>Workshop</span>
                    @else
                        <span class="badge" style="background:#f3e8ff;color:#7c3aed;border:1px solid #ddd6fe;font-weight:700;"><i class="fas fa-box-open" style="font-size:.65rem;margin-right:3px;"></i>Registry</span>
                    @endif
                </td>
                <td><strong>{{ $row->ref }}</strong></td>
                <td>{{ $row->item }}<br><small class="text-muted">{{ $row->brand }}</small></td>
                <td class="text-sm">{{ $row->serial ?? '—' }}</td>
                <td class="text-sm">{{ $row->status }}</td>
                <td style="text-align:center;font-weight:700;color:#0891b2;">{{ $row->available ?? '—' }}</td>
                <td class="text-sm">{{ $row->handler ?? '—' }}</td>
                <td class="text-sm">{{ $row->date?->format('d M Y') ?? '—' }}</td>
                <td><a href="{{ route($row->view_route, $row->view_id) }}" class="btn btn-sm btn-primary">View</a></td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted" style="padding:30px;">No equipment found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
