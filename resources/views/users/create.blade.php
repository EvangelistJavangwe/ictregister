@extends('layouts.app')
@section('title', 'Create User')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-user-plus"></i> Create New User</h2>
    <a href="{{ route('users.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('users.store') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">First Name *</label>
                    <input type="text" name="firstname" class="form-control @error('firstname') is-invalid @enderror" value="{{ old('firstname') }}" required>
                    @error('firstname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Last Name *</label>
                    <input type="text" name="lastname" class="form-control @error('lastname') is-invalid @enderror" value="{{ old('lastname') }}" required>
                    @error('lastname')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Employee Number *</label>
                    <input type="text" name="employee_number" class="form-control @error('employee_number') is-invalid @enderror" value="{{ old('employee_number') }}" placeholder="EMP001" required>
                    @error('employee_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Email Address *</label>
                    <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
                    @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone" class="form-control" value="{{ old('phone') }}" placeholder="+263...">
                </div>
                <div class="form-group">
                    <label class="form-label">Role *</label>
                    <select name="role" id="role-select" class="form-control" required onchange="updateDesignations()">
                        <option value="">— Select Role —</option>
                        @foreach($availableRoles as $role)
                        <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$role)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group" id="designation-group" style="display:none;">
                    <label class="form-label">Designation</label>
                    <select name="designation" id="designation-select" class="form-control">
                        <option value="">— Select Designation —</option>
                    </select>
                </div>
            </div>

            <hr class="divider">
            <div class="alert alert-info" style="margin-bottom:16px;">
                <i class="fas fa-info-circle"></i>
                A username (GMB-EMP-XXXX) and temporary password will be auto-generated and emailed to the user.
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create User</button>
            <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

@push('scripts')
<script>
const hodDesignations = @json($hodDesignations);
const techDesignations = @json($techDesignations);

function updateDesignations() {
    const role = document.getElementById('role-select').value;
    const group = document.getElementById('designation-group');
    const select = document.getElementById('designation-select');
    select.innerHTML = '<option value="">— Select Designation —</option>';

    let options = [];
    if (role === 'hod') options = hodDesignations;
    else if (role === 'technician') options = techDesignations;

    if (options.length) {
        group.style.display = 'block';
        options.forEach(d => {
            select.innerHTML += `<option value="${d}">${d}</option>`;
        });
    } else {
        group.style.display = 'none';
    }
}
// Init on load
updateDesignations();
</script>
@endpush
@endsection
