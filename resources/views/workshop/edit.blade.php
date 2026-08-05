@extends('layouts.app')
@section('title', 'Update Job: '.$workshop->entry_job_number)

@section('content')
<div class="page-header">
    <h2><i class="fas fa-edit"></i> Update Job: {{ $workshop->entry_job_number }}</h2>
    <a href="{{ route('workshop.show', $workshop) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('workshop.update', $workshop) }}">
            @csrf @method('PUT')
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;font-size:.82rem;color:#1e40af;margin-bottom:14px;">
                <i class="fas fa-info-circle"></i> Every device starts <strong>Pending</strong>. Each device below is tracked independently — e.g. one device can be marked <strong>Collected</strong> while others on this same job are still <strong>In Progress</strong>.
            </div>

            <h4 style="font-size:.9rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:14px;"><i class="fas fa-laptop"></i> Devices</h4>

            @foreach($workshop->devices as $i => $device)
            <div class="device-update-row" style="border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:14px;">
                <div style="font-size:.78rem;font-weight:700;color:#2563eb;text-transform:uppercase;margin-bottom:10px;">
                    Device {{ $i + 1 }}{{ $i === 0 ? ' (Primary)' : '' }} — {{ $device->equipment_type }}
                    @if($device->serial_number_asset_tag)<span style="color:#94a3b8;font-weight:400;text-transform:none;">&mdash; {{ $device->serial_number_asset_tag }}</span>@endif
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Status</label>
                        <select name="devices[{{ $device->id }}][status]" class="form-control device-status-select" data-device-id="{{ $device->id }}" required>
                            @foreach(['Pending','In Progress','Completed','Collected'] as $s)
                            <option value="{{ $s }}" {{ old('devices.'.$device->id.'.status', $device->status) === $s ? 'selected' : '' }}>{{ $s }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Date Repair Completed</label>
                        <input type="date" name="devices[{{ $device->id }}][date_repair_completed]" class="form-control" value="{{ old('devices.'.$device->id.'.date_repair_completed', $device->date_repair_completed?->format('Y-m-d')) }}">
                    </div>
                </div>

                <div class="form-group">
                    <label class="form-label device-repair-label">Repair Action Taken</label>
                    <textarea name="devices[{{ $device->id }}][repair_action_taken]" class="form-control device-repair-action @error('devices.'.$device->id.'.repair_action_taken') is-invalid @enderror" rows="3">{{ old('devices.'.$device->id.'.repair_action_taken', $device->repair_action_taken) }}</textarea>
                    @error('devices.'.$device->id.'.repair_action_taken')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>

                <div class="device-collection-fields" style="{{ $device->status === 'Collected' ? '' : 'display:none;' }}">
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Date Collected</label>
                            <input type="date" name="devices[{{ $device->id }}][date_collected]" class="form-control device-date-collected" value="{{ old('devices.'.$device->id.'.date_collected', $device->date_collected?->format('Y-m-d')) }}">
                        </div>
                        <div class="form-group">
                            <label class="form-label">Collected By (Name)</label>
                            <input type="text" name="devices[{{ $device->id }}][collector_name]" class="form-control device-collector-name" value="{{ old('devices.'.$device->id.'.collector_name', $device->collector_name) }}" placeholder="Full name of person collecting">
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="form-label">Collector Signature (Digital)</label>
                        <div class="sig-pad-wrap">
                            <canvas class="device-sig-pad" width="600" height="150"></canvas>
                        </div>
                        <input type="hidden" name="devices[{{ $device->id }}][collector_signature]" class="device-sig-data" value="{{ $device->collector_signature }}">
                        <button type="button" class="btn btn-sm btn-secondary mt-1 device-sig-clear"><i class="fas fa-eraser"></i> Clear</button>
                    </div>
                </div>
            </div>
            @endforeach

            <hr class="divider">
            <h4 style="font-size:.9rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:14px;">Job-Level Details</h4>
            <div class="form-row">
                @if(auth()->user()->isAdminOrHod())
                <div class="form-group">
                    <label class="form-label">Assign Technician</label>
                    <select name="technician_assigned" class="form-control">
                        <option value="">— Unassigned —</option>
                        @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}" {{ $workshop->technician_assigned == $tech->id ? 'selected' : '' }}>
                            {{ $tech->firstname }} {{ $tech->lastname }}
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="form-group">
                    <label class="form-label">Time Taken — Value</label>
                    <select name="time_taken_value" class="form-control">
                        <option value="">—</option>
                        @for($i=1; $i<=60; $i++)
                        <option value="{{ $i }}" {{ $workshop->time_taken_value == $i ? 'selected' : '' }}>{{ $i }}</option>
                        @endfor
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Time Taken — Unit</label>
                    <select name="time_taken_unit" class="form-control">
                        <option value="">—</option>
                        @foreach(['Minutes','Hours','Days','Weeks','Months'] as $u)
                        <option value="{{ $u }}" {{ $workshop->time_taken_unit === $u ? 'selected' : '' }}>{{ $u }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Outgoing Cross Ref / Form 208</label>
                    <input type="text" name="cross_ref_form208_outgoing" class="form-control" value="{{ old('cross_ref_form208_outgoing', $workshop->cross_ref_form208_outgoing) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Final Depot <span style="color:#94a3b8;font-weight:400;">(if heading elsewhere after repair)</span></label>
                    <input type="text" name="final_depot" class="form-control @error('final_depot') is-invalid @enderror" value="{{ old('final_depot', $workshop->final_depot) }}" placeholder="Leave blank to return to {{ $workshop->depot_name }}" list="depot-suggestions" autocomplete="off">
                    @error('final_depot')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <datalist id="depot-suggestions">
                    @foreach($depots as $depot)
                    <option value="{{ $depot }}">
                    @endforeach
                </datalist>
            </div>

            <div class="form-group">
                <label class="form-label">Remarks / Comments</label>
                <textarea name="remarks_comments" class="form-control">{{ old('remarks_comments', $workshop->remarks_comments) }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Update</button>
            <a href="{{ route('workshop.show', $workshop) }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>

@push('scripts')
<script>
document.querySelectorAll('.device-update-row').forEach(function (row) {
    const statusSelect  = row.querySelector('.device-status-select');
    const repairAction  = row.querySelector('.device-repair-action');
    const repairLabel   = row.querySelector('.device-repair-label');
    const dateCollected = row.querySelector('.device-date-collected');
    const collectorName = row.querySelector('.device-collector-name');
    const canvas         = row.querySelector('.device-sig-pad');
    const sigDataInput   = row.querySelector('.device-sig-data');
    const clearBtn        = row.querySelector('.device-sig-clear');
    const collectionFields = row.querySelector('.device-collection-fields');

    // Repair Action Taken becomes required once this device is Completed or Collected
    function toggleRepairActionRequired() {
        const isRequired = statusSelect.value === 'Completed' || statusSelect.value === 'Collected';
        repairAction.required = isRequired;
        repairLabel.textContent = isRequired ? 'Repair Action Taken *' : 'Repair Action Taken';
    }

    // Date Collected / Collected By / Signature only make sense once this device is Collected
    function toggleCollectionFields() {
        collectionFields.style.display = statusSelect.value === 'Collected' ? '' : 'none';
    }

    // Auto-promote this device's status to Collected when both collection fields are filled
    function checkCollectionStatus() {
        const dateVal = dateCollected.value.trim();
        const nameVal = collectorName.value.trim();
        if (dateVal && nameVal) {
            statusSelect.value = 'Collected';
            statusSelect.style.borderColor = '#7c3aed';
            statusSelect.style.background  = '#ede9fe';
        } else if (statusSelect.value === 'Collected' && (!dateVal || !nameVal)) {
            statusSelect.style.borderColor = '';
            statusSelect.style.background  = '';
        }
        toggleRepairActionRequired();
    }

    statusSelect.addEventListener('change', toggleRepairActionRequired);
    statusSelect.addEventListener('change', toggleCollectionFields);
    dateCollected.addEventListener('change', checkCollectionStatus);
    collectorName.addEventListener('input',  checkCollectionStatus);
    checkCollectionStatus();
    toggleCollectionFields();

    // Signature pad
    const ctx = canvas.getContext('2d');
    let drawing = false, lastX = 0, lastY = 0;

    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        const touch = e.touches ? e.touches[0] : e;
        return [touch.clientX - rect.left, touch.clientY - rect.top];
    }
    canvas.addEventListener('mousedown', e => { drawing = true; [lastX, lastY] = getPos(e); });
    canvas.addEventListener('mousemove', e => {
        if (!drawing) return;
        const [x, y] = getPos(e);
        ctx.beginPath(); ctx.moveTo(lastX, lastY); ctx.lineTo(x, y);
        ctx.strokeStyle = '#1e293b'; ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.stroke();
        [lastX, lastY] = [x, y];
        sigDataInput.value = canvas.toDataURL();
    });
    canvas.addEventListener('mouseup', () => drawing = false);
    canvas.addEventListener('mouseleave', () => drawing = false);
    canvas.addEventListener('touchstart', e => { e.preventDefault(); drawing = true; [lastX, lastY] = getPos(e); });
    canvas.addEventListener('touchmove', e => {
        e.preventDefault(); if (!drawing) return;
        const [x, y] = getPos(e);
        ctx.beginPath(); ctx.moveTo(lastX, lastY); ctx.lineTo(x, y);
        ctx.strokeStyle = '#1e293b'; ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.stroke();
        [lastX, lastY] = [x, y];
        sigDataInput.value = canvas.toDataURL();
    });
    canvas.addEventListener('touchend', () => drawing = false);

    clearBtn.addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        sigDataInput.value = '';
    });

    if (sigDataInput.value && sigDataInput.value.startsWith('data:')) {
        const img = new Image();
        img.onload = () => ctx.drawImage(img, 0, 0);
        img.src = sigDataInput.value;
    }
});
</script>
@endpush
@endsection
