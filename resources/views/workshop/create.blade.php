@extends('layouts.app')
@section('title', 'New Workshop Job')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-plus-circle"></i> New Workshop Job</h2>
    <a href="{{ route('workshop.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<div id="draft-banner" class="alert alert-info" style="display:none;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;">
    <span><i class="fas fa-file-circle-question"></i> You have an unsaved draft from <strong id="draft-time"></strong>.</span>
    <span class="d-flex gap-2">
        <button type="button" id="draft-restore" class="btn btn-sm btn-primary">Restore Draft</button>
        <button type="button" id="draft-discard" class="btn btn-sm btn-secondary">Discard</button>
    </span>
</div>

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('workshop.store') }}">
            @csrf
            <h4 style="font-size:.9rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:14px;"><i class="fas fa-info-circle"></i> Job Details</h4>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Date/Time Received *</label>
                    <input type="datetime-local" name="date_time_received" class="form-control" value="{{ old('date_time_received', now()->format('Y-m-d\TH:i')) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Cross Ref / Form 208 *</label>
                    <input type="text" name="cross_ref_form208" class="form-control @error('cross_ref_form208') is-invalid @enderror" value="{{ old('cross_ref_form208') }}" required>
                    @error('cross_ref_form208')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>

            <hr class="divider">
            <h4 style="font-size:.9rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:14px;"><i class="fas fa-laptop"></i> Devices</h4>

            <div id="devices-container">
                {{-- Device 1 — always present, uses the job's own equipment fields --}}
                <div class="device-row" data-device-index="0" style="border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:14px;">
                    <div style="font-size:.78rem;font-weight:700;color:#2563eb;text-transform:uppercase;margin-bottom:10px;">Device 1</div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Equipment Type *</label>
                            <input type="text" name="equipment_type" class="form-control device-type @error('equipment_type') is-invalid @enderror" value="{{ old('equipment_type') }}" placeholder="e.g. Laptop, Printer, Network Switch" required>
                            @error('equipment_type')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Brand/Make/Model *</label>
                            <input type="text" name="brand_make_model" class="form-control device-brand @error('brand_make_model') is-invalid @enderror" value="{{ old('brand_make_model') }}" placeholder="e.g. Dell Latitude 5490" required>
                            @error('brand_make_model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="form-group">
                            <label class="form-label">Serial Number / Asset Tag *</label>
                            <div style="position:relative;">
                                <input type="text" name="serial_number_asset_tag" id="serial_number_asset_tag"
                                       class="form-control device-serial @error('serial_number_asset_tag') is-invalid @enderror" value="{{ old('serial_number_asset_tag') }}"
                                       placeholder="Type serial — Equipment Type & Brand auto-fill"
                                       autocomplete="off" required>
                                <div class="serial-spinner" style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#94a3b8;">
                                    <i class="fas fa-circle-notch fa-spin"></i>
                                </div>
                            </div>
                            @error('serial_number_asset_tag')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            <div class="serial-lookup-result" style="display:none;margin-top:6px;padding:8px 12px;border-radius:8px;font-size:.8rem;"></div>
                            <div class="duplicate-job-alert" style="display:none;margin-top:6px;padding:10px 12px;border-radius:8px;font-size:.82rem;background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;font-weight:600;"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Physical Condition on Receipt *</label>
                            <input type="text" name="physical_condition_on_receipt" class="form-control device-condition @error('physical_condition_on_receipt') is-invalid @enderror" value="{{ old('physical_condition_on_receipt') }}" placeholder="e.g. Good, Damaged, Cracked screen" required>
                            @error('physical_condition_on_receipt')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                    </div>
                </div>
            </div>

            <button type="button" id="add-device-btn" class="btn btn-sm btn-secondary mb-2"><i class="fas fa-plus"></i> Add Another Device</button>

            <template id="device-row-template">
                <div class="device-row" style="border:1px solid #e2e8f0;border-radius:10px;padding:16px;margin-bottom:14px;">
                    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
                        <span class="device-label" style="font-size:.78rem;font-weight:700;color:#2563eb;text-transform:uppercase;"></span>
                        <button type="button" class="btn btn-sm btn-danger remove-device-btn"><i class="fas fa-trash"></i> Remove</button>
                    </div>
                    <div class="form-row">
                        <div class="form-group">
                            <label class="form-label">Equipment Type *</label>
                            <input type="text" class="form-control device-type" placeholder="e.g. Laptop, Printer, Network Switch" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Brand/Make/Model *</label>
                            <input type="text" class="form-control device-brand" placeholder="e.g. Dell Latitude 5490" required>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Serial Number / Asset Tag *</label>
                            <div style="position:relative;">
                                <input type="text" class="form-control device-serial" placeholder="Type serial — Equipment Type & Brand auto-fill" autocomplete="off" required>
                                <div class="serial-spinner" style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#94a3b8;">
                                    <i class="fas fa-circle-notch fa-spin"></i>
                                </div>
                            </div>
                            <div class="serial-lookup-result" style="display:none;margin-top:6px;padding:8px 12px;border-radius:8px;font-size:.8rem;"></div>
                            <div class="duplicate-job-alert" style="display:none;margin-top:6px;padding:10px 12px;border-radius:8px;font-size:.82rem;background:#fee2e2;border:1px solid #fca5a5;color:#991b1b;font-weight:600;"></div>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Physical Condition on Receipt *</label>
                            <input type="text" class="form-control device-condition" placeholder="e.g. Good, Damaged, Cracked screen" required>
                        </div>
                    </div>
                </div>
            </template>

            <hr class="divider">
            <h4 style="font-size:.9rem;font-weight:700;color:#64748b;text-transform:uppercase;margin-bottom:14px;"><i class="fas fa-building"></i> Sender Information</h4>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Depot Name *</label>
                    <input type="text" name="depot_name" class="form-control @error('depot_name') is-invalid @enderror" value="{{ old('depot_name') }}" list="depot-suggestions" autocomplete="off" required>
                    @error('depot_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Final Depot <span style="color:#94a3b8;font-weight:400;">(if heading elsewhere after repair)</span></label>
                    <input type="text" name="final_depot" class="form-control @error('final_depot') is-invalid @enderror" value="{{ old('final_depot') }}" placeholder="Leave blank to return to Depot Name above" list="depot-suggestions" autocomplete="off">
                    @error('final_depot')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <datalist id="depot-suggestions">
                    @foreach($depots as $depot)
                    <option value="{{ $depot }}">
                    @endforeach
                </datalist>
                <div class="form-group">
                    <label class="form-label">Department *</label>
                    <input type="text" name="department" class="form-control @error('department') is-invalid @enderror" value="{{ old('department') }}" required>
                    @error('department')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Extension Number *</label>
                    <input type="text" name="contact_person" class="form-control @error('contact_person') is-invalid @enderror" value="{{ old('contact_person') }}" placeholder="e.g. 1205" maxlength="4" pattern="\d{4}" inputmode="numeric" title="Enter a 4-digit extension number" required>
                    @error('contact_person')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Phone Number *</label>
                    <input type="text" name="phone_number" class="form-control @error('phone_number') is-invalid @enderror" value="{{ old('phone_number') }}" required>
                    @error('phone_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
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
                <label class="form-label">Accessories Received *</label>
                <input type="text" name="accessories_received" class="form-control @error('accessories_received') is-invalid @enderror" value="{{ old('accessories_received') }}" placeholder="e.g. Power cable, Mouse" required>
                @error('accessories_received')<div class="invalid-feedback">{{ $message }}</div>@enderror
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

<div id="unsaved-modal-backdrop" style="display:none;position:fixed;inset:0;background:rgba(15,23,42,.55);z-index:9998;"></div>
<div id="unsaved-modal" style="display:none;position:fixed;top:50%;left:50%;transform:translate(-50%,-50%);z-index:9999;background:#fff;border-radius:10px;box-shadow:0 20px 50px rgba(0,0,0,.25);max-width:420px;width:92%;padding:24px;">
    <h3 style="margin:0 0 8px;font-size:1.05rem;color:#1e293b;"><i class="fas fa-exclamation-triangle" style="color:#f59e0b;"></i> Unsaved Job Details</h3>
    <p style="margin:0 0 18px;color:#64748b;font-size:.9rem;">You have unsaved changes on this job form. Save as a draft so you can pick up where you left off, or leave without saving?</p>
    <div style="display:flex;flex-wrap:wrap;gap:8px;justify-content:flex-end;">
        <button type="button" id="um-cancel" class="btn btn-secondary btn-sm">Cancel</button>
        <button type="button" id="um-discard" class="btn btn-danger btn-sm">Discard &amp; Leave</button>
        <button type="button" id="um-save" class="btn btn-primary btn-sm"><i class="fas fa-save"></i> Save as Draft</button>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const lookupUrl = '{{ route("workshop.lookup-serial") }}';

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

    // Wires up serial auto-fill + duplicate-job detection for a single device row.
    // Called once for Device 1 and again for every device row added dynamically.
    function attachDeviceLookup(row) {
        const serialInput = row.querySelector('.device-serial');
        const typeInput   = row.querySelector('.device-type');
        const brandInput  = row.querySelector('.device-brand');
        const spinner     = row.querySelector('.serial-spinner');
        const resultBox   = row.querySelector('.serial-lookup-result');
        const dupAlert    = row.querySelector('.duplicate-job-alert');

        let debounceTimer;

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

        function showDuplicateAlert(job) {
            dupAlert.style.display = 'block';
            dupAlert.innerHTML =
                '<i class="fas fa-triangle-exclamation"></i> This device was already logged before! Job <strong>' + job.job_number + '</strong>'
                + ' handled by <strong>' + job.technician + '</strong>'
                + ' &mdash; status: <strong>' + job.status + '</strong>'
                + (job.date_received ? ' (received ' + job.date_received + ')' : '')
                + '. Please confirm this isn\'t a duplicate entry before proceeding.';
        }

        function clearDuplicateAlert() {
            dupAlert.style.display = 'none';
            dupAlert.innerHTML = '';
        }

        serialInput.addEventListener('input', function () {
            clearTimeout(debounceTimer);
            const val = this.value.trim();
            if (val.length < 3) { clearResult(); clearDuplicateAlert(); return; }

            debounceTimer = setTimeout(function () {
                spinner.style.display = 'block';
                fetch(lookupUrl + '?serial=' + encodeURIComponent(val), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                })
                .then(r => r.json())
                .then(data => {
                    spinner.style.display = 'none';

                    if (data.prior_workshop_job) {
                        showDuplicateAlert(data.prior_workshop_job);
                    } else {
                        clearDuplicateAlert();
                    }

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
    }

    // --- Multi-device management ---
    const devicesContainer = document.getElementById('devices-container');
    const addDeviceBtn     = document.getElementById('add-device-btn');
    const template          = document.getElementById('device-row-template');
    let additionalIndex     = 0;

    function renumberLabels() {
        devicesContainer.querySelectorAll('.device-row .device-label').forEach(function (label, i) {
            // i=0 is the first *additional* row, which is Device 2 overall (Device 1 has a static label)
            label.textContent = 'Device ' + (i + 2);
        });
    }

    addDeviceBtn.addEventListener('click', function () {
        const idx  = additionalIndex++;
        const node = template.content.cloneNode(true);
        const row  = node.querySelector('.device-row');

        row.querySelector('.device-type').name      = 'additional_devices[' + idx + '][equipment_type]';
        row.querySelector('.device-brand').name     = 'additional_devices[' + idx + '][brand_make_model]';
        row.querySelector('.device-serial').name    = 'additional_devices[' + idx + '][serial_number_asset_tag]';
        row.querySelector('.device-condition').name = 'additional_devices[' + idx + '][physical_condition_on_receipt]';

        row.querySelector('.remove-device-btn').addEventListener('click', function () {
            row.remove();
            renumberLabels();
        });

        devicesContainer.appendChild(row);
        renumberLabels();
        attachDeviceLookup(devicesContainer.lastElementChild);
    });

    attachDeviceLookup(devicesContainer.querySelector('.device-row'));
})();

(function () {
    const form = document.querySelector('form[action="{{ route('workshop.store') }}"]');
    if (!form) return;

    const DRAFT_KEY      = 'workshopNewJobDraft_v1';
    const banner         = document.getElementById('draft-banner');
    const draftTimeEl    = document.getElementById('draft-time');
    const modalBackdrop  = document.getElementById('unsaved-modal-backdrop');
    const modal          = document.getElementById('unsaved-modal');

    let dirty      = false;
    let submitting = false;
    let pendingHref = null;

    form.addEventListener('input',  () => { dirty = true; });
    form.addEventListener('change', () => { dirty = true; });
    form.addEventListener('submit', () => { submitting = true; localStorage.removeItem(DRAFT_KEY); });

    function collectFormData() {
        const data = {};
        Array.from(form.elements).forEach(el => {
            if (!el.name || el.name === '_token') return;
            if (el.type === 'checkbox' || el.type === 'radio') {
                if (el.checked) data[el.name] = el.value;
            } else {
                data[el.name] = el.value;
            }
        });
        return data;
    }

    function saveDraft() {
        localStorage.setItem(DRAFT_KEY, JSON.stringify({ data: collectFormData(), savedAt: new Date().toISOString() }));
    }

    function restoreDraft(payload) {
        const data = payload.data || {};
        Object.keys(data).forEach(name => {
            const el = form.elements[name];
            if (!el) return;
            if (el.type === 'checkbox' || el.type === 'radio') {
                el.checked = (el.value === data[name]);
            } else {
                el.value = data[name];
            }
        });
        dirty = true;
    }

    function clearDraft() { localStorage.removeItem(DRAFT_KEY); }

    const existing = localStorage.getItem(DRAFT_KEY);
    if (existing) {
        try {
            const payload = JSON.parse(existing);
            draftTimeEl.textContent = new Date(payload.savedAt).toLocaleString();
            banner.style.display = 'flex';
            document.getElementById('draft-restore').addEventListener('click', function () {
                restoreDraft(payload);
                banner.style.display = 'none';
            });
            document.getElementById('draft-discard').addEventListener('click', function () {
                clearDraft();
                banner.style.display = 'none';
            });
        } catch (e) { clearDraft(); }
    }

    // Tab/window close or refresh — browsers only allow their own native
    // "leave site?" prompt here (custom buttons aren't permitted), so we
    // silently save a draft first as a safety net in case they leave anyway.
    window.addEventListener('beforeunload', function (e) {
        if (!dirty || submitting) return;
        saveDraft();
        e.preventDefault();
        e.returnValue = '';
    });

    function openModal(href) {
        pendingHref = href;
        modalBackdrop.style.display = 'block';
        modal.style.display = 'block';
    }
    function closeModal() {
        pendingHref = null;
        modalBackdrop.style.display = 'none';
        modal.style.display = 'none';
    }

    document.getElementById('um-cancel').addEventListener('click', closeModal);
    modalBackdrop.addEventListener('click', closeModal);

    document.getElementById('um-discard').addEventListener('click', function () {
        dirty = false;
        clearDraft();
        const href = pendingHref;
        closeModal();
        if (href) window.location.href = href;
    });

    document.getElementById('um-save').addEventListener('click', function () {
        saveDraft();
        dirty = false;
        const href = pendingHref;
        closeModal();
        if (href) window.location.href = href;
    });

    // In-app navigation (Back/Cancel links, sidebar, tabs to other pages)
    document.addEventListener('click', function (e) {
        if (!dirty) return;
        const link = e.target.closest('a[href]');
        if (!link) return;
        const href = link.getAttribute('href');
        if (!href || href.startsWith('#') || href.startsWith('javascript:')) return;
        if (link.target === '_blank' || e.metaKey || e.ctrlKey || e.shiftKey || e.button === 1) return;

        e.preventDefault();
        openModal(link.href);
    });
})();
</script>
@endpush
@endsection
