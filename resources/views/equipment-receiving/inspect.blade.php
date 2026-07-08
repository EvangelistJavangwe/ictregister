@extends('layouts.app')
@section('title', 'Inspect & Transfer — '.$equipmentReceiving->cross_ref_no)

@section('content')
<div class="page-header">
    <div>
        <h2><i class="fas fa-clipboard-check"></i> Inspection &amp; Transfer</h2>
        <span class="text-muted text-sm">Step 2 of 2 — {{ $equipmentReceiving->cross_ref_no }} · {{ $equipmentReceiving->item_description }}</span>
    </div>
    <a href="{{ route('equipment-receiving.show', $equipmentReceiving) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

{{-- Step indicator --}}
<div style="display:flex;gap:0;margin-bottom:20px;">
    <div style="flex:1;padding:10px 16px;background:#16a34a;color:#fff;border-radius:8px 0 0 8px;font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:8px;">
        <span style="background:rgba(255,255,255,.3);border-radius:50%;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;"><i class="fas fa-check"></i></span>
        Delivery &amp; Equipment Details <span style="font-weight:400;">(done)</span>
    </div>
    <div style="flex:1;padding:10px 16px;background:#2563eb;color:#fff;border-radius:0 8px 8px 0;font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:8px;">
        <span style="background:rgba(255,255,255,.3);border-radius:50%;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;">2</span>
        Inspection &amp; Transfer
    </div>
</div>

{{-- Stock status bar --}}
<div class="card mb-2">
    <div class="card-body" style="display:flex;flex-wrap:wrap;gap:20px;align-items:center;">
        <div style="text-align:center;min-width:90px;">
            <div style="font-size:1.6rem;font-weight:700;color:#1e293b;">{{ $equipmentReceiving->qty_received }}</div>
            <div class="text-sm text-muted">Total Received</div>
        </div>
        <div style="font-size:1.4rem;color:#94a3b8;">→</div>
        <div style="text-align:center;min-width:90px;">
            <div style="font-size:1.6rem;font-weight:700;color:#16a34a;">{{ $equipmentReceiving->qty_received - $pending }}</div>
            <div class="text-sm text-muted">Already Inspected</div>
        </div>
        <div style="font-size:1.4rem;color:#94a3b8;">→</div>
        <div style="text-align:center;min-width:90px;">
            <div style="font-size:1.6rem;font-weight:700;color:#d97706;">{{ $pending }}</div>
            <div class="text-sm text-muted">Pending Inspection</div>
        </div>
        <div style="margin-left:auto;text-align:right;" class="text-sm text-muted">
            Ref: <strong>{{ $equipmentReceiving->cross_ref_no }}</strong><br>
            {{ $equipmentReceiving->supplier }} · {{ $equipmentReceiving->date_received?->format('d M Y') }}
        </div>
    </div>
</div>

