@extends('layouts.app')
@section('title', 'Import Workshop Equipment')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-file-import"></i> Bulk Import Workshop Equipment</h2>
    <a href="{{ route('workshop.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card">
    <div class="card-body">
        <p class="text-sm text-muted" style="margin-bottom:16px;">
            Upload a spreadsheet (.xlsx, .xls or .csv) of equipment already in the workshop to create a job for each row.
            Expected columns: <code>DATE RECEIVED, FROM, TO, FAULT, MODEL, TYPE, RECEIVED BY, STATUS, COLLECTED/CARTED BY, TECHNICIAN, DEPARTURE DATE, SERIAL NO, 208 NUMBER</code>.
            Column order doesn't matter as long as the header names match. Rows whose serial number already exists anywhere
            in the system are skipped and reported, not duplicated. Imported jobs land unassigned — assign a technician
            afterward from each job's page.
        </p>

        <form method="POST" action="{{ route('workshop.import.store') }}" enctype="multipart/form-data">
            @csrf
            <div class="form-group">
                <label class="form-label">Spreadsheet *</label>
                <input type="file" name="file" class="form-control @error('file') is-invalid @enderror" accept=".xlsx,.xls,.csv" required>
                @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Upload &amp; Import</button>
            <a href="{{ route('workshop.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
