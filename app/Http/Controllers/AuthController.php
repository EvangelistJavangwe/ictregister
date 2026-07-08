<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\OtpToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rules\Password;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use PragmaRX\Google2FA\Google2FA;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $user = User::where('username', $request->username)
            ->orWhere('email', $request->username)
            ->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            AuditTrail::log('login_failed', 'Auth', "Failed login attempt for: {$request->username}", 'failed');
            return back()->withErrors(['username' => 'Invalid credentials.'])->withInput();
        }

        if ($user->is_blocked) {
            AuditTrail::log('login_blocked', 'Auth', "Blocked user attempted login: {$user->username}", 'warning');
            return back()->withErrors(['username' => 'Your account has been blocked. Contact your administrator.']);
        }

        Auth::login($user, $request->boolean('remember'));

        AuditTrail::log('login', 'Auth', "User {$user->username} logged in", 'success');

        if ($user->mfa_enabled) {
            session(['mfa_verified' => false]);
            return redirect()->route('mfa.verify');
        }

        session(['mfa_verified' => true]);

        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        $username = auth()->user()?->username ?? 'Unknown';
        AuditTrail::log('logout', 'Auth', "User {$username} logged out", 'info');
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    }

    public function showMfaSetup()
    {
        $user = auth()->user();
        if ($user->mfa_enabled) {
            return redirect()->route('dashboard');
        }

        $google2fa = new Google2FA();
        $secret = $google2fa->generateSecretKey();
        session(['mfa_setup_secret' => $secret]);

        $qrCodeUrl = $google2fa->getQRCodeUrl(
            config('app.name'),
            $user->email,
            $secret
        );

        $renderer = new ImageRenderer(
            new RendererStyle(200),
            new SvgImageBackEnd()
        );
        $writer = new Writer($renderer);
        $qrSvg = $writer->writeString($qrCodeUrl);
        $qrCodeDataUri = 'data:image/svg+xml;base64,' . base64_encode($qrSvg);

        return view('auth.mfa-setup', compact('secret', 'qrCodeDataUri'));
    }

    public function enableMfa(Request $request)
    {
        $request->validate(['otp_code' => 'required|string|size:6']);

        $secret = session('mfa_setup_secret');
        if (!$secret) {
            return back()->withErrors(['otp_code' => 'Session expired. Please try again.']);
        }

        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($secret, $request->otp_code);

        if (!$valid) {
            return back()->withErrors(['otp_code' => 'Invalid OTP code. Please try again.']);
        }

        auth()->user()->update([
            'google2fa_secret' => $secret,
            'mfa_enabled' => true,
        ]);

        session()->forget('mfa_setup_secret');
        session(['mfa_verified' => true]);

        AuditTrail::log('mfa_enabled', 'Auth', "User " . auth()->user()->username . " enabled MFA", 'success');

        return redirect()->route('dashboard')->with('success', 'Two-factor authentication enabled successfully.');
    }

    public function showMfaVerify()
    {
        if (!auth()->check()) return redirect()->route('login');
        if (session('mfa_verified')) return redirect()->route('dashboard');
        return view('auth.mfa-verify');
    }

    public function verifyMfa(Request $request)
    {
        $request->validate(['otp_code' => 'required|string|size:6']);

        $user = auth()->user();
        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($user->google2fa_secret, $request->otp_code);

        if (!$valid) {
            AuditTrail::log('mfa_failed', 'Auth', "MFA failed for user: {$user->username}", 'failed');
            return back()->withErrors(['otp_code' => 'Invalid OTP code.']);
        }

        session(['mfa_verified' => true]);
        AuditTrail::log('mfa_verified', 'Auth', "MFA verified for user: {$user->username}", 'success');

        return redirect()->intended(route('dashboard'));
    }

    public function disableMfa()
    {
        auth()->user()->update(['mfa_enabled' => false, 'google2fa_secret' => null]);
        AuditTrail::log('mfa_disabled', 'Auth', "User " . auth()->user()->username . " disabled MFA", 'warning');
        return back()->with('success', 'Two-factor authentication disabled.');
    }

    public function showChangePassword()
    {
        return view('auth.change-password');
    }

    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'password'         => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = auth()->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        AuditTrail::log('password_changed', 'Auth', "User {$user->username} changed their password", 'success');

        return redirect()->route('dashboard')->with('success', 'Password changed successfully.');
    }

    public function showForgotPassword()
    {
        return view('auth.forgot-password');
    }

    public function sendOtp(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();
        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpToken::where('user_id', $user->id)->where('type', 'password_reset')->delete();

        OtpToken::create([
            'user_id' => $user->id,
            'token' => $otp,
            'type' => 'password_reset',
            'expires_at' => now()->addMinutes(15),
            'ip_address' => $request->ip(),
        ]);

        try {
            Mail::raw("Your password reset OTP is: {$otp}\n\nThis OTP expires in 15 minutes.\n\nDo not share this with anyone.", function ($message) use ($user) {
                $message->to($user->email)->subject('Password Reset OTP - ICT Register');
            });
        } catch (\Exception $e) {
            // Log but don't expose mail errors
        }

        AuditTrail::log('otp_sent', 'Auth', "Password reset OTP sent to {$user->email}", 'info');

        return redirect()->route('password.reset.otp')
            ->with(['otp_email' => $user->email, 'success' => 'OTP sent to your email address.']);
    }

    public function showResetWithOtp()
    {
        return view('auth.reset-password');
    }

    public function resetWithOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|string',
            'password' => ['required', 'confirmed', Password::min(8)->mixedCase()->numbers()->symbols()],
        ]);

        $user = User::where('email', $request->email)->first();
        $otpRecord = OtpToken::where('user_id', $user->id)
            ->where('token', $request->otp)
            ->where('type', 'password_reset')
            ->whereNull('used_at')
            ->where('expires_at', '>', now())
            ->first();

        if (!$otpRecord) {
            return back()->withErrors(['otp' => 'Invalid or expired OTP.']);
        }

        $otpRecord->update(['used_at' => now()]);
        $user->update([
            'password' => Hash::make($request->password),
            'must_change_password' => false,
        ]);

        AuditTrail::log('password_reset', 'Auth', "Password reset for user: {$user->username}", 'success');

        return redirect()->route('login')->with('success', 'Password reset successfully. You can now log in.');
    }
}