<form method="POST" action="{{ route('equipment-receiving.inspect.store', $equipmentReceiving) }}">
    @csrf

    {{-- Inspection Details --}}
    <div class="card mb-2">
        <div class="card-header"><h3><i class="fas fa-clipboard-check" style="color:#2563eb;margin-right:6px;"></i> Inspection Details</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Qty Being Inspected * <span class="text-muted text-sm">(max {{ $pending }})</span></label>
                    <input type="number" name="qty_inspected" class="form-control @error('qty_inspected') is-invalid @enderror"
                        value="{{ old('qty_inspected', $pending) }}" min="1" max="{{ $pending }}" required>
                    @error('qty_inspected')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div class="text-sm text-muted mt-1">These items will be deducted from the {{ $pending }} pending-inspection stock.</div>
                </div>
                <div class="form-group">
                    <label class="form-label">Inspection Status *</label>
                    <select name="inspection_status" class="form-control @error('inspection_status') is-invalid @enderror" required>
                        <option value="">— Select —</option>
                        @foreach(['Accepted','Rejected','Pending'] as $s)
                        <option value="{{ $s }}" {{ old('inspection_status') === $s ? 'selected' : '' }}>{{ $s }}</option>
                        @endforeach
                    </select>
                    @error('inspection_status')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Inspected By</label>
                    <select name="inspected_by" class="form-control">
                        <option value="">— Select Inspector —</option>
                        @foreach($inspectors as $i)
                        <option value="{{ $i->id }}" {{ old('inspected_by') == $i->id ? 'selected' : '' }}>
                            {{ $i->firstname }} {{ $i->lastname }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Inspection Date *</label>
                    <input type="date" name="inspection_date" class="form-control" value="{{ old('inspection_date', today()->format('Y-m-d')) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Physical Condition</label>
                    <input type="text" name="physical_condition" class="form-control" value="{{ old('physical_condition') }}" placeholder="e.g. No visible damage">
                </div>
                <div class="form-group">
                    <label class="form-label">Functional Test Result</label>
                    <select name="functional_test_result" class="form-control">
                        <option value="">— Select —</option>
                        @foreach(['Passed','Failed','Not Tested'] as $v)
                        <option value="{{ $v }}" {{ old('functional_test_result') === $v ? 'selected' : '' }}>{{ $v }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Accessories Checked</label>
                    <input type="text" name="accessories_checked" class="form-control" value="{{ old('accessories_checked') }}" placeholder="Items verified during inspection">
                </div>
            </div>
        </div>
    </div>

    {{-- Remarks --}}
    <div class="card mb-2">
        <div class="card-body">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control" rows="2">{{ old('remarks') }}</textarea>
            </div>
        </div>
    </div>

    {{-- Inspector Signature only --}}
    <div class="card mb-2">
        <div class="card-header"><h3><i class="fas fa-signature" style="color:#2563eb;margin-right:6px;"></i> Inspector Signature</h3></div>
        <div class="card-body" style="max-width:560px;">
            <div class="form-group" style="margin-bottom:0;">
                <label class="form-label">Inspector / Authorising Officer Signature</label>
                <div class="sig-pad-wrap"><canvas id="sig-inspector" width="500" height="160"></canvas></div>
                <input type="hidden" name="inspector_signature" id="sig-inspector-data">
                <button type="button" onclick="clearCanvas('sig-inspector','sig-inspector-data')" class="btn btn-sm btn-secondary mt-1"><i class="fas fa-eraser"></i> Clear</button>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-clipboard-check"></i> Save Inspection Record</button>
        <a href="{{ route('equipment-receiving.show', $equipmentReceiving) }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

@push('scripts')
<script>
function initSigPad(canvasId, hiddenId) {
    const canvas = document.getElementById(canvasId);
    const ctx = canvas.getContext('2d');
    let drawing = false, lastX = 0, lastY = 0;
    function getPos(e) {
        const rect = canvas.getBoundingClientRect();
        const t = e.touches ? e.touches[0] : e;
        return [t.clientX - rect.left, t.clientY - rect.top];
    }
    canvas.addEventListener('mousedown', e => { drawing = true; [lastX, lastY] = getPos(e); });
    canvas.addEventListener('mousemove', e => {
        if (!drawing) return;
        const [x, y] = getPos(e);
        ctx.beginPath(); ctx.moveTo(lastX, lastY); ctx.lineTo(x, y);
        ctx.strokeStyle = '#1e293b'; ctx.lineWidth = 2; ctx.lineCap = 'round'; ctx.stroke();
        [lastX, lastY] = [x, y];
        document.getElementById(hiddenId).value = canvas.toDataURL();
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
        document.getElementById(hiddenId).value = canvas.toDataURL();
    });
    canvas.addEventListener('touchend', () => drawing = false);
}
function clearCanvas(canvasId, hiddenId) {
    document.getElementById(canvasId).getContext('2d').clearRect(0, 0, 9999, 9999);
    document.getElementById(hiddenId).value = '';
}
initSigPad('sig-inspector', 'sig-inspector-data');
</script>
@endpush
@endsection
