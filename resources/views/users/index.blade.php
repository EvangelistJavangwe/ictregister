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
        <table class="responsive-table">
            <thead>
                <tr>
                    <th>Username</th><th>Name</th><th>Employee No.</th><th>Email</th><th>Role</th><th>Designation</th><th>Status</th><th>2FA</th><th>Actions</th>
                </tr>
            </thead>
            <tbody>
            @forelse($users as $user)
            <tr>
                <td data-label="Username"><strong>{{ $user->username }}</strong></td>
                <td data-label="Name">{{ $user->firstname }} {{ $user->lastname }}</td>
                <td data-label="Employee No.">{{ $user->employee_number }}</td>
                <td class="text-sm text-truncate" data-label="Email" title="{{ $user->email }}">{{ $user->email }}</td>
                <td data-label="Role">
                    <span class="badge role-{{ $user->role }}">
                        {{ ucfirst(str_replace('_',' ',$user->role)) }}
                    </span>
                </td>
                <td class="text-sm" data-label="Designation">{{ $user->designation ?? '—' }}</td>
                <td data-label="Status">
                    @if($user->is_blocked)
                        <span class="badge badge-danger">Blocked</span>
                    @else
                        <span class="badge badge-success">Active</span>
                    @endif
                </td>
                <td data-label="2FA">
                    @if($user->mfa_enabled)
                        <span class="badge badge-success"><i class="fas fa-check"></i></span>
                    @else
                        <span class="badge badge-secondary">Off</span>
                    @endif
                </td>
                <td data-label="Actions">
                    <div class="d-flex gap-2">
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-secondary" title="Edit" aria-label="Edit {{ $user->username }}"><i class="fas fa-edit"></i></a>
                        <form method="POST" action="{{ route('users.block', $user) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm {{ $user->is_blocked ? 'btn-success' : 'btn-warning' }}" title="{{ $user->is_blocked ? 'Unblock' : 'Block' }}" aria-label="{{ $user->is_blocked ? 'Unblock' : 'Block' }} {{ $user->username }}" data-confirm="Are you sure?">
                                <i class="fas fa-{{ $user->is_blocked ? 'unlock' : 'ban' }}"></i>
                            </button>
                        </form>
                        <form method="POST" action="{{ route('users.reset-password', $user) }}" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-info" title="Reset Password" aria-label="Reset password for {{ $user->username }}" data-confirm="Send OTP to {{ $user->email }}?">
                                <i class="fas fa-key"></i>
                            </button>
                        </form>
                        @if(auth()->user()->isSuperAdmin())
                        <form method="POST" action="{{ route('users.destroy', $user) }}" style="display:inline;">
                            @csrf @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-danger" title="Delete" aria-label="Delete {{ $user->username }}" data-confirm="PERMANENTLY delete {{ $user->username }}? This cannot be undone.">
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
