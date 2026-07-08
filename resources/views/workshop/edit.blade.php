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
                <i class="fas fa-info-circle"></i> Every job starts <strong>Pending</strong>. The technician handling it must update the Status below to <strong>In Progress</strong> once work begins.
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Status</label>
                    <select name="status" class="form-control" required>
                        @foreach(['Pending','In Progress','Completed','Collected'] as $s)
                        <option value="{{ $s }}" {{ $workshop->status === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                </div>
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
                    <label class="form-label">Date Repair Completed</label>
                    <input type="date" name="date_repair_completed" class="form-control" value="{{ $workshop->date_repair_completed?->format('Y-m-d') }}">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Repair Action Taken</label>
                <textarea name="repair_action_taken" class="form-control" rows="4">{{ old('repair_action_taken', $workshop->repair_action_taken) }}</textarea>
            </div>

            <div class="form-row">
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
            </div>

            <hr class="divider">
            <h4 style="font-size:.9rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:14px;">Collection Details</h4>
            <div style="background:#eff6ff;border:1px solid #bfdbfe;border-radius:8px;padding:10px 14px;font-size:.82rem;color:#1e40af;margin-bottom:14px;">
                <i class="fas fa-info-circle"></i> Filling in both <strong>Date Collected</strong> and <strong>Collected By</strong> will automatically move the status to <strong>Collected</strong>.
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Date Collected</label>
                    <input type="date" name="date_collected" id="date_collected" class="form-control" value="{{ $workshop->date_collected?->format('Y-m-d') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Collected By (Name)</label>
                    <input type="text" name="collector_name" id="collector_name" class="form-control" value="{{ old('collector_name', $workshop->collector_name) }}" placeholder="Full name of person collecting">
                </div>
            </div>

            <div class="form-group">
                <label class="form-label">Collector Signature (Digital)</label>
                <div class="sig-pad-wrap">
                    <canvas id="signature-pad" width="600" height="150"></canvas>
                </div>
                <input type="hidden" name="collector_signature" id="sig-data" value="{{ $workshop->collector_signature }}">
                <button type="button" onclick="clearSig()" class="btn btn-sm btn-secondary mt-1"><i class="fas fa-eraser"></i> Clear</button>
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
// Auto-promote status to Collected when both collection fields are filled
function checkCollectionStatus() {
    const dateVal = document.getElementById('date_collected').value.trim();
    const nameVal = document.getElementById('collector_name').value.trim();
    const statusSelect = document.querySelector('select[name="status"]');
    if (dateVal && nameVal) {
        statusSelect.value = 'Collected';
        statusSelect.style.borderColor = '#7c3aed';
        statusSelect.style.background  = '#ede9fe';
    } else if (statusSelect.value === 'Collected' && (!dateVal || !nameVal)) {
        // only revert if user cleared the fields
        statusSelect.style.borderColor = '';
        statusSelect.style.background  = '';
    }
}
document.getElementById('date_collected').addEventListener('change', checkCollectionStatus);
document.getElementById('collector_name').addEventListener('input',  checkCollectionStatus);
// Run once on page load in case record already has collection details
checkCollectionStatus();

const canvas = document.getElementById('signature-pad');
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
    document.getElementById('sig-data').value = canvas.toDataURL();
});
canvas.addEventListener('mouseup', () => drawing = false);
canvas.addEventListener('mouseleave', () => drawing = false);

function clearSig() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    document.getElementById('sig-data').value = '';
}

// Load existing signature
const existing = '{{ $workshop->collector_signature }}';
if (existing && existing.startsWith('data:')) {
    const img = new Image();
    img.onload = () => ctx.drawImage(img, 0, 0);
    img.src = existing;
}
</script>
@endpush
@endsection
