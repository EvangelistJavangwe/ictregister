@extends('layouts.app')
@section('title', 'Receive New Equipment')

@section('content')
<div class="page-header">
    <div>
        <h2><i class="fas fa-truck"></i> Receive New Equipment</h2>
        <span class="text-muted text-sm">Step 1 of 2 — Delivery &amp; Equipment Details</span>
    </div>
    <a href="{{ route('equipment-receiving.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

{{-- Step indicator --}}
<div style="display:flex;gap:0;margin-bottom:20px;">
    <div style="flex:1;padding:10px 16px;background:#2563eb;color:#fff;border-radius:8px 0 0 8px;font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:8px;">
        <span style="background:rgba(255,255,255,.3);border-radius:50%;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;">1</span>
        Delivery &amp; Equipment Details
    </div>
    <div style="flex:1;padding:10px 16px;background:#e2e8f0;color:#94a3b8;border-radius:0 8px 8px 0;font-size:.85rem;font-weight:600;display:flex;align-items:center;gap:8px;">
        <span style="background:#cbd5e1;border-radius:50%;width:22px;height:22px;display:inline-flex;align-items:center;justify-content:center;font-size:.75rem;">2</span>
        Inspection &amp; Transfer <span style="font-weight:400;font-size:.75rem;">(after saving)</span>
    </div>
</div>

