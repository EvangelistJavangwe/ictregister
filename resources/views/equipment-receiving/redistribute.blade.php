@extends('layouts.app')
@section('title', 'Redistribute — '.$equipmentReceiving->cross_ref_no)

@section('content')
<div class="page-header">
    <div>
        <h2><i class="fas fa-share-square"></i> Redistribute / Transfer Equipment</h2>
        <span class="text-muted text-sm">{{ $equipmentReceiving->cross_ref_no }} &mdash; {{ $equipmentReceiving->item_description }}</span>
    </div>
    <a href="{{ route('equipment-receiving.show', $equipmentReceiving) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

{{-- Stock summary --}}
<div class="card mb-2">
    <div class="card-body" style="display:flex;flex-wrap:wrap;gap:20px;align-items:center;">
        <div style="text-align:center;min-width:80px;">
            <div style="font-size:1.6rem;font-weight:800;color:#2563eb;">{{ $equipmentReceiving->qty_received }}</div>
            <div class="text-sm text-muted">Received</div>
        </div>
        <div style="font-size:1.2rem;color:#94a3b8;">›</div>
        <div style="text-align:center;min-width:80px;">
            <div style="font-size:1.6rem;font-weight:800;color:#0891b2;">{{ count($availableSerials) }}</div>
            <div class="text-sm text-muted">Unassigned</div>
        </div>
        <div style="font-size:1.2rem;color:#94a3b8;">›</div>
        <div style="text-align:center;min-width:80px;">
            <div style="font-size:1.6rem;font-weight:800;color:#7c3aed;">{{ count($deployedWithHolder) }}</div>
            <div class="text-sm text-muted">Deployed</div>
        </div>
        <div style="margin-left:auto;text-align:right;" class="text-sm text-muted">
            {{ $equipmentReceiving->supplier }} &middot; {{ $equipmentReceiving->date_received?->format('d M Y') }}
        </div>
    </div>
</div>

