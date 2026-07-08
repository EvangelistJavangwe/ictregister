@extends('layouts.auth')
@section('title', 'Setup 2FA')
@section('subtitle', 'Enable Two-Factor Authentication')

@section('content')
<p style="font-size:.875rem;color:#64748b;margin-bottom:14px;">Scan this QR code with your authenticator app (Google Authenticator, Authy, etc.), then enter the 6-digit code to confirm.</p>

<div style="text-align:center;margin-bottom:16px;">
    <img src="{{ $qrCodeDataUri }}" style="width:180px;height:180px;border:4px solid #fff;border-radius:8px;box-shadow:0 2px 8px rgba(0,0,0,.15);">
</div>

<p style="font-size:.8rem;color:#64748b;text-align:center;margin-bottom:4px;">Or enter this secret manually:</p>
<p style="font-size:.85rem;font-weight:700;color:#1e293b;text-align:center;letter-spacing:.1em;margin-bottom:16px;">{{ $secret }}</p>

<form method="POST" action="{{ route('mfa.enable') }}">
    @csrf
    <div class="form-group">
        <label class="form-label">Enter 6-digit OTP code</label>
        <input type="text" name="otp_code" class="form-control no-icon" maxlength="6" pattern="\d{6}" placeholder="000000" required autofocus style="text-align:center;letter-spacing:.3em;font-size:1.2rem;">
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-shield-alt"></i> &nbsp;Enable 2FA</button>
</form>

<div style="margin-top:14px;text-align:center;">
    <a href="{{ route('dashboard') }}" class="link">Skip for now</a>
</div>
@endsection