<form method="POST" action="{{ route('equipment-receiving.store') }}">
    @csrf

    {{-- Section 1: Delivery Information --}}
    <div class="card mb-2">
        <div class="card-header"><h3><i class="fas fa-truck" style="color:#2563eb;margin-right:6px;"></i> Delivery Information</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Date Received *</label>
                    <input type="date" name="date_received" class="form-control @error('date_received') is-invalid @enderror"
                        value="{{ old('date_received', today()->format('Y-m-d')) }}" required>
                    @error('date_received')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Supplier / Source *</label>
                    <input type="text" name="supplier" class="form-control @error('supplier') is-invalid @enderror"
                        value="{{ old('supplier') }}" required>
                    @error('supplier')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Delivery Note No. *</label>
                    <input type="text" name="delivery_note_no" class="form-control @error('delivery_note_no') is-invalid @enderror" value="{{ old('delivery_note_no') }}" required>
                    @error('delivery_note_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">LPO / PO No. *</label>
                    <input type="text" name="po_no" class="form-control @error('po_no') is-invalid @enderror" value="{{ old('po_no') }}" required>
                    @error('po_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    {{-- Section 2: Equipment Details --}}
    <div class="card mb-2">
        <div class="card-header"><h3><i class="fas fa-desktop" style="color:#2563eb;margin-right:6px;"></i> Equipment Details</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group" style="grid-column:span 2;">
                    <label class="form-label">Item Description *</label>
                    <input type="text" name="item_description" class="form-control @error('item_description') is-invalid @enderror"
                        value="{{ old('item_description') }}" required placeholder="e.g. Desktop Computer, Laptop, Printer">
                    @error('item_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Brand / Model *</label>
                    <input type="text" name="brand_model" class="form-control @error('brand_model') is-invalid @enderror" value="{{ old('brand_model') }}" placeholder="e.g. Dell OptiPlex 7090" required>
                    @error('brand_model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Quantity Received *</label>
                    <input type="number" name="qty_received" id="qty_received" class="form-control @error('qty_received') is-invalid @enderror"
                        value="{{ old('qty_received', 1) }}" min="1" required oninput="updateSerialFields()">
                    @error('qty_received')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Cost Price (USD)</label>
                    <input type="number" name="cost_price" class="form-control @error('cost_price') is-invalid @enderror"
                        value="{{ old('cost_price') }}" step="0.01" min="0" placeholder="e.g. 850.00">
                    @error('cost_price')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            {{-- Dynamic serial number fields --}}
            <div class="form-group" id="serial-numbers-section">
                <label class="form-label">Serial Numbers <span class="text-muted text-sm" id="serial-hint">(one per unit)</span></label>
                <div id="serial-fields">
                    <input type="text" name="serial_numbers[]" class="form-control mb-1" placeholder="Serial number 1">
                </div>
                <div class="text-sm text-muted mt-1">Enter a serial number for each unit received. Leave blank if not applicable.</div>
            </div>

            <div class="form-group">
                <label class="form-label">Accessories Included *</label>
                <input type="text" name="accessories_included" class="form-control @error('accessories_included') is-invalid @enderror" value="{{ old('accessories_included') }}"
                    placeholder="e.g. Power cable, Keyboard, Mouse" required>
                @error('accessories_included')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Remarks *</label>
                <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="2" required>{{ old('remarks') }}</textarea>
                @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- Section 3: Warranty --}}
    <div class="card mb-2">
        <div class="card-header"><h3><i class="fas fa-shield-alt" style="color:#16a34a;margin-right:6px;"></i> Warranty Information <span class="text-muted text-sm" style="font-weight:400;">(optional)</span></h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Warranty Start Date</label>
                    <input type="date" name="warranty_start_date" class="form-control @error('warranty_start_date') is-invalid @enderror"
                        value="{{ old('warranty_start_date') }}">
                    @error('warranty_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Warranty Expiry Date</label>
                    <input type="date" name="warranty_end_date" class="form-control @error('warranty_end_date') is-invalid @enderror"
                        value="{{ old('warranty_end_date') }}">
                    @error('warranty_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" style="grid-column:span 2;">
                    <label class="form-label">Warranty Notes</label>
                    <input type="text" name="warranty_notes" class="form-control" value="{{ old('warranty_notes') }}"
                        placeholder="e.g. 3-year on-site support, contact 0800-DELL-HELP">
                </div>
            </div>
            <p class="text-sm text-muted"><i class="fas fa-info-circle"></i> When the warranty expires it will automatically be recorded in the asset tracking history.</p>
        </div>
    </div>

    {{-- Section 4: Receiving Signatures --}}
    <div class="card mb-2">
        <div class="card-header"><h3><i class="fas fa-signature" style="color:#2563eb;margin-right:6px;"></i> Receiving Signatures</h3></div>
        <div class="card-body">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Receiver Signature <span class="text-muted text-sm">(person accepting delivery)</span></label>
                    <div class="sig-pad-wrap">
                        <canvas id="sig-receiver" width="500" height="150"></canvas>
                    </div>
                    <input type="hidden" name="user_signature" id="sig-receiver-data">
                    <button type="button" onclick="clearCanvas('sig-receiver','sig-receiver-data')" class="btn btn-sm btn-secondary mt-1"><i class="fas fa-eraser"></i> Clear</button>
                </div>
                <div class="form-group">
                    <label class="form-label">Authorising Signature <span class="text-muted text-sm">(supervisor / HOD)</span></label>
                    <div class="sig-pad-wrap">
                        <canvas id="sig-auth" width="500" height="150"></canvas>
                    </div>
                    <input type="hidden" name="inspector_signature" id="sig-auth-data">
                    <button type="button" onclick="clearCanvas('sig-auth','sig-auth-data')" class="btn btn-sm btn-secondary mt-1"><i class="fas fa-eraser"></i> Clear</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Delivery Record</button>
        <a href="{{ route('equipment-receiving.index') }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

@push('scripts')
<script>
function updateSerialFields() {
    const qty = parseInt(document.getElementById('qty_received').value) || 1;
    const container = document.getElementById('serial-fields');
    const existing = container.querySelectorAll('input');
    const current = existing.length;

    // Add missing fields
    for (let i = current; i < qty; i++) {
        const inp = document.createElement('input');
        inp.type = 'text';
        inp.name = 'serial_numbers[]';
        inp.className = 'form-control mb-1';
        inp.placeholder = `Serial number ${i + 1}`;
        container.appendChild(inp);
    }
    // Remove extra fields
    for (let i = current; i > qty; i--) {
        container.removeChild(container.lastChild);
    }
    // Update placeholders
    container.querySelectorAll('input').forEach((inp, idx) => {
        inp.placeholder = `Serial number ${idx + 1}`;
    });

    document.getElementById('serial-hint').textContent = qty > 1 ? `(${qty} units — one serial per row)` : '(one per unit)';
}
// Init on load
updateSerialFields();

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
initSigPad('sig-receiver', 'sig-receiver-data');
initSigPad('sig-auth', 'sig-auth-data');
</script>
@endpush
@endsection