<form method="POST" action="{{ route('equipment-receiving.redistribute.store', $equipmentReceiving) }}">
    @csrf

    @if($errors->has('selected_serials'))
    <div class="alert alert-danger"><i class="fas fa-times-circle"></i> {{ $errors->first('selected_serials') }}</div>
    @endif

    @if(!empty($workshopLockedSerials))
    <div class="alert alert-warning" style="display:flex;align-items:flex-start;gap:10px;">
        <i class="fas fa-tools" style="margin-top:3px;flex-shrink:0;"></i>
        <div>
            <strong>Workshop Lock:</strong> {{ count($workshopLockedSerials) }} serial number(s) below are currently in the workshop and cannot be redistributed until collected.
        </div>
    </div>
    @endif

    @if(!empty($disposalLockedSerials))
    <div class="alert alert-danger" style="display:flex;align-items:flex-start;gap:10px;">
        <i class="fas fa-trash-alt" style="margin-top:3px;flex-shrink:0;"></i>
        <div>
            <strong>Disposal Lock:</strong> {{ count($disposalLockedSerials) }} serial number(s) below have an active disposal request and cannot be redistributed.
        </div>
    </div>
    @endif

    {{-- Serial selection --}}
    <div class="card mb-2">
        <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
            <h3><i class="fas fa-list-ol" style="color:#2563eb;margin-right:6px;"></i> Select Units</h3>
            <div class="d-flex gap-2">
                <button type="button" class="btn btn-sm btn-secondary" onclick="selectAll(true)">Select All</button>
                <button type="button" class="btn btn-sm btn-secondary" onclick="selectAll(false)">Deselect All</button>
                <span id="selected-count" style="font-size:.85rem;color:#2563eb;font-weight:600;align-self:center;min-width:80px;"></span>
            </div>
        </div>
        <div class="card-body" style="padding:0;">

            {{-- Section A: Unassigned --}}
            @if(count($availableSerials) > 0)
            <div style="padding:12px 20px 6px;background:#f0fdf4;border-bottom:1px solid #bbf7d0;">
                <span style="font-size:.8rem;font-weight:700;color:#16a34a;text-transform:uppercase;letter-spacing:.04em;">
                    <i class="fas fa-box-open" style="margin-right:4px;"></i> Unassigned Stock ({{ count($availableSerials) }})
                </span>
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:8px 14px;width:36px;border-bottom:1px solid #e2e8f0;"></th>
                        <th style="padding:8px 14px;border-bottom:1px solid #e2e8f0;">#</th>
                        <th style="padding:8px 14px;border-bottom:1px solid #e2e8f0;">Serial Number</th>
                        <th style="padding:8px 14px;border-bottom:1px solid #e2e8f0;">Asset Tag <span style="font-weight:400;color:#94a3b8;">(assign now)</span></th>
                        <th style="padding:8px 14px;border-bottom:1px solid #e2e8f0;color:#94a3b8;">Current Holder</th>
                    </tr>
                </thead>
                <tbody>
                @foreach($availableSerials as $i => $sn)
                @php
                    $isWorkshopLocked = isset($workshopLockedSerials[$sn]);
                    $isDisposalLocked = isset($disposalLockedSerials[$sn]);
                    $isLocked = $isWorkshopLocked || $isDisposalLocked;
                    $rowBg = $isDisposalLocked ? 'background:#fef2f2;' : ($isWorkshopLocked ? 'background:#fffbeb;' : '');
                @endphp
                <tr style="border-bottom:1px solid #f1f5f9;{{ $rowBg }}" class="serial-row">
                    <td style="padding:9px 14px;">
                        <input type="checkbox" name="selected_serials[]" value="{{ $sn }}"
                            id="cb-a-{{ $i }}"
                            onchange="toggleAssetTag('at-a-{{ $i }}')"
                            {{ is_array(old('selected_serials')) && in_array($sn, old('selected_serials')) ? 'checked' : '' }}
                            {{ $isLocked ? 'disabled' : '' }}
                            style="width:16px;height:16px;{{ $isLocked ? 'cursor:not-allowed;opacity:.4;' : 'cursor:pointer;' }}">
                    </td>
                    <td style="padding:9px 14px;color:#94a3b8;font-size:.78rem;">{{ $i + 1 }}</td>
                    <td style="padding:9px 14px;">
                        <code style="background:#f1f5f9;padding:2px 7px;border-radius:4px;font-size:.82rem;">{{ $sn }}</code>
                        @if($isDisposalLocked)
                        <span style="display:inline-flex;align-items:center;gap:4px;background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;border-radius:4px;padding:1px 7px;font-size:.74rem;font-weight:600;margin-left:6px;">
                            <i class="fas fa-trash-alt" style="font-size:.7rem;"></i>
                            Disposal {{ $disposalLockedSerials[$sn]['status'] }}
                            @if($disposalLockedSerials[$sn]['disposal_ref_no']) &mdash; {{ $disposalLockedSerials[$sn]['disposal_ref_no'] }} @endif
                        </span>
                        @elseif($isWorkshopLocked)
                        <span style="display:inline-flex;align-items:center;gap:4px;background:#fef3c7;color:#92400e;border:1px solid #fcd34d;border-radius:4px;padding:1px 7px;font-size:.74rem;font-weight:600;margin-left:6px;">
                            <i class="fas fa-tools" style="font-size:.7rem;"></i>
                            In Workshop &mdash; {{ $workshopLockedSerials[$sn]['entry_job_number'] }}
                            ({{ $workshopLockedSerials[$sn]['status'] }})
                        </span>
                        @endif
                    </td>
                    <td style="padding:9px 14px;">
                        @php $aChecked = !$isLocked && is_array(old('selected_serials')) && in_array($sn, old('selected_serials')); @endphp
                        <input type="text" name="asset_tags[{{ $sn }}]" id="at-a-{{ $i }}"
                            class="form-control"
                            placeholder="e.g. ICT-2026-0{{ str_pad($i+1,2,'0',STR_PAD_LEFT) }}"
                            value="{{ old('asset_tags.'.$sn) }}"
                            {{ $isLocked ? 'disabled' : '' }}
                            style="max-width:180px;{{ !$aChecked || $isLocked ? 'opacity:.35;pointer-events:none;' : '' }}">
                    </td>
                    <td style="padding:9px 14px;color:#94a3b8;font-size:.82rem;">Not yet assigned</td>
                </tr>
                @endforeach
                </tbody>
            </table>
            @endif

            {{-- Section B: Currently Deployed --}}
            @if(count($deployedWithHolder) > 0)
            <div style="padding:12px 20px 6px;background:#faf5ff;border-top:1px solid #e2e8f0;border-bottom:1px solid #e9d5ff;">
                <span style="font-size:.8rem;font-weight:700;color:#7c3aed;text-transform:uppercase;letter-spacing:.04em;">
                    <i class="fas fa-user-check" style="margin-right:4px;"></i> Currently Deployed — Transfer to New Holder ({{ count($deployedWithHolder) }})
                </span>
            </div>
            <table style="width:100%;border-collapse:collapse;font-size:.875rem;">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="padding:8px 14px;width:36px;border-bottom:1px solid #e2e8f0;"></th>
                        <th style="padding:8px 14px;border-bottom:1px solid #e2e8f0;">#</th>
                        <th style="padding:8px 14px;border-bottom:1px solid #e2e8f0;">Serial Number</th>
                        <th style="padding:8px 14px;border-bottom:1px solid #e2e8f0;">Asset Tag</th>
                        <th style="padding:8px 14px;border-bottom:1px solid #e2e8f0;">Current Holder</th>
                        <th style="padding:8px 14px;border-bottom:1px solid #e2e8f0;">Depot / Dept</th>
                        <th style="padding:8px 14px;border-bottom:1px solid #e2e8f0;">Since</th>
                    </tr>
                </thead>
                <tbody>
                @php $di = 0; @endphp
                @foreach($deployedWithHolder as $sn => $holder)
                @php
                    $isWorkshopLocked = isset($workshopLockedSerials[$sn]);
                    $isDisposalLocked = isset($disposalLockedSerials[$sn]);
                    $isLocked = $isWorkshopLocked || $isDisposalLocked;
                    $rowBg = $isDisposalLocked ? 'background:#fef2f2;' : ($isWorkshopLocked ? 'background:#fffbeb;' : '');
                    $isPreselected = !$isLocked && (
                        ($filterSerial === $sn)
                        || (is_array(old('selected_serials')) && in_array($sn, old('selected_serials')))
                    );
                @endphp
                <tr style="border-bottom:1px solid #f1f5f9;{{ $rowBg }}" class="serial-row">
                    <td style="padding:9px 14px;">
                        <input type="checkbox" name="selected_serials[]" value="{{ $sn }}"
                            id="cb-d-{{ $di }}"
                            onchange="toggleAssetTag('at-d-{{ $di }}')"
                            {{ $isPreselected ? 'checked' : '' }}
                            {{ $isLocked ? 'disabled' : '' }}
                            style="width:16px;height:16px;{{ $isLocked ? 'cursor:not-allowed;opacity:.4;' : 'cursor:pointer;' }}">
                    </td>
                    <td style="padding:9px 14px;color:#94a3b8;font-size:.78rem;">{{ $di + 1 }}</td>
                    <td style="padding:9px 14px;">
                        <code style="background:#f3e8ff;padding:2px 7px;border-radius:4px;font-size:.82rem;">{{ $sn }}</code>
                        @if($isDisposalLocked)
                        <span style="display:inline-flex;align-items:center;gap:4px;background:#fee2e2;color:#b91c1c;border:1px solid #fca5a5;border-radius:4px;padding:1px 7px;font-size:.74rem;font-weight:600;margin-left:6px;">
                            <i class="fas fa-trash-alt" style="font-size:.7rem;"></i>
                            Disposal {{ $disposalLockedSerials[$sn]['status'] }}
                            @if($disposalLockedSerials[$sn]['disposal_ref_no']) &mdash; {{ $disposalLockedSerials[$sn]['disposal_ref_no'] }} @endif
                        </span>
                        @elseif($isWorkshopLocked)
                        <span style="display:inline-flex;align-items:center;gap:4px;background:#fef3c7;color:#92400e;border:1px solid #fcd34d;border-radius:4px;padding:1px 7px;font-size:.74rem;font-weight:600;margin-left:6px;">
                            <i class="fas fa-tools" style="font-size:.7rem;"></i>
                            In Workshop &mdash; {{ $workshopLockedSerials[$sn]['entry_job_number'] }}
                            ({{ $workshopLockedSerials[$sn]['status'] }})
                        </span>
                        @endif
                    </td>
                    <td style="padding:9px 14px;">
                        <input type="text" name="asset_tags[{{ $sn }}]" id="at-d-{{ $di }}"
                            class="form-control"
                            placeholder="Asset tag"
                            value="{{ old('asset_tags.'.$sn, $holder['asset_tag'] ?? '') }}"
                            {{ $isLocked ? 'disabled' : '' }}
                            style="max-width:180px;{{ !$isPreselected || $isLocked ? 'opacity:.35;pointer-events:none;' : '' }}">
                    </td>
                    <td style="padding:9px 14px;">
                        <span style="font-weight:600;color:#1e293b;">{{ $holder['recipient'] }}</span>
                    </td>
                    <td style="padding:9px 14px;font-size:.82rem;color:#64748b;">{{ $holder['depot'] ?? '—' }}</td>
                    <td style="padding:9px 14px;font-size:.78rem;color:#94a3b8;">{{ $holder['date']?->format('d M Y') }}</td>
                </tr>
                @php $di++; @endphp
                @endforeach
                </tbody>
            </table>
            @endif

        </div>
    </div>

    {{-- Redistribution details --}}
    <div class="card mb-2">
        <div class="card-header"><h3><i class="fas fa-exchange-alt" style="color:#2563eb;margin-right:6px;"></i> New Assignment Details</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Date *</label>
                    <input type="date" name="redistribution_date" class="form-control @error('redistribution_date') is-invalid @enderror"
                        value="{{ old('redistribution_date', today()->format('Y-m-d')) }}" required>
                    @error('redistribution_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Assignment Type *</label>
                    <select name="redistribution_type" id="redist-type" class="form-control" required>
                        <option value="">— Select —</option>
                        <option value="Individual"       {{ old('redistribution_type') === 'Individual' ? 'selected' : '' }}>Individual (Person)</option>
                        <option value="Depot/Department" {{ old('redistribution_type') === 'Depot/Department' ? 'selected' : '' }}>Depot / Department</option>
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">New Recipient / Holder *</label>
                    <input type="text" name="recipient_name" class="form-control @error('recipient_name') is-invalid @enderror"
                        value="{{ old('recipient_name') }}" placeholder="Full name or depot name" required>
                    @error('recipient_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Job Title *</label>
                    <input type="text" name="recipient_job_title" class="form-control @error('recipient_job_title') is-invalid @enderror"
                        value="{{ old('recipient_job_title') }}" placeholder="e.g. Accounts Clerk" required>
                    @error('recipient_job_title')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Depot</label>
                    <input type="text" name="depot_department" class="form-control" value="{{ old('depot_department') }}" placeholder="e.g. Plumtree Depot — depot the recipient belongs to">
                </div>
                <div class="form-group">
                    <label class="form-label">Issued By *</label>
                    <input type="text" name="issued_by" class="form-control @error('issued_by') is-invalid @enderror" value="{{ old('issued_by', auth()->user()->firstname.' '.auth()->user()->lastname) }}" required>
                    @error('issued_by')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Cross Ref / Form 208 *</label>
                    <input type="text" name="cross_ref_form208" class="form-control @error('cross_ref_form208') is-invalid @enderror" value="{{ old('cross_ref_form208') }}" required>
                    @error('cross_ref_form208')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Remarks *</label>
                <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror" rows="2" required>{{ old('remarks') }}</textarea>
                @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- Signatures --}}
    <div class="card mb-2">
        <div class="card-header"><h3><i class="fas fa-signature" style="color:#2563eb;margin-right:6px;"></i> Signatures</h3></div>
        <div class="card-body">
            <div class="grid-2">
                <div class="form-group">
                    <label class="form-label">Receiver Signature</label>
                    <div class="sig-pad-wrap"><canvas id="sig-receiver" width="500" height="140"></canvas></div>
                    <input type="hidden" name="receiver_signature" id="sig-receiver-data">
                    <button type="button" onclick="clearCanvas('sig-receiver','sig-receiver-data')" class="btn btn-sm btn-secondary mt-1"><i class="fas fa-eraser"></i> Clear</button>
                </div>
                <div class="form-group">
                    <label class="form-label">Issuer Signature</label>
                    <div class="sig-pad-wrap"><canvas id="sig-issuer" width="500" height="140"></canvas></div>
                    <input type="hidden" name="issuer_signature" id="sig-issuer-data">
                    <button type="button" onclick="clearCanvas('sig-issuer','sig-issuer-data')" class="btn btn-sm btn-secondary mt-1"><i class="fas fa-eraser"></i> Clear</button>
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
        <a href="{{ route('equipment-receiving.show', $equipmentReceiving) }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>

