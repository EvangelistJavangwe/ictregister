@extends('layouts.app')
@section('title', 'Dashboard')

@section('content')
<div class="page-header">
    <h2>Welcome back, {{ auth()->user()->firstname }}!</h2>
    <span class="text-sm text-muted">{{ now()->format('l, d F Y') }}</span>
</div>

<!-- Stats -->
<div class="stats-grid">
    @if(auth()->user()->isAdminOrHod())
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-box-open"></i></div>
        <div class="stat-info">
            <div class="value" style="color:#2563eb;">{{ $stats['total_equipment_received'] }}</div>
            <div class="label">Equipment Received</div>
        </div>
    </div>
    @endif
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3;color:#ca8a04;"><i class="fas fa-clock"></i></div>
        <div class="stat-info">
            <div class="value" style="color:#ca8a04;">{{ $stats['workshop_pending'] }}</div>
            <div class="label">Pending Jobs</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-wrench"></i></div>
        <div class="stat-info">
            <div class="value" style="color:#2563eb;">{{ $stats['workshop_in_progress'] }}</div>
            <div class="label">In Progress</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <div class="value" style="color:#16a34a;">{{ $stats['workshop_completed'] }}</div>
            <div class="label">Completed</div>
        </div>
    </div>
    @if(auth()->user()->isAdminOrHod())
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-trash-alt"></i></div>
        <div class="stat-info">
            <div class="value" style="color:#dc2626;">{{ $stats['disposal_pending'] }}</div>
            <div class="label">Disposal Pending</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#f3e8ff;color:#7c3aed;"><i class="fas fa-users"></i></div>
        <div class="stat-info">
            <div class="value" style="color:#7c3aed;">{{ $stats['total_users'] }}</div>
            <div class="label">System Users</div>
        </div>
    </div>
    @endif
</div>

<div class="grid-2">
    <!-- Overdue Jobs -->
    @if(auth()->user()->isAdminOrHod() && $overdueJobs->count())
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-exclamation-triangle" style="color:#dc2626;"></i> Overdue Jobs</h3>
            <a href="{{ route('workshop.index') }}?status=In+Progress" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Job No.</th><th>Equipment</th><th>Technician</th><th>Due Date</th><th></th></tr></thead>
                <tbody>
                @foreach($overdueJobs as $job)
                <tr>
                    <td><span class="badge badge-danger">{{ $job->entry_job_number }}</span></td>
                    <td>{{ $job->equipment_type }}</td>
                    <td>{{ $job->technician?->firstname ?? 'Unassigned' }}</td>
                    <td class="text-danger">{{ $job->due_date?->format('d M Y') }}</td>
                    <td><a href="{{ route('workshop.show', $job) }}" class="btn btn-sm btn-primary">View</a></td>
                </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
    @endif

    <!-- My Jobs (Technicians) -->
    @if(auth()->user()->isTechnician() && $myJobs)
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-tools"></i> My Active Jobs</h3>
            <a href="{{ route('workshop.index') }}" class="btn btn-sm btn-secondary">View All</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Job No.</th><th>Equipment</th><th>Priority</th><th>Status</th><th></th></tr></thead>
                <tbody>
                @forelse($myJobs as $job)
                <tr @if($job->isOverdue()) class="overdue-row" @endif>
                    <td>{{ $job->entry_job_number }}</td>
                    <td>{{ $job->equipment_type }}</td>
                    <td>
                        <span class="badge {{ $job->priority_level === 'Urgent' ? 'badge-danger' : ($job->priority_level === 'High' ? 'badge-warning' : 'badge-info') }}">
                            {{ $job->priority_level }}
                        </span>
                    </td>
                    <td><span class="badge badge-info">{{ $job->status }}</span></td>
                    <td><a href="{{ route('workshop.show', $job) }}" class="btn btn-sm btn-primary">View</a></td>
                </tr>
                @empty
                <tr><td colspan="5" class="text-center text-muted">No active jobs assigned to you.</td></tr>
                @endforelse
            </table>
        </div>
    </div>
    @endif

    <!-- Recent Audit -->
    @if(auth()->user()->isAdminOrHod())
    <div class="card">
        <div class="card-header">
            <h3><i class="fas fa-clipboard-list"></i> Recent Activity</h3>
            <a href="{{ route('audit-trail.index') }}" class="btn btn-sm btn-secondary">Full Log</a>
        </div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>User</th><th>Action</th><th>Module</th><th>Time</th></tr></thead>
                <tbody>
                @foreach($recentAudit as $trail)
                <tr>
                    <td class="text-sm">{{ $trail->username }}</td>
                    <td>
                        <span class="badge badge-{{ $trail->badge_status === 'success' ? 'success' : ($trail->badge_status === 'failed' ? 'danger' : ($trail->badge_status === 'warning' ? 'warning' : 'info')) }}">
                            {{ $trail->action }}
                        </span>
                    </td>
                    <td class="text-sm">{{ $trail->module }}</td>
                    <td class="text-sm text-muted">{{ $trail->created_at?->diffForHumans() }}</td>
                </tr>
                @endforeach
            </table>
        </div>
    </div>
    @endif
</div>

<!-- Quick Actions -->
<div class="card mt-2">
    <div class="card-header"><h3>Quick Actions</h3></div>
    <div class="card-body" style="display:flex;gap:12px;flex-wrap:wrap;">
        <a href="{{ route('workshop.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> New Workshop Job</a>
        @if(auth()->user()->isAdminOrHod())
        <a href="{{ route('equipment-receiving.create') }}" class="btn btn-success"><i class="fas fa-box-open"></i> Receive Equipment</a>
        @endif
        <a href="{{ route('disposal.create') }}" class="btn btn-warning"><i class="fas fa-trash-alt"></i>
            {{ auth()->user()->isTechnician() ? 'Request Disposal' : 'New Disposal' }}
        </a>
        @if(auth()->user()->isAdminOrHod())
        <a href="{{ route('users.create') }}" class="btn btn-secondary"><i class="fas fa-user-plus"></i> Add User</a>
        <a href="{{ route('equipment-history.index') }}" class="btn btn-outline"><i class="fas fa-search"></i> Track Equipment</a>
        @endif
    </div>
</div>
@endsection
