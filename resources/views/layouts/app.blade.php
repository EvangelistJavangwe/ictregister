<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'ICT Register') — {{ config('app.name') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('images/gmb-logo.png') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: #f1f5f9; color: #1e293b; min-height: 100vh; display: flex; }

        /* Sidebar */
        .sidebar { width: 260px; height: 100vh; background: #1e293b; display: flex; flex-direction: column; position: fixed; top: 0; left: 0; z-index: 100; transition: transform .3s; overflow: hidden; }
        .sidebar-brand { padding: 20px 20px 16px; border-bottom: 1px solid #334155; }
        .sidebar-brand h1 { color: #fff; font-size: 1.1rem; font-weight: 700; }
        .sidebar-brand p { color: #94a3b8; font-size: .75rem; margin-top: 2px; }
        .sidebar-user { padding: 14px 20px; border-bottom: 1px solid #334155; }
        .sidebar-user .name { color: #f1f5f9; font-size: .9rem; font-weight: 600; }
        .sidebar-user .role-badge { display: inline-block; padding: 2px 8px; border-radius: 12px; font-size: .7rem; font-weight: 600; margin-top: 4px; }
        .role-super_admin { background: #7c3aed; color: #fff; }
        .role-hod { background: #0284c7; color: #fff; }
        .role-technician { background: #16a34a; color: #fff; }
        .sidebar-nav { flex: 1; padding: 12px 0; overflow-y: auto; }
        .nav-section { padding: 8px 20px 4px; color: #64748b; font-size: .7rem; font-weight: 600; text-transform: uppercase; letter-spacing: .05em; }
        .nav-item { display: flex; align-items: center; gap: 10px; padding: 9px 20px; color: #cbd5e1; font-size: .875rem; text-decoration: none; transition: all .15s; }
        .nav-item:hover, .nav-item.active { background: #334155; color: #fff; }
        .nav-item.active { border-right: 3px solid #3b82f6; }
        .nav-item i { width: 18px; text-align: center; font-size: .9rem; }
        .sidebar-footer { padding: 12px 20px; border-top: 1px solid #334155; }

        /* Main */
        .main-wrapper { margin-left: 260px; flex: 1; display: flex; flex-direction: column; min-height: 100vh; }
        .topbar { background: #fff; border-bottom: 1px solid #e2e8f0; padding: 0 24px; height: 60px; display: flex; align-items: center; justify-content: space-between; position: sticky; top: 0; z-index: 90; }
        .topbar-title { font-size: 1rem; font-weight: 600; color: #1e293b; }
        .topbar-right { display: flex; align-items: center; gap: 16px; }
        .topbar-btn { background: none; border: none; cursor: pointer; color: #64748b; font-size: .9rem; padding: 6px; border-radius: 6px; transition: color .15s; }
        .topbar-btn:hover { color: #1e293b; background: #f1f5f9; }
        .content { flex: 1; padding: 24px; }
        .page-header { margin-bottom: 24px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px; }
        .page-header h2 { font-size: 1.25rem; font-weight: 700; color: #1e293b; }

        /* Cards */
        .card { background: #fff; border-radius: 10px; border: 1px solid #e2e8f0; }
        .card-body { padding: 20px; }
        .card-header { padding: 14px 20px; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; }
        .card-header h3 { font-size: .95rem; font-weight: 600; color: #1e293b; }

        /* Stats grid */
        .stats-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(200px, 1fr)); gap: 16px; margin-bottom: 24px; }
        .stat-card { background: #fff; border-radius: 10px; border: 1px solid #e2e8f0; padding: 20px; display: flex; align-items: center; gap: 16px; }
        .stat-icon { width: 48px; height: 48px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-size: 1.3rem; }
        .stat-info .value { font-size: 1.6rem; font-weight: 700; line-height: 1; }
        .stat-info .label { font-size: .78rem; color: #64748b; margin-top: 4px; }

        /* Table */
        .table-wrap { overflow-x: auto; }
        table { width: 100%; border-collapse: collapse; font-size: .875rem; }
        th { background: #f8fafc; color: #475569; font-weight: 600; padding: 10px 14px; text-align: left; border-bottom: 2px solid #e2e8f0; white-space: nowrap; }
        td { padding: 10px 14px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
        tr:hover td { background: #f8fafc; }

        /* Badges */
        .badge { display: inline-flex; align-items: center; padding: 3px 10px; border-radius: 12px; font-size: .72rem; font-weight: 600; white-space: nowrap; }
        .badge-success { background: #dcfce7; color: #16a34a; }
        .badge-warning { background: #fef9c3; color: #ca8a04; }
        .badge-danger { background: #fee2e2; color: #dc2626; }
        .badge-info { background: #dbeafe; color: #2563eb; }
        .badge-secondary { background: #f1f5f9; color: #64748b; }
        .badge-purple { background: #f3e8ff; color: #7c3aed; }

        /* Buttons */
        .btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: .875rem; font-weight: 500; cursor: pointer; border: none; text-decoration: none; transition: all .15s; white-space: nowrap; }
        .btn-sm { padding: 5px 10px; font-size: .8rem; }
        .btn-primary { background: #2563eb; color: #fff; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-success { background: #16a34a; color: #fff; }
        .btn-success:hover { background: #15803d; }
        .btn-warning { background: #d97706; color: #fff; }
        .btn-warning:hover { background: #b45309; }
        .btn-danger { background: #dc2626; color: #fff; }
        .btn-danger:hover { background: #b91c1c; }
        .btn-secondary { background: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
        .btn-secondary:hover { background: #e2e8f0; }
        .btn-outline { background: transparent; border: 1px solid #e2e8f0; color: #475569; }
        .btn-outline:hover { background: #f8fafc; }

        /* Forms */
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: .875rem; font-weight: 500; color: #374151; margin-bottom: 6px; }
        .form-control { width: 100%; padding: 9px 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .875rem; color: #1e293b; background: #fff; transition: border-color .15s; outline: none; }
        .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
        .form-control.is-invalid { border-color: #dc2626; }
        .invalid-feedback { color: #dc2626; font-size: .8rem; margin-top: 4px; }
        .form-row { display: grid; grid-template-columns: repeat(auto-fill, minmax(240px, 1fr)); gap: 16px; }
        select.form-control { cursor: pointer; }
        textarea.form-control { resize: vertical; min-height: 90px; }

        /* Alerts */
        .alert { padding: 12px 16px; border-radius: 8px; margin-bottom: 16px; display: flex; align-items: flex-start; gap: 10px; font-size: .875rem; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-warning { background: #fef9c3; color: #92400e; border: 1px solid #fde68a; }
        .alert-info { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }

        /* Pagination */
        .pagination { display: flex; align-items: center; gap: 4px; flex-wrap: wrap; }
        .pagination-btn { display: inline-flex; align-items: center; justify-content: center; min-width: 36px; height: 36px; padding: 0 10px; border-radius: 8px; font-size: .875rem; font-weight: 500; border: 1px solid #e2e8f0; color: #475569; text-decoration: none; cursor: pointer; transition: all .15s; user-select: none; }
        .pagination-btn:hover:not(.disabled):not(.active) { background: #f1f5f9; color: #1e293b; border-color: #cbd5e1; }
        .pagination-btn.active { background: #2563eb; color: #fff; border-color: #2563eb; cursor: default; }
        .pagination-btn.disabled { color: #cbd5e1; border-color: #f1f5f9; background: #fafafa; cursor: not-allowed; }
        .pagination-btn.pagination-dots { border: none; background: none; color: #94a3b8; cursor: default; }

        /* Utility */
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .text-sm { font-size: .8rem; }
        .text-muted { color: #64748b; }
        .text-danger { color: #dc2626; }
        .text-success { color: #16a34a; }
        .fw-bold { font-weight: 700; }
        .mt-1 { margin-top: 8px; } .mt-2 { margin-top: 16px; } .mt-3 { margin-top: 24px; }
        .mb-1 { margin-bottom: 8px; } .mb-2 { margin-bottom: 16px; } .mb-3 { margin-bottom: 24px; }
        .d-flex { display: flex; } .align-center { align-items: center; } .gap-2 { gap: 8px; } .gap-3 { gap: 12px; }
        .flex-wrap { flex-wrap: wrap; } .justify-between { justify-content: space-between; }
        .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
        .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 16px; }
        .sidebar-overlay { display: none; position: fixed; inset: 0; background: rgba(0,0,0,.45); z-index: 99; }
        .menu-toggle { display: none; background: none; border: none; cursor: pointer; color: #64748b; font-size: 1.2rem; padding: 6px 8px; border-radius: 6px; margin-right: 8px; }
        .menu-toggle:hover { color: #1e293b; background: #f1f5f9; }
        @media (max-width: 768px) {
            .grid-2, .grid-3 { grid-template-columns: 1fr; }
            .sidebar { transform: translateX(-260px); }
            .sidebar.open { transform: translateX(0); }
            .sidebar-overlay.open { display: block; }
            .main-wrapper { margin-left: 0; }
            .menu-toggle { display: inline-flex; align-items: center; }
            .content { padding: 16px; }
            .topbar { padding: 0 16px; }
        }
        .divider { border: none; border-top: 1px solid #e2e8f0; margin: 16px 0; }
        .overdue-row td { background: #fff7ed !important; }

        /* Signature pad */
        .sig-pad-wrap { border: 1px solid #d1d5db; border-radius: 8px; overflow: hidden; }
        #signature-pad { display: block; width: 100%; height: 150px; cursor: crosshair; background: #fff; }

        /* Filter bar */
        .filter-bar { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 16px; margin-bottom: 20px; display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; }
        .filter-bar .form-group { margin-bottom: 0; min-width: 160px; }

        /* Detail view */
        .detail-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 0; }
        .detail-item { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; }
        .detail-item:nth-child(odd) { background: #f8fafc; }
        .detail-label { font-size: .78rem; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: .04em; }
        .detail-value { font-size: .9rem; color: #1e293b; margin-top: 3px; font-weight: 500; }
    </style>
    @stack('styles')
</head>
<body>
    <div class="sidebar-overlay" id="sidebar-overlay"></div>

    <!-- Sidebar -->
    <nav class="sidebar" id="sidebar">
        <div class="sidebar-brand">
            <h1><i class="fas fa-desktop" style="color:#3b82f6;margin-right:8px;"></i>ICT Register</h1>
            <p>{{ config('app.name') }}</p>
        </div>
        <div class="sidebar-user">
            <div class="name">{{ auth()->user()->firstname }} {{ auth()->user()->lastname }}</div>
            <div class="text-sm text-muted">{{ auth()->user()->username }}</div>
            <span class="role-badge role-{{ auth()->user()->role }}">
                {{ ucfirst(str_replace('_', ' ', auth()->user()->role)) }}
            </span>
        </div>
        <div class="sidebar-nav">
            <div class="nav-section">Main</div>
            <a href="{{ route('dashboard') }}" class="nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <i class="fas fa-tachometer-alt"></i> Dashboard
            </a>

            <div class="nav-section">Registers</div>
            @if(auth()->user()->isAdminOrHod())
            <a href="{{ route('equipment-receiving.index') }}" class="nav-item {{ request()->routeIs('equipment-receiving.*') ? 'active' : '' }}">
                <i class="fas fa-box-open"></i> Helpdesk Administrator
            </a>
            @endif

            <a href="{{ route('workshop.index') }}" class="nav-item {{ request()->routeIs('workshop.*') ? 'active' : '' }}">
                <i class="fas fa-tools"></i> Workshop Register
            </a>

            <a href="{{ route('disposal.index') }}" class="nav-item {{ request()->routeIs('disposal.*') ? 'active' : '' }}">
                <i class="fas fa-trash-alt"></i>
                @if(auth()->user()->isTechnician()) Disposal Requests
                @else Disposal Register @endif
            </a>

            @if(auth()->user()->isAdminOrHod())
            <div class="nav-section">Administration</div>
            <a href="{{ route('users.index') }}" class="nav-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                <i class="fas fa-users"></i> Users
            </a>
            <a href="{{ route('equipment-history.index') }}" class="nav-item {{ request()->routeIs('equipment-history.*') ? 'active' : '' }}">
                <i class="fas fa-history"></i> Equipment History
            </a>
            <a href="{{ route('audit-trail.index') }}" class="nav-item {{ request()->routeIs('audit-trail.*') ? 'active' : '' }}">
                <i class="fas fa-clipboard-list"></i> Audit Trail
            </a>
            @if(auth()->user()->isSuperAdmin())
            <a href="{{ route('backup.index') }}" class="nav-item {{ request()->routeIs('backup.*') ? 'active' : '' }}">
                <i class="fas fa-database"></i> Backup
            </a>
            @endif
            @endif

            <div class="nav-section">Account</div>
            <a href="{{ route('profile.edit') }}" class="nav-item {{ request()->routeIs('profile.edit') ? 'active' : '' }}">
                <i class="fas fa-id-badge"></i> My Profile
            </a>
            <a href="{{ route('mfa.setup') }}" class="nav-item {{ request()->routeIs('mfa.*') ? 'active' : '' }}">
                <i class="fas fa-shield-alt"></i> 2FA Security
            </a>
            <a href="{{ route('password.change') }}" class="nav-item {{ request()->routeIs('password.change') ? 'active' : '' }}">
                <i class="fas fa-key"></i> Change Password
            </a>
        </div>
        <div class="sidebar-footer">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-item" style="width:100%;background:none;border:none;cursor:pointer;color:#94a3b8;">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </button>
            </form>
        </div>
    </nav>

    <!-- Main -->
    <div class="main-wrapper">
        <div class="topbar">
            <div style="display:flex;align-items:center;">
                <button class="menu-toggle" id="menu-toggle" aria-label="Toggle menu"><i class="fas fa-bars"></i></button>
                <span class="topbar-title">@yield('title', 'Dashboard')</span>
            </div>
            <div class="topbar-right">
                @if(auth()->user()->mfa_enabled)
                    <span class="badge badge-success"><i class="fas fa-shield-alt"></i> 2FA On</span>
                @else
                    <a href="{{ route('mfa.setup') }}" class="badge badge-warning" style="text-decoration:none;"><i class="fas fa-exclamation-triangle"></i> Enable 2FA</a>
                @endif
                <span class="text-sm text-muted">{{ now()->format('d M Y') }}</span>
            </div>
        </div>
        <div class="content">
            @if(session('success'))
                <div class="alert alert-success"><i class="fas fa-check-circle"></i> {{ session('success') }}</div>
            @endif
            @if(session('error') || $errors->has('error'))
                <div class="alert alert-danger"><i class="fas fa-times-circle"></i> {{ session('error') ?? $errors->first('error') }}</div>
            @endif
            @if($errors->any() && !$errors->has('error'))
                <div class="alert alert-danger">
                    <i class="fas fa-times-circle"></i>
                    <ul style="margin:0;padding-left:16px;">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @yield('content')
        </div>
        <footer style="padding:10px 24px;border-top:1px solid #e2e8f0;background:#fff;display:flex;justify-content:flex-end;align-items:center;">
            <span style="font-size:.75rem;color:#94a3b8;">Developed by <strong style="color:#475569;">GMB ICT Department</strong></span>
        </footer>
    </div>

    <script>
    // Sidebar toggle for mobile
    (function() {
        const sidebar  = document.getElementById('sidebar');
        const overlay  = document.getElementById('sidebar-overlay');
        const toggle   = document.getElementById('menu-toggle');
        function openSidebar()  { sidebar.classList.add('open');  overlay.classList.add('open'); }
        function closeSidebar() { sidebar.classList.remove('open'); overlay.classList.remove('open'); }
        if (toggle)  toggle.addEventListener('click', function() { sidebar.classList.contains('open') ? closeSidebar() : openSidebar(); });
        if (overlay) overlay.addEventListener('click', closeSidebar);
        // Close sidebar on nav link click (so page transition feels snappy on mobile)
        document.querySelectorAll('.nav-item').forEach(function(el) {
            el.addEventListener('click', closeSidebar);
        });
    })();

    // Confirm delete
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', function(e) {
            if (!confirm(this.dataset.confirm)) e.preventDefault();
        });
    });

    // Export dropdown helpers
    function toggleExportMenu(id) {
        const menu = document.getElementById(id);
        if (!menu) return;
        const open = menu.style.display !== 'none';
        // close all menus first
        document.querySelectorAll('[id$="-export-menu"]').forEach(m => m.style.display = 'none');
        if (!open) menu.style.display = 'block';
    }
    // Close export menus on outside click
    document.addEventListener('click', function(e) {
        if (!e.target.closest('[id$="-export-menu"]') && !e.target.closest('button[onclick*="toggleExportMenu"]')) {
            document.querySelectorAll('[id$="-export-menu"]').forEach(m => m.style.display = 'none');
        }
    });
    // Build export URL from current filter form values + format, then navigate
    function doExport(baseUrl, format, formId) {
        const form   = document.getElementById(formId);
        const params = new URLSearchParams();
        if (form) {
            new FormData(form).forEach((val, key) => { if (val) params.set(key, val); });
        }
        params.set('format', format);
        window.location.href = baseUrl + '?' + params.toString();
        document.querySelectorAll('[id$="-export-menu"]').forEach(m => m.style.display = 'none');
    }
    </script>
    @stack('scripts')
</body>
</html>
