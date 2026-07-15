@extends('layouts.app')
@section('title', 'Import Results')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-clipboard-check"></i> Import Results</h2>
    <div class="d-flex gap-2">
        <a href="{{ route('workshop.import') }}" class="btn btn-secondary"><i class="fas fa-redo"></i> Import Another File</a>
        <a href="{{ route('workshop.index') }}" class="btn btn-primary"><i class="fas fa-list"></i> View Workshop Register</a>
    </div>
</div>

<div class="stats-grid">
    <div class="stat-card">
        <div class="stat-icon" style="background:#dcfce7;color:#16a34a;"><i class="fas fa-check-circle"></i></div>
        <div class="stat-info">
            <div class="value">{{ count($imported) }}</div>
            <div class="label">Jobs Created</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fef9c3;color:#ca8a04;"><i class="fas fa-copy"></i></div>
        <div class="stat-info">
            <div class="value">{{ count($duplicates) }}</div>
            <div class="label">Already In System</div>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon" style="background:#fee2e2;color:#dc2626;"><i class="fas fa-exclamation-triangle"></i></div>
        <div class="stat-info">
            <div class="value">{{ count($invalid) }}</div>
            <div class="label">Skipped — Invalid</div>
        </div>
    </div>
</div>

@if(count($imported) > 0)
<div class="card mt-2">
    <div class="card-header"><h3><i class="fas fa-check-circle" style="color:#16a34a;"></i> Jobs Created</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Row</th><th>Job No.</th><th>Equipment Type</th><th>Serial</th><th></th></tr></thead>
            <tbody>
            @foreach($imported as $r)
                <tr>
                    <td>{{ $r['row'] }}</td>
                    <td><code>{{ $r['job']->entry_job_number }}</code></td>
                    <td>{{ $r['job']->equipment_type }}</td>
                    <td>{{ $r['job']->serial_number_asset_tag }}</td>
                    <td><a href="{{ route('workshop.show', $r['job']) }}" class="btn btn-sm btn-secondary">View</a></td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if(count($duplicates) > 0)
<div class="card mt-2">
    <div class="card-header"><h3><i class="fas fa-copy" style="color:#ca8a04;"></i> Already In System (skipped)</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Row</th><th>Serial</th><th>Existing Record</th><th></th></tr></thead>
            <tbody>
            @foreach($duplicates as $d)
                <tr>
                    <td>{{ $d['row'] }}</td>
                    <td>{{ $d['serial'] }}</td>
                    <td>{{ $d['label'] }} <span class="text-muted text-sm">— {{ $d['meta'] }}</span></td>
                    <td>
                        <a href="{{ route('equipment-history.index', ['type' => $d['identifier_type'], 'identifier' => $d['serial']]) }}" class="btn btn-sm btn-secondary">View History</a>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif

@if(count($invalid) > 0)
<div class="card mt-2">
    <div class="card-header"><h3><i class="fas fa-exclamation-triangle" style="color:#dc2626;"></i> Skipped — Invalid Rows</h3></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Row</th><th>Reason</th></tr></thead>
            <tbody>
            @foreach($invalid as $r)
                <tr><td>{{ $r['row'] }}</td><td>{{ $r['reason'] }}</td></tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
