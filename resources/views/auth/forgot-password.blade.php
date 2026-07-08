@extends('layouts.auth')
@section('title', 'Forgot Password')
@section('subtitle', 'Reset your password via OTP')

@section('content')
<p style="font-size:.875rem;color:#64748b;margin-bottom:16px;">Enter your email address and we'll send you a one-time password to reset your account.</p>

<form method="POST" action="{{ route('password.send-otp') }}">
    @csrf
    <div class="form-group">
        <label class="form-label">Email Address</label>
        <div class="input-wrap">
            <i class="fas fa-envelope input-icon"></i>
            <input type="email" name="email" class="form-control" value="{{ old('email') }}" placeholder="your@email.com" required>
        </div>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> &nbsp;Send OTP</button>
</form>

<div style="margin-top:14px;text-align:center;">
    <a href="{{ route('login') }}" class="link"><i class="fas fa-arrow-left"></i> Back to Login</a>
</div>
@endsection
