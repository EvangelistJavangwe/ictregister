@extends('layouts.auth')
@section('title', 'Login')
@section('subtitle', 'Sign in to your account')

@section('content')
<form method="POST" action="{{ route('login') }}">
    @csrf
    <div class="form-group">
        <label class="form-label">Username or Email</label>
        <div class="input-wrap">
            <i class="fas fa-user input-icon"></i>
            <input type="text" name="username" class="form-control" value="{{ old('username') }}" placeholder="GMB-EMP-0001 or email" required autofocus>
        </div>
    </div>
    <div class="form-group">
        <label class="form-label">Password</label>
        <div class="input-wrap">
            <i class="fas fa-lock input-icon"></i>
            <input type="password" name="password" class="form-control" placeholder="••••••••" required>
        </div>
    </div>
    <div class="form-group" style="display:flex;align-items:center;justify-content:space-between;">
        <label style="display:flex;align-items:center;gap:6px;font-size:.85rem;cursor:pointer;">
            <input type="checkbox" name="remember"> Remember me
        </label>
        <a href="{{ route('password.forgot') }}" class="link">Forgot password?</a>
    </div>
    <button type="submit" class="btn btn-primary"><i class="fas fa-sign-in-alt"></i> &nbsp;Sign In</button>
</form>

<div class="auth-divider"><span>or</span></div>

<a href="{{ route('auth.microsoft.redirect') }}" class="btn btn-microsoft">
    <i class="fa-brands fa-microsoft"></i> &nbsp;Sign in with Microsoft
</a>
@endsection
