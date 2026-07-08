@extends('layouts.app')
@section('title', auth()->user()->isTechnician() ? 'Request Disposal' : 'New Disposal')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-trash-alt"></i> {{ auth()->user()->isTechnician() ? 'Request Equipment Disposal' : 'New Equipment Disposal' }}</h2>
    <a href="{{ route('disposal.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

@if(auth()->user()->isTechnician())
<div class="alert alert-info"><i class="fas fa-info-circle"></i> You are submitting a disposal request. A HOD or Super Admin must approve it before the equipment is disposed.</div>
@endif

<div class="card">
    <div class="card-body">
        <form method="POST" action="{{ route('disposal.store') }}">
            @csrf
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Asset Tag / Serial No.</label>
                    <div style="position:relative;">
                        <input type="text" name="asset_tag_serial_no" id="asset_tag_serial_no"
                               class="form-control" value="{{ old('asset_tag_serial_no') }}"
                               placeholder="Type serial — details auto-fill"
                               autocomplete="off">
                        <div id="disposal-serial-spinner" style="display:none;position:absolute;right:10px;top:50%;transform:translateY(-50%);color:#94a3b8;">
                            <i class="fas fa-circle-notch fa-spin"></i>
                        </div>
                    </div>
                    <div id="disposal-serial-result" style="display:none;margin-top:6px;padding:8px 12px;border-radius:8px;font-size:.8rem;"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Asset Description *</label>
                    <input type="text" name="asset_description" id="asset_description"
                           class="form-control @error('asset_description') is-invalid @enderror"
                           value="{{ old('asset_description') }}" required>
                    @error('asset_description')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Model / Brand</label>
                    <input type="text" name="model_brand" id="model_brand"
                           class="form-control" value="{{ old('model_brand') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Department / User</label>
                    <input type="text" name="department_user" id="department_user"
                           class="form-control" value="{{ old('department_user') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Date Acquired</label>
                    <input type="date" name="date_acquired" id="date_acquired"
                           class="form-control" value="{{ old('date_acquired') }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Condition at Disposal</label>
                    <input type="text" name="condition_at_disposal" class="form-control" value="{{ old('condition_at_disposal') }}" placeholder="e.g. Beyond repair, Obsolete">
                </div>
                @if(!auth()->user()->isTechnician())
                <div class="form-group">
                    <label class="form-label">Disposal Method</label>
                    <select name="disposal_method" class="form-control">
                        <option value="">— Select —</option>
                        @foreach(['Auction','Donation','Destruction','Write-Off','Trade-In','Recycling'] as $m)
                        <option value="{{ $m }}" {{ old('disposal_method') === $m ? 'selected' : '' }}>{{ $m }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="form-group">
                    <label class="form-label">Disposal Value (USD)</label>
                    <input type="number" name="disposal_value" class="form-control" value="{{ old('disposal_value') }}" step="0.01" min="0">
                </div>
                @endif
            </div>

            <div class="form-group">
                <label class="form-label">Reason for Disposal *</label>
                <textarea name="reason_for_disposal" class="form-control @error('reason_for_disposal') is-invalid @enderror" required>{{ old('reason_for_disposal') }}</textarea>
                @error('reason_for_disposal')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="form-group">
                <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                    <input type="checkbox" name="data_wiped_destroyed" value="1" {{ old('data_wiped_destroyed') ? 'checked' : '' }}>
                    <span class="form-label" style="margin:0;">Data Wiped / Destroyed</span>
                </label>
            </div>

            <div class="form-group">
                <label class="form-label">Remarks</label>
                <textarea name="remarks" class="form-control">{{ old('remarks') }}</textarea>
            </div>

            <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i>
                {{ auth()->user()->isTechnician() ? 'Submit Request' : 'Save Disposal Record' }}
            </button>
            <a href="{{ route('disposal.index') }}" class="btn btn-secondary">Cancel</a>
        </form>
    </div>
</div>
@push('scripts')
<script>
(function () {
    const serialInput  = document.getElementById('asset_tag_serial_no');
    const descInput    = document.getElementById('asset_description');
    const brandInput   = document.getElementById('model_brand');
    const deptInput    = document.getElementById('department_user');
    const dateInput    = document.getElementById('date_acquired');
    const spinner      = document.getElementById('disposal-serial-spinner');
    const resultBox    = document.getElementById('disposal-serial-result');
    const lookupUrl    = '{{ route("disposal.lookup-serial") }}';

    let debounceTimer;

    const autoFields = [descInput, brandInput, deptInput, dateInput];

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
        resultBox.style.display    = 'block';
        resultBox.style.background = type === 'success' ? '#d1fae5' : '#f1f5f9';
        resultBox.style.border     = '1px solid ' + (type === 'success' ? '#a7f3d0' : '#e2e8f0');
        resultBox.style.color      = type === 'success' ? '#065f46' : '#64748b';
        resultBox.innerHTML        = msg;
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
                    showResult('<i class="fas fa-info-circle"></i> Serial not found in the register — fill in details manually.', 'info');
                    return;
                }

                if (!descInput.dataset.manual)  { descInput.value  = data.asset_description || ''; greyOut(descInput);  }
                if (!brandInput.dataset.manual) { brandInput.value = data.model_brand        || ''; greyOut(brandInput); }
                if (!deptInput.dataset.manual)  { deptInput.value  = data.department_user    || ''; greyOut(deptInput);  }
                if (!dateInput.dataset.manual)  { dateInput.value  = data.date_acquired      || ''; greyOut(dateInput);  }

                showResult(
                    '<i class="fas fa-check-circle"></i> Found: <strong>' + data.asset_description + '</strong>'
                    + (data.model_brand     ? ' &mdash; ' + data.model_brand                   : '')
                    + (data.department_user ? ' &nbsp;|&nbsp; <strong>Currently with:</strong> ' + data.department_user : ''),
                    'success'
                );
            })
            .catch(() => { spinner.style.display = 'none'; });
        }, 450);
    });

    // Typing in any auto-filled field removes the grey and marks it as manual
    autoFields.forEach(function (el) {
        el.addEventListener('input', function () {
            this.dataset.manual = '1';
            clearGrey(this);
        });
    });
})();
</script>
@endpush
@endsection
