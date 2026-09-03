@extends('layouts.app')
@section('title', 'Database Backup')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-database"></i> Database Backup</h2>
    <span class="text-sm text-muted">Nightly automatic backup runs at 00:00</span>
</div>

<!-- Stats -->
<div class="stats-grid">
    <div class="stat-card">
        @php
            $lastColor = match($lastRun?->badge_status) {
                'success' => '#16a34a', 'warning' => '#ca8a04', 'failed' => '#dc2626', default => '#64748b',
            };
            $lastIcon = match($lastRun?->badge_status) {
                'success' => 'fa-check-circle', 'warning' => 'fa-exclamation-triangle', 'failed' => 'fa-times-circle', default => 'fa-question-circle',
            };
        @endphp
        <div class="stat-icon" style="background:{{ $lastColor }}22;color:{{ $lastColor }};"><i class="fas {{ $lastIcon }}"></i></div>
        <div class="stat-info">
            <div class="value" style="color:{{ $lastColor }};font-size:1.1rem;">{{ $lastRun ? ucfirst($lastRun->badge_status) : 'No runs yet' }}</div>
            <div class="label">Last Backup {{ $lastRun?->created_at?->format('d M Y H:i') }}</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fab fa-microsoft"></i></div>
        <div class="stat-info">
            <div class="value" style="color:#16a34a;font-size:1.1rem;">{{ $lastSuccess?->created_at?->format('d M Y H:i') ?? '—' }}</div>
            <div class="label">Last Successful SharePoint Backup</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-info">
            <div class="value" style="color:#dc2626;font-size:1.1rem;">{{ $lastFailure?->created_at?->format('d M Y H:i') ?? 'None' }}</div>
            <div class="label">Last Failed / Warning</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#dbeafe;color:#2563eb;"><i class="fas fa-chart-line"></i></div>
        <div class="stat-info">
            <div class="value" style="color:#2563eb;">{{ $totalRuns ? round($successRuns / $totalRuns * 100) : 0 }}%</div>
            <div class="label">Success Rate (Last 30 Days, {{ $totalRuns }} runs)</div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header"><h3><i class="fab fa-microsoft"></i> Backup to SharePoint</h3></div>
    <div class="card-body">
        <p class="text-sm text-muted mb-2">
            Runs a full <code>.sql</code> dump of the entire database (all registers, users, and audit trail) and
            uploads it directly to your Microsoft 365 SharePoint site — no manual file handling needed.
        </p>
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 16px;font-size:.85rem;color:#1e40af;margin-bottom:16px;">
            <i class="fas fa-info-circle"></i> An automatic backup also runs every night at <strong>00:00</strong>. Every backup (success or failure) is recorded below.
        </div>
        <form method="POST" action="{{ route('backup.sharepoint') }}" style="display:inline;">
            @csrf
            <button type="submit" class="btn btn-primary">
                <i class="fas fa-cloud-upload-alt"></i> Backup Now to SharePoint
            </button>
        </form>
    </div>
</div>

<div class="card mt-2">
    <div class="card-header"><h3>Download Locally Instead</h3></div>
    <div class="card-body">
        <p class="text-sm text-muted mb-2">
            Alternatively, download the same <code>.sql</code> dump to this computer instead of uploading it to
            SharePoint.
        </p>
        <a href="{{ route('backup.download') }}" class="btn btn-secondary">
            <i class="fas fa-download"></i> Download Backup Now (.sql)
        </a>
    </div>
</div>

<hr class="divider">
<h4 style="font-size:.9rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:14px;"><i class="fas fa-history"></i> Backup History</h4>

<form method="GET" action="{{ route('backup.index') }}" class="filter-bar" id="backup-filter-form" autocomplete="off">
    <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
            <option value="">All</option>
            @foreach(['success'=>'Success','warning'=>'Warning','failed'=>'Failed'] as $val => $label)
            <option value="{{ $val }}" {{ request('status')===$val ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Type</label>
        <select name="type" class="form-control">
            <option value="">All</option>
            <option value="Scheduler" {{ request('type')==='Scheduler' ? 'selected' : '' }}>Automatic (00:00)</option>
            <option value="System" {{ request('type')==='System' ? 'selected' : '' }}>Manual</option>
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">From</label>
        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
    </div>
    <div class="form-group">
        <label class="form-label">To</label>
        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
    </div>
    <div class="form-group" style="align-self:flex-end;display:flex;gap:6px;flex-wrap:wrap;">
        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Filter</button>
        <a href="{{ route('backup.index') }}" class="btn btn-secondary">Clear</a>
    </div>
</form>

<div class="card">
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Time</th>
                    <th>Type</th>
                    <th>Status</th>
                    <th>Description</th>
                </tr>
            </thead>
            <tbody>
            @forelse($history as $entry)
            <tr>
                <td class="text-sm" style="white-space:nowrap;">{{ $entry->created_at?->format('d M Y H:i:s') }}</td>
                <td>
                    <span class="badge badge-info">
                        <i class="fas {{ $entry->module === 'Scheduler' ? 'fa-clock' : 'fa-user' }}"></i>
                        {{ $entry->module === 'Scheduler' ? 'Automatic (00:00)' : 'Manual' }}
                    </span>
                </td>
                <td>
                    <span class="badge badge-{{ $entry->badge_status==='success'?'success':($entry->badge_status==='failed'?'danger':'warning') }}">
                        {{ ucfirst($entry->badge_status) }}
                    </span>
                </td>
                <td class="text-sm">{{ $entry->description }}</td>
            </tr>
            @empty
            <tr><td colspan="4" class="text-center text-muted" style="padding:30px;">No backup history yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body" style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:8px;">
        <span class="text-sm text-muted">
            Showing {{ $history->firstItem() ?? 0 }}–{{ $history->lastItem() ?? 0 }} of {{ $history->total() }} records
        </span>
        {{ $history->links() }}
    </div>
</div>
@endsection
