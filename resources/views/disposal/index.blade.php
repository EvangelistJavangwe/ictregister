@extends('layouts.app')
@section('title', auth()->user()->isTechnician() ? 'My Disposal Requests' : 'Disposal Register')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-trash-alt"></i>
        {{ auth()->user()->isTechnician() ? 'My Disposal Requests' : 'ICT Equipment Disposal Register' }}
    </h2>
    <a href="{{ route('disposal.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i>
        {{ auth()->user()->isTechnician() ? 'Request Disposal' : 'New Disposal' }}
    </a>
</div>

<form method="GET" class="filter-bar" id="disposal-filter-form">
    <div class="form-group">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Ref no, asset, serial...">
    </div>
    <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
            <option value="">All</option>
            @foreach(['Requested','Pending Approval','Approved','Rejected','Disposed'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group" style="align-self:flex-end;display:flex;gap:6px;flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        <a href="{{ route('disposal.index') }}" class="btn btn-secondary">Clear</a>
        <div style="position:relative;display:inline-block;">
            <button type="button" class="btn btn-success" onclick="toggleExportMenu('disposal-export-menu')">
                <i class="fas fa-download"></i> Export
            </button>
            <div id="disposal-export-menu" style="display:none;position:absolute;top:calc(100% + 4px);left:0;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 6px 16px rgba(0,0,0,.1);z-index:200;min-width:150px;overflow:hidden;">
                @foreach(['csv'=>'CSV Spreadsheet','excel'=>'Excel (.xlsx)','word'=>'Word Document','pdf'=>'PDF Report'] as $fmt => $label)
                <a href="javascript:void(0)" onclick="doExport('{{ route('disposal.export') }}', '{{ $fmt }}', 'disposal-filter-form')"
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
                    <th>Disposal Ref.</th><th>Asset Description</th><th>Asset Tag/Serial</th>
                    <th>Department</th><th>Requested By</th><th>Status</th>
                    @if(auth()->user()->isAdminOrHod())<th>Approved By</th>@endif
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($records as $record)
            <tr>
                <td><strong>{{ $record->disposal_ref_no ?? '—' }}</strong></td>
                <td>{{ $record->asset_description }}<br><small class="text-muted">{{ $record->model_brand }}</small></td>
                <td class="text-sm">{{ $record->asset_tag_serial_no ?? '—' }}</td>
                <td class="text-sm">{{ $record->department_user ?? '—' }}</td>
                <td class="text-sm">{{ $record->requester?->firstname ?? '—' }}</td>
                <td>
                    <span class="badge {{ $record->status==='Disposed'?'badge-danger':($record->status==='Approved'?'badge-success':($record->status==='Rejected'?'badge-secondary':'badge-warning')) }}">
                        {{ $record->status }}
                    </span>
                </td>
                @if(auth()->user()->isAdminOrHod())
                <td class="text-sm">{{ $record->approver?->firstname ?? '—' }}</td>
                @endif
                <td><a href="{{ route('disposal.show', $record) }}" class="btn btn-sm btn-primary">View</a></td>
            </tr>
            @empty
            <tr><td colspan="8" class="text-center text-muted" style="padding:30px;">No records found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $records->appends(request()->query())->links() }}</div>
</div>
@endsection
