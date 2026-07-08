@extends('layouts.app')
@section('title', 'Change Password')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-key"></i> Change Password</h2>
</div>

<div class="card" style="max-width:480px;">
    <div class="card-body">
        <form method="POST" action="{{ route('password.update') }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Current Password</label>
                <input type="password" name="current_password" class="form-control" required>
            </div>
            <div class="form-group">
                <label class="form-label">New Password</label>
                <input type="password" name="password" id="new-password" class="form-control @error('password') is-invalid @enderror" required minlength="8">
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                <div id="pw-strength" style="margin-top:8px;display:none;">
                    <div style="height:4px;border-radius:2px;background:#e2e8f0;margin-bottom:6px;"><div id="pw-bar" style="height:4px;border-radius:2px;width:0;transition:width .3s,background .3s;"></div></div>
                    <ul style="list-style:none;padding:0;margin:0;display:grid;grid-template-columns:1fr 1fr;gap:2px 12px;">
                        <li id="req-len"  style="font-size:.75rem;color:#94a3b8;"><i class="fas fa-circle" style="font-size:.5rem;margin-right:4px;"></i>At least 8 characters</li>
                        <li id="req-upper" style="font-size:.75rem;color:#94a3b8;"><i class="fas fa-circle" style="font-size:.5rem;margin-right:4px;"></i>Uppercase letter (A–Z)</li>
                        <li id="req-lower" style="font-size:.75rem;color:#94a3b8;"><i class="fas fa-circle" style="font-size:.5rem;margin-right:4px;"></i>Lowercase letter (a–z)</li>
                        <li id="req-num"  style="font-size:.75rem;color:#94a3b8;"><i class="fas fa-circle" style="font-size:.5rem;margin-right:4px;"></i>Number (0–9)</li>
                        <li id="req-sym"  style="font-size:.75rem;color:#94a3b8;"><i class="fas fa-circle" style="font-size:.5rem;margin-right:4px;"></i>Special character (!@#$…)</li>
                    </ul>
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Confirm New Password</label>
                <input type="password" name="password_confirmation" class="form-control" required>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Update Password</button>
        </form>
    </div>
</div>
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
@endsection
