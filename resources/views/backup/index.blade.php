@extends('layouts.app')
@section('title', 'Database Backup')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-database"></i> Database Backup</h2>
</div>

<div class="card">
    <div class="card-header"><h3><i class="fab fa-microsoft"></i> Backup to SharePoint</h3></div>
    <div class="card-body">
        <p class="text-sm text-muted mb-2">
            Runs a full <code>.sql</code> dump of the entire database (all registers, users, and audit trail) and
            uploads it directly to your Microsoft 365 SharePoint site — no manual file handling needed.
        </p>
        <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:12px 16px;font-size:.85rem;color:#1e40af;margin-bottom:16px;">
            <i class="fas fa-info-circle"></i> Every backup (success or failure) is recorded in the Audit Trail.
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
@endsection
