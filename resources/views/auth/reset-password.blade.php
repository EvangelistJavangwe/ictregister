@extends('layouts.auth')
@section('title', 'Reset Password')
@section('subtitle', 'Enter OTP and new password')

@section('content')
<form method="POST" action="{{ route('password.reset') }}">
    @csrf
    <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-wrap">
            <i class="fas fa-envelope input-icon"></i>
            <input type="email" name="email" class="form-control" value="{{ old('email') ?? $linkEmail ?? session('otp_email') }}" required>
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">OTP Code (from email)</label>
        <input type="text" name="otp" class="form-control no-icon" maxlength="6" placeholder="Enter 6-digit OTP" required style="text-align:center;letter-spacing:.2em;font-size:1.1rem;" value="{{ old('otp') ?? $linkOtp }}">
    </div>
    <div class="form-group">
        <label class="form-label">New Password</label>
        <div class="input-wrap">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" name="password" id="new-password" class="form-control @error('password') is-invalid @enderror" required minlength="8">
        </div>
        @error('password')<div class="invalid-feedback" style="display:block;">{{ $message }}</div>@enderror
        <div id="pw-strength" style="margin-top:8px;display:none;">
            <div style="height:4px;border-radius:2px;background:#e2e8f0;margin-bottom:6px;"><div id="pw-bar" style="height:4px;border-radius:2px;width:0;transition:width .3s,background .3s;"></div></div>
            <ul style="list-style:none;padding:0;margin:0;font-size:.75rem;display:grid;grid-template-columns:1fr 1fr;gap:2px 8px;">
                <li id="req-len"  style="color:#94a3b8;"><i class="fas fa-circle" style="font-size:.5rem;margin-right:4px;"></i>8+ characters</li>
                <li id="req-upper" style="color:#94a3b8;"><i class="fas fa-circle" style="font-size:.5rem;margin-right:4px;"></i>Uppercase (A–Z)</li>
                <li id="req-lower" style="color:#94a3b8;"><i class="fas fa-circle" style="font-size:.5rem;margin-right:4px;"></i>Lowercase (a–z)</li>
                <li id="req-num"  style="color:#94a3b8;"><i class="fas fa-circle" style="font-size:.5rem;margin-right:4px;"></i>Number (0–9)</li>
                <li id="req-sym"  style="color:#94a3b8;"><i class="fas fa-circle" style="font-size:.5rem;margin-right:4px;"></i>Special char (!@#$…)</li>
            </ul>
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Confirm New Password</label>
        <div class="input-wrap">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" name="password_confirmation" class="form-control" required>
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-key"></i> &nbsp;Reset Password</button>
</form>
@push('scripts')
<script>
const pw = document.getElementById('new-password');
const bar = document.getElementById('pw-bar');
const checks = {
    'req-len':   v => v.length >= 8,
    'req-upper': v => /[A-Z]/.test(v),
    'req-lower': v => /[a-z]/.test(v),
    'req-num':   v => /[0-9]/.test(v),
    'req-sym':   v => /[^A-Za-z0-9]/.test(v),
};
pw.addEventListener('input', function () {
    const v = this.value;
    document.getElementById('pw-strength').style.display = v ? 'block' : 'none';
    let passed = 0;
    for (const [id, fn] of Object.entries(checks)) {
        const ok = fn(v);
        if (ok) passed++;
        const el = document.getElementById(id);
        el.style.color = ok ? '#16a34a' : '#94a3b8';
        el.querySelector('i').className = ok ? 'fas fa-check-circle' : 'fas fa-circle';
        el.querySelector('i').style.fontSize = ok ? '.75rem' : '.5rem';
    }
    const colors = ['#dc2626','#f97316','#eab308','#84cc16','#16a34a'];
    bar.style.width = (passed * 20) + '%';
    bar.style.background = colors[passed - 1] || '#e2e8f0';
});
</script>
@endpush

<div style="margin-top:14px;text-align:center;">
    <a href="{{ route('password.forgot') }}" class="link">Resend OTP</a> &nbsp;·&nbsp;
    <a href="{{ route('login') }}" class="link">Back to Login</a>
</div>
@endsection
