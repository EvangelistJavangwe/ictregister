@extends('layouts.app')
@section('title', 'Helpdesk Administrator')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-box-open"></i> Helpdesk Administrator</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('equipment.index', ['type' => 'registry']) }}" class="btn btn-secondary"><i class="fas fa-boxes"></i> Equipments</a>
        <a href="{{ route('equipment-receiving.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Receive Equipment</a>
    </div>
</div>

<form method="GET" class="filter-bar" id="receiving-filter-form">
    <div class="form-group">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Ref no, supplier, item, serial...">
    </div>
    <div class="form-group">
        <label class="form-label">Stock Status</label>
        <select name="status" class="form-control">
            <option value="">All</option>
            <option value="pending"   {{ request('status')==='pending'    ? 'selected' : '' }}>Pending Inspection</option>
            <option value="partial"   {{ request('status')==='partial'    ? 'selected' : '' }}>Partially Inspected</option>
            <option value="inspected" {{ request('status')==='inspected'  ? 'selected' : '' }}>Fully Inspected</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Supplier</label>
        <select name="supplier" class="form-control">
            <option value="">All</option>
            @foreach($suppliers as $supplier)
            <option value="{{ $supplier }}" {{ request('supplier')===$supplier ? 'selected' : '' }}>{{ $supplier }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Date Received From</label>
        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
    </div>
    <div class="form-group">
        <label class="form-label">Date Received To</label>
        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
    </div>
    <div class="form-group" style="align-self:flex-end;display:flex;gap:6px;flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        <a href="{{ route('equipment-receiving.index') }}" class="btn btn-secondary">Clear</a>
        <div style="position:relative;display:inline-block;">
            <button type="button" class="btn btn-success" onclick="toggleExportMenu('receiving-export-menu')">
                <i class="fas fa-download"></i> Export
            </button>
            <div id="receiving-export-menu" style="display:none;position:absolute;top:calc(100% + 4px);left:0;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 6px 16px rgba(0,0,0,.1);z-index:200;min-width:150px;overflow:hidden;">
                @foreach(['csv'=>'CSV Spreadsheet','excel'=>'Excel (.xlsx)','word'=>'Word Document','pdf'=>'PDF Report'] as $fmt => $label)
                <a href="javascript:void(0)" onclick="doExport('{{ route('equipment-receiving.export') }}', '{{ $fmt }}', 'receiving-filter-form')"
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
        <table class="responsive-table">
            <thead>
                <tr>
                    <th>Ref No.</th>
                    <th>Date</th>
                    <th>Supplier</th>
                    <th>Item Description</th>
                    <th>Serial No.</th>
                    <th style="text-align:center;">Received</th>
                    <th style="text-align:center;">Inspected</th>
                    <th style="text-align:center;">Redistributed</th>
                    <th style="text-align:center;">Available</th>
                    <th>Stock Status</th>
                    <th>Warranty</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($records as $record)
            @php
                $inspected     = $record->totalInspected();
                $redistributed = $record->totalRedistributed();
                $available     = $record->availableStock();
                $pending       = $record->pendingInspection();
                $qty           = $record->qty_received;
                $status        = $record->stockStatus();
            @endphp
            <tr>
                <td data-label="Ref No."><a href="{{ route('equipment-receiving.show', $record) }}" style="font-weight:700;color:#2563eb;text-decoration:none;">{{ $record->cross_ref_no }}</a></td>
                <td class="text-sm" data-label="Date">{{ $record->date_received?->format('d M Y') }}</td>
                <td class="text-sm" data-label="Supplier">{{ $record->supplier }}</td>
                <td data-label="Item Description">
                    {{ $record->item_description }}
                    @if($record->brand_model)<br><small class="text-muted">{{ $record->brand_model }}</small>@endif
                </td>
                <td class="text-sm" data-label="Serial No.">{{ $record->serial_number ?? '—' }}</td>
                <td style="text-align:center;font-weight:700;color:#2563eb;" data-label="Received">{{ $qty }}</td>
                <td style="text-align:center;font-weight:700;color:#16a34a;" data-label="Inspected">{{ $inspected }}</td>
                <td style="text-align:center;font-weight:700;color:#7c3aed;" data-label="Redistributed">{{ $redistributed }}</td>
                <td style="text-align:center;font-weight:700;color:{{ $available > 0 ? '#0891b2' : '#94a3b8' }};font-size:1rem;" data-label="Available">{{ $available }}</td>
                <td data-label="Stock Status">
                    @if($status === 'Fully Inspected')
                        <span class="badge badge-success"><i class="fas fa-check-double"></i> Fully Inspected</span>
                    @elseif($status === 'Partially Inspected')
                        <span class="badge badge-warning">Partial</span>
                    @else
                        <span class="badge badge-secondary">Pending</span>
                    @endif
                </td>
                <td data-label="Warranty">
                    @if(!$record->warranty_end_date)
                        <span class="badge badge-secondary" style="font-size:.7rem;">No Warranty</span>
                    @elseif($record->warrantyStatus() === 'expired')
                        <span class="badge badge-danger" style="font-size:.7rem;" title="Expired {{ $record->warranty_end_date->format('d M Y') }}"><i class="fas fa-times-circle"></i> Expired</span>
                    @elseif($record->warrantyStatus() === 'expiring')
                        <span class="badge badge-warning" style="font-size:.7rem;" title="Expires {{ $record->warranty_end_date->format('d M Y') }}"><i class="fas fa-exclamation-triangle"></i> {{ $record->warrantyDaysLeft() }}d left</span>
                    @else
                        <span class="badge badge-success" style="font-size:.7rem;" title="Expires {{ $record->warranty_end_date->format('d M Y') }}"><i class="fas fa-shield-alt"></i> Active</span>
                    @endif
                </td>
                <td data-label="Actions">
                    <div class="d-flex gap-2">
                        <a href="{{ route('equipment-receiving.show', $record) }}" class="btn btn-sm btn-primary" title="View"><i class="fas fa-eye"></i></a>
                        @if($pending > 0)
                        <a href="{{ route('equipment-receiving.inspect', $record) }}" class="btn btn-sm btn-warning" title="Inspect"><i class="fas fa-clipboard-check"></i></a>
                        @endif
                        <a href="{{ route('equipment-receiving.redistribute', $record) }}" class="btn btn-sm btn-success" title="Redistribute"><i class="fas fa-share-square"></i></a>
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="12" class="text-center text-muted responsive-full" style="padding:32px;">No records found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $records->appends(request()->query())->links() }}</div>
</div>
@endsection
