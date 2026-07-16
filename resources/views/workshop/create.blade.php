@extends('layouts.app')
@section('title', 'New Workshop Job')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> New Workshop Job</h2>
    <a href="{{ route('workshop.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('workshop.store') }}">
            @csrf
            <h4 style="font-size:.9rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:14px;"><i class="fas fa-info-circle"></i> Equipment Details</h4>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Date/Time Received *</label>
                    <input type="datetime-local" name="date_time_received" class="form-control" value="{{ old('date_time_received', now()->format('Y-m-d\TH:i')) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Equipment Type *</label>
                    <input type="text" name="equipment_type" class="form-control @error('equipment_type') is-invalid @enderror" value="{{ old('equipment_type') }}" placeholder="e.g. Laptop, Printer, Network Switch" required>
                    @error('equipment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Brand/Make/Model</label>
                    <input type="text" name="brand_make_model" class="form-control" value="{{ old('brand_make_model') }}" placeholder="e.g. Dell Latitude 5490">
                </div>
                <div class="form-group">
                    <label class="form-label">Serial Number / Asset Tag *</label>
                    <div style="position:relative;">
                        <input type="text" name="serial_number_asset_tag" id="serial_number_asset_tag"
                               class="form-control @error('serial_number_asset_tag') is-invalid @enderror" value="{{ old('serial_number_asset_tag') }}"
                               placeholder="Type serial — Equipment Type & Brand auto-fill"
                               autocomplete="off" required>
                        <div id="serial-spinner" style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#94a3b8;">
                            <i class="fas fa-circle-notch fa-spin"></i>
                        </div>
                    </div>
                    @error('serial_number_asset_tag')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    <div id="serial-lookup-result" style="display:none;margin-top:6px;padding:8px 12px;border-radius:8px;font-size:.8rem;"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Cross Ref / Form 208</label>
                    <input type="text" name="cross_ref_form208" class="form-control" value="{{ old('cross_ref_form208') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Physical Condition on Receipt</label>
                    <input type="text" name="physical_condition_on_receipt" class="form-control" value="{{ old('physical_condition_on_receipt') }}" placeholder="e.g. Good, Damaged, Cracked screen">
                </div>
            </div>

            <hr class="divider">
            <h4 style="font-size:.9rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:14px;"><i class="fas fa-building"></i> Sender Information</h4>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Depot Name</label>
                    <input type="text" name="depot_name" class="form-control" value="{{ old('depot_name') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Department</label>
                    <input type="text" name="department" class="form-control" value="{{ old('department') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Contact Person</label>
                    <input type="text" name="contact_person" class="form-control" value="{{ old('contact_person') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number</label>
                    <input type="text" name="phone_number" class="form-control" value="{{ old('phone_number') }}">
                </div>
            </div>

            <hr class="divider">
            <h4 style="font-size:.9rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:14px;"><i class="fas fa-wrench"></i> Fault & Assignment</h4>
            <div class="form-group">
                <label class="form-label">Nature of Fault / Classification *</label>
                <textarea name="nature_of_fault" class="form-control @error('nature_of_fault') is-invalid @enderror" required>{{ old('nature_of_fault') }}</textarea>
                @error('nature_of_fault')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
            <div class="form-group">
                <label class="form-label">Accessories Received</label>
                <input type="text" name="accessories_received" class="form-control" value="{{ old('accessories_received') }}" placeholder="e.g. Power cable, Mouse">
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Priority Level *</label>
                    <select name="priority_level" class="form-control" required>
                        @foreach(['Low','Medium','High','Urgent'] as $p)
                        <option value="{{ $p }}" {{ old('priority_level','Medium') === $p ? 'selected' : '' }}>{{ $p }}</option>
                        @endforeach
                    </select>
                </div>
                @if(auth()->user()->isAdminOrHod())
                <div class="form-group">
                    <label class="form-label">Assign Technician</label>
                    <select name="technician_assigned" class="form-control">
                        <option value="">— Unassigned —</option>
                        @foreach($technicians as $tech)
                        <option value="{{ $tech->id }}" {{ old('technician_assigned') == $tech->id ? 'selected' : '' }}>
                            {{ $tech->firstname }} {{ $tech->lastname }} ({{ $tech->designation ?? $tech->username }})
                        </option>
                        @endforeach
                    </select>
                </div>
                @endif
                <div class="form-group">
                    <label class="form-label">Due Date</label>
                    <input type="datetime-local" name="due_date" class="form-control" value="{{ old('due_date') }}">
                </div>
            </div>

            <hr class="divider">
            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Create Job</button>
            <a href="{{ route('workshop.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@push('scripts')
<script>
(function () {
    const serialInput  = document.getElementById('serial_number_asset_tag');
    const typeInput    = document.querySelector('input[name="equipment_type"]');
    const brandInput   = document.querySelector('input[name="brand_make_model"]');
    const contactInput = document.querySelector('input[name="contact_person"]');
    const deptInput    = document.querySelector('input[name="department"]');
    const spinner      = document.getElementById('serial-spinner');
    const resultBox    = document.getElementById('serial-lookup-result');
    const lookupUrl    = '{{ route("workshop.lookup-serial") }}';

    let debounceTimer;

    function greyOut(el) {
        el.style.background  = '#f1f5f9';
        el.style.color       = '#64748b';
        el.style.borderColor = '#cbd5e1';
        el.dataset.autofilled = '1';
    }

    function clearGrey(el) {
        el.style.background  = '';
        el.style.color       = '';
        el.style.borderColor = '';
        delete el.dataset.autofilled;
    }

    function showResult(msg, type) {
        resultBox.style.display = 'block';
        resultBox.style.background = type === 'success' ? '#d1fae5' : (type === 'warn' ? '#fef9c3' : '#f1f5f9');
        resultBox.style.border     = '1px solid ' + (type === 'success' ? '#a7f3d0' : (type === 'warn' ? '#fde68a' : '#e2e8f0'));
        resultBox.style.color      = type === 'success' ? '#065f46' : (type === 'warn' ? '#92400e' : '#64748b');
        resultBox.innerHTML = msg;
    }

    function clearResult() {
        resultBox.style.display = 'none';
        resultBox.innerHTML = '';
    }

    serialInput.addEventListener('input', function () {
        clearTimeout(debounceTimer);
        const val = this.value.trim();
        if (val.length < 3) { clearResult(); return; }

        debounceTimer = setTimeout(function () {
            spinner.style.display = 'block';
            fetch(lookupUrl + '?serial=' + encodeURIComponent(val), {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            })
            .then(r => r.json())
            .then(data => {
                spinner.style.display = 'none';
                if (!data.found) {
                    showResult('<i class="fas fa-info-circle"></i> Serial not found in the equipment register or workshop history — fill in details manually.', 'info');
                    return;
                }

                // Auto-fill Equipment Type if still empty or was auto-filled before
                if (!typeInput.dataset.manual) {
                    typeInput.value = data.equipment_type || '';
                    greyOut(typeInput);
                }
                // Auto-fill Brand/Make/Model
                if (!brandInput.dataset.manual) {
                    brandInput.value = data.brand_model || '';
                    greyOut(brandInput);
                }

                if (data.source === 'workshop') {
                    // Came from a prior workshop job, not the Helpdesk Registry
                    let holderLine = '';
                    if (data.assigned_to) {
                        holderLine = ' &nbsp;|&nbsp; <strong>Previous contact:</strong> ' + data.assigned_to
                            + (data.depot ? ' <span style="color:#94a3b8;">(' + data.depot + ')</span>' : '');
                    }
                    showResult(
                        '<i class="fas fa-history"></i> Found in workshop history (Job ' + (data.last_job || '—') + '): <strong>' + data.equipment_type + '</strong>'
                        + (data.brand_model ? ' &mdash; ' + data.brand_model : '')
                        + holderLine
                        + ' <span style="color:#94a3b8;">(not in Helpdesk Registry)</span>',
                        'warn'
                    );
                    return;
                }

                // Build holder info line
                let holderLine = '';
                if (data.assigned_to) {
                    holderLine = ' &nbsp;|&nbsp; <strong>Currently with:</strong> ' + data.assigned_to
                        + (data.depot ? ' <span style="color:#94a3b8;">(' + data.depot + ')</span>' : '');
                }

                showResult(
                    '<i class="fas fa-check-circle"></i> Found: <strong>' + data.equipment_type + '</strong>'
                    + (data.brand_model ? ' &mdash; ' + data.brand_model : '')
                    + holderLine,
                    'success'
                );
            })
            .catch(() => { spinner.style.display = 'none'; });
        }, 450);
    });

    // Mark field as manually edited so auto-fill won't overwrite user's own typing
    typeInput.addEventListener('input',  function () { this.dataset.manual = '1'; clearGrey(this); });
    brandInput.addEventListener('input', function () { this.dataset.manual = '1'; clearGrey(this); });
})();
</script>
@endpush
@endsection
