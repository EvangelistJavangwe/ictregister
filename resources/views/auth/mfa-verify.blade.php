@extends('layouts.auth')
@section('title', 'Two-Factor Authentication')
@section('subtitle', 'Enter your authenticator code')

@section('content')
<p style="font-size:.875rem;color:#64748b;margin-bottom:16px;text-align:center;">Open your authenticator app and enter the 6-digit code for this account.</p>

<form method="POST" action="{{ route('mfa.verify.post') }}">
    @csrf
    <div class="form-group">
        <label class="form-label text-center" style="text-align:center;display:block;">Authentication Code</label>
        <input type="text" name="otp_code" class="form-control no-icon" maxlength="6" pattern="\d{6}" placeholder="000000" required autofocus style="text-align:center;letter-spacing:.3em;font-size:1.4rem;font-weight:700;">
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> &nbsp;Verify</button>
</form>

<div style="margin-top:14px;text-align:center;">
    <form method="POST" action="{{ route('logout') }}" style="display:inline;">
        @csrf
        <button type="submit" style="background:none;border:none;cursor:pointer;" class="link">Use another account</button>
    </form>
</div>
@endsection
