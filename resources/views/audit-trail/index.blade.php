@extends('layouts.app')
@section('title', 'Audit Trail')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-clipboard-list"></i> System Audit Trail</h2>
</div>

<form method="GET" action="{{ route('audit-trail.index') }}" class="filter-bar" id="audit-filter-form" autocomplete="off">
    <div class="form-group">
        <label class="form-label">Username</label>
        <input type="text" name="username" class="form-control" value="{{ request('username') }}"
               placeholder="Search username..." autocomplete="off">
    </div>
    <div class="form-group">
        <label class="form-label">Module</label>
        <select name="module" class="form-control">
            <option value="">All Modules</option>
            @foreach($modules as $module)
            <option value="{{ $module }}" {{ request('module') == $module ? 'selected' : '' }}>{{ $module }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Status</label>
        <select name="badge_status" class="form-control">
            <option value="">All</option>
            @foreach(['success','failed','warning','info'] as $s)
            <option value="{{ $s }}" {{ request('badge_status') == $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">From</label>
        <input type="date" name="date_from" class="form-control"
               value="{{ request('date_from') }}" autocomplete="off">
    </div>
    <div class="form-group">
        <label class="form-label">To</label>
        <input type="date" name="date_to" class="form-control"
               value="{{ request('date_to') }}" autocomplete="off">
    </div>
    <div class="form-group" style="align-self:flex-end;display:flex;gap:6px;flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        <a href="{{ route('audit-trail.index') }}" class="btn btn-secondary">Clear</a>
        <div style="position:relative;display:inline-block;">
            <button type="button" class="btn btn-success" onclick="toggleExportMenu('audit-export-menu')">
                <i class="fas fa-download"></i> Export
            </button>
            <div id="audit-export-menu" style="display:none;position:absolute;top:calc(100% + 4px);left:0;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 6px 16px rgba(0,0,0,.1);z-index:200;min-width:150px;overflow:hidden;">
                @foreach(['csv'=>'CSV Spreadsheet','excel'=>'Excel (.xlsx)','word'=>'Word Document','pdf'=>'PDF Report'] as $fmt => $label)
                <a href="javascript:void(0)" onclick="doExport('{{ route('audit-trail.export') }}', '{{ $fmt }}', 'audit-filter-form')"
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
                    <th>Time</th>
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
            @forelse($trails as $trail)
            <tr>
                <td class="text-sm" style="white-space:nowrap;">{{ $trail->created_at?->format('d M Y H:i:s') }}</td>
                <td><strong>{{ $trail->username }}</strong></td>
                <td class="text-sm">{{ $trail->action }}</td>
                <td><span class="badge badge-info">{{ $trail->module }}</span></td>
                <td class="text-sm" style="max-width:260px;">{{ $trail->description }}</td>
                <td class="text-sm" style="font-family:monospace;white-space:nowrap;">{{ $trail->ip_address ?? '—' }}</td>
                <td class="text-sm" style="font-family:monospace;white-space:nowrap;">{{ $trail->mac_address ?? '—' }}</td>
                <td class="text-sm" style="white-space:nowrap;">{{ $trail->computer_name ?? '—' }}</td>
                <td>
                    <span class="badge badge-{{ $trail->badge_status==='success'?'success':($trail->badge_status==='failed'?'danger':($trail->badge_status==='warning'?'warning':'info')) }}">
                        {{ ucfirst($trail->badge_status) }}
                    </span>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted" style="padding:30px;">No audit records found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
        <span class="text-sm text-muted">
            Showing {{ $trails->firstItem() ?? 0 }}–{{ $trails->lastItem() ?? 0 }} of {{ $trails->total() }} records
        </span>
        {{ $trails->appends(request()->query())->links() }}
    </div>
</div>
@endsection