@push('scripts')
<script>
function toggleAssetTag(atId) {
    const at = document.getElementById(atId);
    if (!at) return;
    const cb = at.closest('tr').querySelector('input[type=checkbox]');
    at.style.opacity = cb.checked ? '1' : '.35';
    at.style.pointerEvents = cb.checked ? 'auto' : 'none';
    if (!cb.checked) at.value = '';
    updateCount();
}

function selectAll(state) {
    document.querySelectorAll('input[name="selected_serials[]"]').forEach(cb => {
        cb.checked = state;
        const row = cb.closest('tr');
        const at  = row.querySelector('input[type=text]');
        if (at) {
            at.style.opacity = state ? '1' : '.35';
            at.style.pointerEvents = state ? 'auto' : 'none';
            if (!state) at.value = '';
        }
    });
    updateCount();
}

function updateCount() {
    const total   = document.querySelectorAll('input[name="selected_serials[]"]').length;
    const checked = document.querySelectorAll('input[name="selected_serials[]"]:checked').length;
    document.getElementById('selected-count').textContent = checked ? checked + ' of ' + total + ' selected' : '';
}

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
function clearCanvas(id, hid) {
    document.getElementById(id).getContext('2d').clearRect(0,0,9999,9999);
    document.getElementById(hid).value = '';
}

initSigPad('sig-receiver', 'sig-receiver-data');
initSigPad('sig-issuer',   'sig-issuer-data');
updateCount();
</script>
@endpush
@endsection
