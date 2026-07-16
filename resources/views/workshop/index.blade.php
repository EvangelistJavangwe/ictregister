@extends('layouts.app')
@section('title', 'Workshop Equipment Register')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-tools"></i> ICT Workshop Equipment Register</h2>
    <div class="d-flex gap-2">
        @if(auth()->user()->isAdminOrHod())
        <a href="{{ route('equipment.index', ['type' => 'workshop']) }}" class="btn btn-secondary"><i class="fas fa-boxes"></i> Equipments</a>
        <a href="{{ route('workshop.import') }}" class="btn btn-secondary"><i class="fas fa-file-import"></i> Import Equipment</a>
        @endif
        <a href="{{ route('workshop.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> New Job</a>
    </div>
</div>

{{-- Status summary strip --}}
<div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <a href="{{ route('workshop.index', array_merge(request()->query(), ['status'=>'Pending'])) }}"
       style="flex:1;min-width:140px;background:#fff;border:1px solid #e2e8f0;border-top:3px solid #f59e0b;border-radius:10px;padding:14px 18px;text-decoration:none;transition:box-shadow .15s;"
       onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow=''">
        <div style="font-size:1.7rem;font-weight:800;color:#f59e0b;">{{ $statusCounts['Pending'] ?? 0 }}</div>
        <div style="font-size:.78rem;color:#64748b;font-weight:600;margin-top:2px;"><i class="fas fa-clock" style="margin-right:4px;"></i>Pending</div>
    </a>
    <a href="{{ route('workshop.index', array_merge(request()->query(), ['status'=>'In Progress'])) }}"
       style="flex:1;min-width:140px;background:#fff;border:1px solid #e2e8f0;border-top:3px solid #3b82f6;border-radius:10px;padding:14px 18px;text-decoration:none;transition:box-shadow .15s;"
       onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow=''">
        <div style="font-size:1.7rem;font-weight:800;color:#3b82f6;">{{ $statusCounts['In Progress'] ?? 0 }}</div>
        <div style="font-size:.78rem;color:#64748b;font-weight:600;margin-top:2px;"><i class="fas fa-spinner" style="margin-right:4px;"></i>In Progress</div>
    </a>
    <a href="{{ route('workshop.index', array_merge(request()->query(), ['status'=>'Completed'])) }}"
       style="flex:1;min-width:140px;background:#fff;border:1px solid #e2e8f0;border-top:3px solid #10b981;border-radius:10px;padding:14px 18px;text-decoration:none;transition:box-shadow .15s;"
       onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow=''">
        <div style="font-size:1.7rem;font-weight:800;color:#10b981;">{{ $statusCounts['Completed'] ?? 0 }}</div>
        <div style="font-size:.78rem;color:#64748b;font-weight:600;margin-top:2px;"><i class="fas fa-check-circle" style="margin-right:4px;"></i>Completed <span style="font-weight:400;">(awaiting collection)</span></div>
    </a>
    <a href="{{ route('workshop.index', array_merge(request()->query(), ['status'=>'Collected'])) }}"
       style="flex:1;min-width:140px;background:#fff;border:1px solid #e2e8f0;border-top:3px solid #7c3aed;border-radius:10px;padding:14px 18px;text-decoration:none;transition:box-shadow .15s;"
       onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow=''">
        <div style="font-size:1.7rem;font-weight:800;color:#7c3aed;">{{ $statusCounts['Collected'] ?? 0 }}</div>
        <div style="font-size:.78rem;color:#64748b;font-weight:600;margin-top:2px;"><i class="fas fa-box" style="margin-right:4px;"></i>Collected <span style="font-weight:400;">(closed)</span></div>
    </a>
    @if($overdue > 0)
    <a href="{{ route('workshop.index') }}"
       style="flex:1;min-width:140px;background:#fff;border:1px solid #e2e8f0;border-top:3px solid #dc2626;border-radius:10px;padding:14px 18px;text-decoration:none;transition:box-shadow .15s;"
       onmouseover="this.style.boxShadow='0 4px 12px rgba(0,0,0,.08)'" onmouseout="this.style.boxShadow=''">
        <div style="font-size:1.7rem;font-weight:800;color:#dc2626;">{{ $overdue }}</div>
        <div style="font-size:.78rem;color:#64748b;font-weight:600;margin-top:2px;"><i class="fas fa-exclamation-triangle" style="margin-right:4px;"></i>Overdue</div>
    </a>
    @endif
</div>

