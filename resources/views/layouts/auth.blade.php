<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta http-equiv="Cache-Control" content="no-cache, no-store, must-revalidate">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <title>@yield('title', 'Login') — ICT Register</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; }
        .auth-card { background: #fff; border-radius: 16px; width: 100%; max-width: 420px; box-shadow: 0 25px 50px rgba(0,0,0,.3); overflow: hidden; }
        .auth-header { background: linear-gradient(135deg, #1e293b, #0f172a); padding: 32px; text-align: center; }
        .auth-header .logo { display: flex; align-items: center; justify-content: center; margin: 0 auto 14px; }
        .auth-header .logo img { width: 56px; height: 56px; object-fit: contain; filter: drop-shadow(0 2px 6px rgba(0,0,0,.4)); }
        .auth-header h1 { color: #fff; font-size: 1.2rem; font-weight: 700; }
        .auth-header p { color: #94a3b8; font-size: .85rem; margin-top: 4px; }
        .auth-body { padding: 28px; }
        .form-group { margin-bottom: 16px; }
        .form-label { display: block; font-size: .875rem; font-weight: 500; color: #374151; margin-bottom: 6px; }
        .input-wrap { position: relative; }
        .input-icon { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #9ca3af; font-size: .9rem; }
        .form-control { width: 100%; padding: 10px 12px 10px 36px; border: 1px solid #d1d5db; border-radius: 8px; font-size: .875rem; color: #1e293b; outline: none; transition: border-color .15s; }
        .form-control:focus { border-color: #2563eb; box-shadow: 0 0 0 3px rgba(37,99,235,.1); }
        .form-control.no-icon { padding-left: 12px; }
        .btn { width: 100%; padding: 11px; border-radius: 8px; font-size: .9rem; font-weight: 600; border: none; cursor: pointer; transition: all .15s; }
        .btn-primary { background: #2563eb; color: #fff; touch-action: manipulation; }
        .btn-primary:hover { background: #1d4ed8; }
        .btn-primary:active { background: #1e40af; transform: scale(0.98); }
        .btn-primary:disabled { background: #93c5fd; cursor: not-allowed; }
        .btn-microsoft { display: flex; align-items: center; justify-content: center; background: #fff; color: #1e293b; border: 1px solid #d1d5db; text-decoration: none; }
        .btn-microsoft:hover { background: #f8fafc; border-color: #9ca3af; }
        .auth-divider { display: flex; align-items: center; gap: 12px; margin: 18px 0; color: #9ca3af; font-size: .78rem; text-transform: uppercase; letter-spacing: .04em; }
        .auth-divider::before, .auth-divider::after { content: ''; flex: 1; height: 1px; background: #e5e7eb; }
        .alert { padding: 10px 14px; border-radius: 8px; margin-bottom: 14px; font-size: .85rem; }
        .alert-danger { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
        .alert-success { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
        .text-center { text-align: center; }
        .link { color: #2563eb; text-decoration: none; font-size: .85rem; }
        .link:hover { text-decoration: underline; }
        .otp-inputs { display: flex; gap: 8px; justify-content: center; margin: 16px 0; }
        .otp-inputs input { width: 48px; height: 52px; text-align: center; font-size: 1.3rem; font-weight: 700; border: 2px solid #d1d5db; border-radius: 8px; outline: none; }
        .otp-inputs input:focus { border-color: #2563eb; }
    </style>
</head>
<body>
    <div class="auth-card">
        <div class="auth-header">
            <div class="logo">
                <img src="{{ asset('images/gmb-logo.png') }}" alt="Grain Marketing Board Zimbabwe">
            </div>
            <h1>ICT Register System</h1>
            <p>@yield('subtitle', 'Government of Zimbabwe — ICT Department')</p>
        </div>
        <div class="auth-body">
            @if(session('success'))
                <div class="alert alert-success">{{ session('success') }}</div>
            @endif
            @if($errors->any())
                <div class="alert alert-danger">
                    @foreach($errors->all() as $e) {{ $e }}<br> @endforeach
                </div>
            @endif
            @yield('content')
        </div>
        <div style="padding:12px 28px 16px;text-align:center;border-top:1px solid #f1f5f9;">
            <span style="font-size:.72rem;color:#94a3b8;">Developed by <strong style="color:#475569;">Evangelist Javangwe</strong> &nbsp;|&nbsp; Analyst Programmer</span>
        </div>
    </div>
<script>
// Prevent double-submission on all auth forms (mobile double-tap / touchend+click)
document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form').forEach(function (form) {
        var submitted = false;
        form.addEventListener('submit', function (e) {
            if (submitted) { e.preventDefault(); return; }
            submitted = true;
            var btn = form.querySelector('button[type="submit"]');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> &nbsp;Please wait…';
            }
        });
    });
});
</script>
@stack('scripts')
</body>
</html>
