@extends('layouts.app')
@section('title', 'Edit User')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-user-edit"></i> Edit User — {{ $user->username }}</h2>
    <a href="{{ route('users.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('users.update', $user) }}">
            @csrf @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Username</label>
                    <input type="text" class="form-control" value="{{ $user->username }}" disabled style="background:#f8fafc;">
                </div>
                <div class="form-group">
                    <label class="form-label">Role</label>
                    @if($canEditRole)
                    <select name="role" id="role-select" class="form-control @error('role') is-invalid @enderror" onchange="updateDesignations()">
                        @foreach(['super_admin', 'hod', 'technician'] as $role)
                        <option value="{{ $role }}" {{ old('role', $user->role) === $role ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$role)) }}</option>
                        @endforeach
                    </select>
                    @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    @else
                    <input type="text" class="form-control" value="{{ ucfirst(str_replace('_',' ',$user->role)) }}" disabled style="background:#f8fafc;">
                    @endif
                </div>
                <div class="form-group">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="firstname" class="form-control @error('firstname') is-invalid @enderror" value="{{ old('firstname', $user->firstname) }}" required>
                    @error('firstname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name *</label>
                    <input type="text" name="lastname" class="form-control @error('lastname') is-invalid @enderror" value="{{ old('lastname', $user->lastname) }}" required>
                    @error('lastname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Email *</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Phone *</label>
                    <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" value="{{ old('phone', $user->phone) }}" required>
                    @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" id="designation-group">
                    <label class="form-label">Designation</label>
                    <select name="designation" id="designation-select" class="form-control">
                        <option value="">— None —</option>
                        @foreach($user->role === 'hod' ? $hodDesignations : $techDesignations as $d)
                        <option value="{{ $d }}" {{ old('designation', $user->designation) === $d ? 'selected' : '' }}>{{ $d }}</option>
                        @endforeach
                    </select>
                </div>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

@if($canEditRole)
@push('scripts')
<script>
const hodDesignations = @json($hodDesignations);
const techDesignations = @json($techDesignations);
const currentDesignation = @json(old('designation', $user->designation));

function updateDesignations() {
    const role = document.getElementById('role-select').value;
    const group = document.getElementById('designation-group');
    const select = document.getElementById('designation-select');
    const previousValue = select.value || currentDesignation;
    select.innerHTML = '<option value="">— None —</option>';

    let options = [];
    if (role === 'hod') options = hodDesignations;
    else if (role === 'technician') options = techDesignations;

    if (options.length) {
        group.style.display = 'block';
        options.forEach(d => {
            const selected = d === previousValue ? 'selected' : '';
            select.innerHTML += `<option value="${d}" ${selected}>${d}</option>`;
        });
    } else {
        group.style.display = 'none';
    }
}
</script>
@endpush
@endif
@endsection
