<?php

namespace App\Http\Controllers;

use App\Models\AuditTrail;
use App\Models\OtpToken;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    const HOD_DESIGNATIONS = [
        'Computer Systems Manager',
        'Analyst Programmer',
        'Principal Systems Administrator',
        'Helpdesk and Systems Administrator',
        'Hardware and Network Administrator',
        'Cyber Security Analyst',
        'Auditor',
    ];

    const TECH_DESIGNATIONS = [
        'Computer Systems Technician',
        'Programmer',
    ];

    public function index()
    {
        $auth = auth()->user();
        $query = User::where('id', '!=', $auth->id);

        if ($auth->isHod()) {
            // HODs cannot see super admin
            $query->where('role', '!=', 'super_admin');
        }

        $users = $query->latest()->paginate(15);
        return view('users.index', compact('users'));
    }

    public function create()
    {
        $auth = auth()->user();
        $hodDesignations = self::HOD_DESIGNATIONS;
        $techDesignations = self::TECH_DESIGNATIONS;

        $availableRoles = ['hod', 'technician'];
        if ($auth->isSuperAdmin()) {
            $availableRoles[] = 'super_admin';
        }

        return view('users.create', compact('hodDesignations', 'techDesignations', 'availableRoles'));
    }

    public function store(Request $request)
    {
        $auth = auth()->user();

        $request->validate([
            'firstname'       => 'required|string|max:100',
            'lastname'        => 'required|string|max:100',
            'employee_number' => 'required|string|unique:users,employee_number',
            'email'           => 'required|email|unique:users,email',
            'phone'           => 'nullable|string|max:20',
            'role'            => ['required', Rule::in(['super_admin', 'hod', 'technician'])],
            'designation'     => 'nullable|string|max:100',
        ]);

        // HODs can only create hod/technician
        if ($auth->isHod() && $request->role === 'super_admin') {
            abort(403);
        }

        $username = $this->generateUsername();
        $tempPassword = $this->generateTempPassword();

        $user = User::create([
            'username'        => $username,
            'firstname'       => $request->firstname,
            'lastname'        => $request->lastname,
            'employee_number' => $request->employee_number,
            'email'           => $request->email,
            'phone'           => $request->phone,
            'role'            => $request->role,
            'designation'     => $request->designation,
            'password'        => Hash::make($tempPassword),
            'must_change_password' => true,
            'created_by'      => $auth->id,
        ]);

        try {
            Mail::raw(
                "Welcome to ICT Register System!\n\nYour account has been created.\n\nUsername: {$username}\nTemporary Password: {$tempPassword}\n\nPlease login and change your password immediately.",
                function ($message) use ($user) {
                    $message->to($user->email)->subject('Your ICT Register Account - ' . config('app.name'));
                }
            );
        } catch (\Exception $e) {
            // Silently fail email send
        }

        AuditTrail::log('create_user', 'Users', "Created user: {$username} with role: {$user->role}", 'success', $user->id);

        return redirect()->route('users.index')
            ->with('success', "User {$username} created. Temporary password: {$tempPassword}");
    }

    public function show(User $user)
    {
        $this->authorizeAccess($user);
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        $this->authorizeAccess($user);
        $hodDesignations = self::HOD_DESIGNATIONS;
        $techDesignations = self::TECH_DESIGNATIONS;
        return view('users.edit', compact('user', 'hodDesignations', 'techDesignations'));
    }

    public function update(Request $request, User $user)
    {
        $this->authorizeAccess($user);

        $request->validate([
            'firstname'   => 'required|string|max:100',
            'lastname'    => 'required|string|max:100',
            'email'       => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'phone'       => 'nullable|string|max:20',
            'designation' => 'nullable|string|max:100',
        ]);

        $user->update($request->only('firstname', 'lastname', 'email', 'phone', 'designation'));

        AuditTrail::log('update_user', 'Users', "Updated user: {$user->username}", 'success', $user->id);

        return redirect()->route('users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user)
    {
        // Only super admin can delete
        if (!auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot delete your own account.']);
        }

        $username = $user->username;
        $user->delete();

        AuditTrail::log('delete_user', 'Users', "Deleted user: {$username}", 'warning');

        return redirect()->route('users.index')->with('success', "User {$username} deleted.");
    }

    public function block(User $user)
    {
        $this->authorizeAccess($user);

        if ($user->id === auth()->id()) {
            return back()->withErrors(['error' => 'You cannot block your own account.']);
        }

        $user->update(['is_blocked' => !$user->is_blocked]);
        $action = $user->is_blocked ? 'blocked' : 'unblocked';

        AuditTrail::log("user_{$action}", 'Users', "User {$user->username} was {$action}", 'warning', $user->id);

        return back()->with('success', "User {$user->username} has been {$action}.");
    }

    public function resetPassword(Request $request, User $user)
    {
        $auth = auth()->user();

        // HODs can reset for HODs and Technicians; Super Admin can reset anyone
        if ($auth->isHod() && $user->isSuperAdmin()) {
            abort(403);
        }

        $otp = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        OtpToken::where('user_id', $user->id)->where('type', 'password_reset')->delete();

        OtpToken::create([
            'user_id'    => $user->id,
            'token'      => $otp,
            'type'       => 'password_reset',
            'expires_at' => now()->addHour(),
            'ip_address' => $request->ip(),
        ]);

        try {
            Mail::raw(
                "Your password has been reset by an administrator.\n\nYour one-time OTP: {$otp}\n\nUse this OTP to set your new password. It expires in 1 hour.",
                function ($message) use ($user) {
                    $message->to($user->email)->subject('Password Reset OTP - ICT Register');
                }
            );
        } catch (\Exception $e) {
            // Silently fail
        }

        AuditTrail::log('admin_reset_password', 'Users', "Password reset initiated for: {$user->username} by {$auth->username}", 'warning', $user->id);

        return back()->with('success', "OTP sent to {$user->email}. OTP: {$otp}");
    }

    private function authorizeAccess(User $user): void
    {
        $auth = auth()->user();
        if ($auth->isHod() && $user->isSuperAdmin()) {
            abort(403);
        }
    }

    private function generateUsername(): string
    {
        $last = User::where('username', 'like', 'GMB-EMP-%')
            ->orderByDesc('id')->first();
        $seq = $last ? ((int) substr($last->username, -4)) + 1 : 1;
        return 'GMB-EMP-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
    }

    private function generateTempPassword(): string
    {
        return 'ICT@' . str_pad(random_int(0, 9999), 4, '0', STR_PAD_LEFT);
    }
}