<!-- Filter -->
<form method="GET" class="filter-bar" id="workshop-filter-form">
    <div class="form-group">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" value="{{ request('search') }}" placeholder="Job no, equipment, serial...">
    </div>
    <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
            <option value="">All Status</option>
            @foreach(['Pending','In Progress','Completed','Collected'] as $s)
            <option value="{{ $s }}" {{ request('status') === $s ? 'selected' : '' }}>{{ $s }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Priority</label>
        <select name="priority" class="form-control">
            <option value="">All Priorities</option>
            @foreach(['Low','Medium','High','Urgent'] as $p)
            <option value="{{ $p }}" {{ request('priority') === $p ? 'selected' : '' }}>{{ $p }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group" style="align-self:flex-end;display:flex;gap:6px;flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        <a href="{{ route('workshop.index') }}" class="btn btn-secondary">Clear</a>
        <div style="position:relative;display:inline-block;">
            <button type="button" class="btn btn-success" onclick="toggleExportMenu('workshop-export-menu')">
                <i class="fas fa-download"></i> Export
            </button>
            <div id="workshop-export-menu" style="display:none;position:absolute;top:calc(100% + 4px);left:0;background:#fff;border:1px solid #e2e8f0;border-radius:8px;box-shadow:0 6px 16px rgba(0,0,0,.1);z-index:200;min-width:150px;overflow:hidden;">
                @foreach(['csv'=>'CSV Spreadsheet','excel'=>'Excel (.xlsx)','word'=>'Word Document','pdf'=>'PDF Report'] as $fmt => $label)
                <a href="javascript:void(0)" onclick="doExport('{{ route('workshop.export') }}', '{{ $fmt }}', 'workshop-filter-form')"
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
                    <th>Job Number</th><th>Date Received</th><th>Equipment</th><th>Depot/Dept/Contact</th><th>Serial/Asset</th>
                    <th>Priority</th><th>Technician</th><th>Status</th><th>Due Date</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($jobs as $job)
            <tr @if($job->isOverdue()) class="overdue-row" @endif>
                <td><strong>{{ $job->entry_job_number }}</strong></td>
                <td class="text-sm">{{ $job->date_time_received?->format('d M Y H:i') }}</td>
                <td>{{ $job->equipment_type }}<br><small class="text-muted">{{ $job->brand_make_model }}</small></td>
                <td class="text-sm">
                    @if($job->depot_name)<strong>{{ $job->depot_name }}</strong><br>@endif
                    {{ $job->department }}
                    @if($job->department && $job->contact_person)<br>@endif
                    {{ $job->contact_person }}
                </td>
                <td class="text-sm">{{ $job->serial_number_asset_tag ?? '—' }}</td>
                <td>
                    <span class="badge {{ $job->priority_level==='Urgent'?'badge-danger':($job->priority_level==='High'?'badge-warning':($job->priority_level==='Medium'?'badge-info':'badge-secondary')) }}">
                        {{ $job->priority_level }}
                    </span>
                </td>
                <td class="text-sm">{{ $job->technician?->firstname ?? '—' }}</td>
                <td>
                    @if($job->status === 'Collected')
                        <span class="badge" style="background:#ede9fe;color:#6d28d9;border:1px solid #ddd6fe;font-weight:700;">
                            <i class="fas fa-box" style="font-size:.65rem;margin-right:3px;"></i>Collected
                        </span>
                    @elseif($job->status === 'Completed')
                        <span class="badge" style="background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;font-weight:700;">
                            <i class="fas fa-check-circle" style="font-size:.65rem;margin-right:3px;"></i>Completed
                        </span>
                    @elseif($job->status === 'In Progress')
                        <span class="badge badge-info">
                            <i class="fas fa-spinner" style="font-size:.65rem;margin-right:3px;"></i>In Progress
                        </span>
                    @else
                        <span class="badge badge-warning">
                            <i class="fas fa-clock" style="font-size:.65rem;margin-right:3px;"></i>Pending
                        </span>
                    @endif
                    @if($job->isOverdue())<br><span class="text-danger text-sm"><i class="fas fa-exclamation-circle"></i> Overdue</span>@endif
                </td>
                <td class="text-sm {{ $job->isOverdue() ? 'text-danger fw-bold' : '' }}">{{ $job->due_date?->format('d M Y') ?? '—' }}</td>
                <td><a href="{{ route('workshop.show', $job) }}" class="btn btn-sm btn-primary">View</a></td>
            </tr>
            @empty
            <tr><td colspan="10" class="text-center text-muted" style="padding:30px;">No jobs found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $jobs->appends(request()->query())->links() }}</div>
</div>
@endsection
