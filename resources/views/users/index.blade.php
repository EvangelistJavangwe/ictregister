@extends('layouts.app')
@section('title', 'User Management')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-users"></i> User Management</h2>
    <a href="{{ route('users.create') }}" class="btn btn-primary"><i class="fas fa-user-plus"></i> Add User</a>
</div>

<div class="card">
    <div class="card-header">
        <h3>System Users ({{ $users->total() }})</h3>
    </div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Username</th><th>Name</th><th>Employee No.</th><th>Email</th><th>Role</th><th>Designation</th><th>Status</th><th>2FA</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
            <tr>
                <td><strong>{{ $user->username }}</strong></td>
                <td>{{ $user->firstname }} {{ $user->lastname }}</td>
                <td>{{ $user->employee_number }}</td>
                <td class="text-sm">{{ $user->email }}</td>
                <td>
                    <span class="badge role-{{ $user->role }}" style="background:{{ $user->role==='super_admin'?'#7c3aed':($user->role==='hod'?'#0284c7':'#16a34a') }};color:#fff;">
                        {{ ucfirst(str_replace('_',' ',$user->role)) }}
                    </span>
                </td>
                <td class="text-sm">{{ $user->designation ?? '—' }}</td>
                <td>
                    @if($user->is_blocked)
                        <span class="badge badge-danger">Blocked</span>
                    @else
                        <span class="badge badge-success">Active</span>
                    @endif
                </td>
                <td>
                    @if($user->mfa_enabled)
                        <span class="badge badge-success"><i class="fas fa-check"></i></span>
                    @else
                        <span class="badge badge-secondary">Off</span>
                    @endif
                </td>
                <td>
                    <div class="d-flex gap-2">
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-secondary"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('users.block', $user) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $user->is_blocked ? 'btn-success' : 'btn-warning' }}" title="{{ $user->is_blocked ? 'Unblock' : 'Block' }}" data-confirm="Are you sure?">
                                <i class="fas fa-{{ $user->is_blocked ? 'unlock' : 'ban' }}"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('users.reset-password', $user) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-info" title="Reset Password" data-confirm="Send OTP to {{ $user->email }}?" style="background:#0284c7;color:#fff;">
                                <i class="fas fa-key"></i>
                            </button>
                        </form>
                        @if(auth()->user()->isSuperAdmin())
                        <form method="POST" action="{{ route('users.destroy', $user) }}" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" data-confirm="PERMANENTLY delete {{ $user->username }}? This cannot be undone.">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endif
                    </div>
                </td>
            </tr>
            @empty
            <tr><td colspan="9" class="text-center text-muted" style="padding:30px;">No users found.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-body">{{ $users->links() }}</div>
</div>
@endsection
