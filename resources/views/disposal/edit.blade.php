@extends('layouts.app')
@section('title', 'Edit Disposal')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-edit"></i> Edit Disposal</h2>
    <a href="{{ route('disposal.show', $disposal) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>
<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('disposal.update', $disposal) }}">
            @csrf @method('PUT')
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Asset Description *</label>
                    <input type="text" name="asset_description" class="form-control" value="{{ old('asset_description', $disposal->asset_description) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Asset Tag / Serial No.</label>
                    <input type="text" name="asset_tag_serial_no" class="form-control" value="{{ old('asset_tag_serial_no', $disposal->asset_tag_serial_no) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Model / Brand</label>
                    <input type="text" name="model_brand" class="form-control" value="{{ old('model_brand', $disposal->model_brand) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Department / User</label>
                    <input type="text" name="department_user" class="form-control" value="{{ old('department_user', $disposal->department_user) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Condition at Disposal</label>
                    <input type="text" name="condition_at_disposal" class="form-control" value="{{ old('condition_at_disposal', $disposal->condition_at_disposal) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Disposal Method</label>
                    <select name="disposal_method" class="form-control">
                        <option value="">—</option>
                        @foreach(['Auction','Donation','Destruction','Write-Off','Trade-In','Recycling'] as $m)
                        <option value="{{ $m }}" {{ $disposal->disposal_method === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Disposal Value (USD)</label>
                    <input type="number" name="disposal_value" class="form-control" value="{{ old('disposal_value', $disposal->disposal_value) }}" step="0.01" min="0">
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Reason for Disposal *</label>
                <textarea name="reason_for_disposal" class="form-control" required>{{ old('reason_for_disposal', $disposal->reason_for_disposal) }}</textarea>
            </div>
            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="data_wiped_destroyed" value="1" {{ $disposal->data_wiped_destroyed ? 'checked' : '' }}>
                    <span class="form-label" style="margin:0;">Data Wiped / Destroyed</span>
                </label>
            </div>
            <div class="form-group">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control">{{ old('remarks', $disposal->remarks) }}</textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
            <a href="{{ route('disposal.show', $disposal) }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@endsection
