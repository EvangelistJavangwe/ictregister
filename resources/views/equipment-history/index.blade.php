@extends('layouts.app')
@section('title', 'Equipment History')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-history"></i> Equipment History Tracker</h2>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="d-flex gap-3 flex-wrap align-center" id="search-form" autocomplete="off">
            <div class="form-group" style="margin:0;flex:2;min-width:320px;position:relative;">
                <label class="form-label">Search Equipment</label>
                <input type="hidden" name="type" id="search-type" value="{{ request('type', 'auto') }}">
                <input type="text"
                    name="identifier"
                    id="identifier-input"
                    class="form-control"
                    value="{{ request('identifier') }}"
                    placeholder="Type a serial number, asset tag, item name, brand, or recipient..."
                    autocomplete="off">
                <div id="suggest-dropdown" style="display:none;position:absolute;top:100%;left:0;right:0;z-index:20;background:#fff;border:1px solid #e2e8f0;border-radius:8px;margin-top:4px;max-height:320px;overflow-y:auto;box-shadow:0 4px 12px rgba(0,0,0,.08);"></div>
            </div>
            <div style="align-self:flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Track Equipment</button>
                @if(request('identifier'))
                <a href="{{ route('equipment-history.index') }}" class="btn btn-secondary">Clear</a>
                @endif
            </div>
        </form>

        <hr class="divider">

        <form method="POST" action="{{ route('equipment-history.ask') }}" class="d-flex gap-3 flex-wrap align-center">
            @csrf
            <div class="form-group" style="margin:0;flex:2;min-width:320px;">
                <label class="form-label"><i class="fas fa-wand-magic-sparkles"></i> Ask AI</label>
                <input type="text" name="query" class="form-control"
                    placeholder="e.g. &quot;who has the HP laptop with serial ABC123?&quot;">
            </div>
            <div style="align-self:flex-end;">
                <button type="submit" class="btn btn-secondary"><i class="fas fa-sparkles"></i> Ask</button>
            </div>
        </form>
    </div>
</div>

@if(session('ai_summary'))
<div class="alert alert-info mt-2">
    <i class="fas fa-wand-magic-sparkles"></i> <strong>AI Summary:</strong> {{ session('ai_summary') }}
</div>
@elseif(session('ai_summary_unavailable'))
<div class="alert alert-warning mt-2">
    <i class="fas fa-exclamation-triangle"></i> Found the equipment below, but an AI summary isn't available right now.
</div>
@endif

@if($searchValue !== null)
<div class="card mt-2">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <h3 style="display:flex;align-items:center;gap:8px;">
            @if($searchType === 'asset_tag')
                <i class="fas fa-tag" style="color:#2563eb;"></i>
                Asset Tag: <strong>{{ $searchValue }}</strong>
                <span class="badge badge-info">Asset Tag</span>
            @elseif($searchType === 'cross_ref_no')
                <i class="fas fa-hashtag" style="color:#2563eb;"></i>
                Reference No: <strong>{{ $searchValue }}</strong>
                <span class="badge badge-info">Cross Ref No</span>
            @else
                <i class="fas fa-barcode" style="color:#2563eb;"></i>
                Serial Number: <strong>{{ $searchValue }}</strong>
                <span class="badge badge-info">Serial Number</span>
            @endif
        </h3>
        <span class="text-muted text-sm">{{ $history?->count() ?? 0 }} event(s) found</span>
    </div>

    @if(($history?->count() ?? 0) > 0)
    @php
        $firstEvent = $history->last();
        $lastEvent  = $history->first();
    @endphp

    {{-- Summary strip --}}
    <div style="background:#f8fafc;border-bottom:1px solid #e2e8f0;padding:14px 20px;display:flex;flex-wrap:wrap;gap:24px;align-items:center;">
        <div>
            <div class="text-sm text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.04em;">First Seen</div>
            <div class="text-sm">{{ $firstEvent?->event_date?->format('d M Y') ?? '—' }}</div>
        </div>
        <div>
            <div class="text-sm text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Last Event</div>
            <div class="text-sm">{{ $lastEvent?->event_date?->format('d M Y') ?? '—' }}</div>
        </div>
        <div>
            <div class="text-sm text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Total Events</div>
            <div class="text-sm">{{ $history->count() }}</div>
        </div>
        <div>
            <div class="text-sm text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Latest Status</div>
            <div><span class="badge badge-success">{{ $lastEvent?->event_type ?? '—' }}</span></div>
        </div>
        @if($receivingRegister)
        <div style="margin-left:auto;">
            <a href="{{ route('equipment-receiving.show', $receivingRegister) }}" class="btn btn-sm btn-secondary" style="font-size:.82rem;">
                <i class="fas fa-box-open"></i> View Delivery Record
            </a>
        </div>
        @endif
    </div>

    {{-- Disposal-in-progress banner — replaces current assignment display --}}
    @if($activeDisposal)
    <div style="background:#fef2f2;border-bottom:2px solid #fca5a5;padding:14px 20px;display:flex;flex-wrap:wrap;gap:24px;align-items:center;">
        <div style="display:flex;align-items:center;gap:8px;font-weight:700;color:#b91c1c;font-size:.85rem;text-transform:uppercase;letter-spacing:.04em;">
            <i class="fas fa-trash-alt"></i> Disposal In Progress
        </div>
        <div>
            <div class="text-sm text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Ref No.</div>
            <div class="text-sm">
                @if($activeDisposal->disposal_ref_no)
                    <code style="background:#fee2e2;color:#b91c1c;padding:2px 6px;border-radius:4px;">{{ $activeDisposal->disposal_ref_no }}</code>
                @else
                    <span class="text-muted">Pending ref</span>
                @endif
            </div>
        </div>
        <div>
            <div class="text-sm text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Status</div>
            <span class="badge badge-danger">{{ $activeDisposal->status }}</span>
        </div>
        <div>
            <div class="text-sm text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Reason</div>
            <div class="text-sm" style="max-width:320px;">{{ $activeDisposal->reason_for_disposal }}</div>
        </div>
        <div style="margin-left:auto;">
            <a href="{{ route('disposal.show', $activeDisposal) }}" class="btn btn-sm btn-secondary" style="font-size:.82rem;">
                <i class="fas fa-eye"></i> View Disposal Record
            </a>
        </div>
    </div>

    {{-- Current Assignment box (no disposal active) --}}
    @elseif($currentAssignment)
    <div style="background:#eff6ff;border-bottom:2px solid #bfdbfe;padding:14px 20px;display:flex;flex-wrap:wrap;gap:32px;align-items:center;">
        <div style="display:flex;align-items:center;gap:8px;font-weight:700;color:#1d4ed8;font-size:.85rem;text-transform:uppercase;letter-spacing:.04em;">
            <i class="fas fa-map-marker-alt"></i> Current Assignment
        </div>
        <div>
            <div class="text-sm text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Assigned To</div>
            <div style="font-weight:600;color:#1e293b;">{{ $currentAssignment->recipient_name }}</div>
        </div>
        @if($currentAssignment->depot_department)
        <div>
            <div class="text-sm text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Depot / Department</div>
            <div style="font-weight:600;color:#1e293b;">{{ $currentAssignment->depot_department }}</div>
        </div>
        @endif
        <div>
            <div class="text-sm text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Type</div>
            <span class="badge {{ $currentAssignment->redistribution_type === 'Individual' ? 'badge-info' : 'badge-purple' }}">
                {{ $currentAssignment->redistribution_type }}
            </span>
        </div>
        <div>
            <div class="text-sm text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Since</div>
            <div class="text-sm">{{ $currentAssignment->redistribution_date?->format('d M Y') }}</div>
        </div>
        <div>
            <div class="text-sm text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Ref</div>
            <div class="text-sm"><code style="background:#dbeafe;padding:2px 6px;border-radius:4px;">{{ $currentAssignment->redistribution_ref_no }}</code></div>
        </div>
        @if(!empty($currentAssignment->asset_tags))
            @php $assetTag = $currentAssignment->asset_tags[$searchValue] ?? null; @endphp
            @if($assetTag)
            <div>
                <div class="text-sm text-muted" style="font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Asset Tag</div>
                <div><span style="background:#dbeafe;color:#1d4ed8;padding:2px 8px;border-radius:4px;font-weight:700;">{{ $assetTag }}</span></div>
            </div>
            @endif
        @endif
        @if($receivingRegister)
        <div style="margin-left:auto;">
            <a href="{{ route('equipment-receiving.redistribute', $receivingRegister) }}?serial={{ urlencode($searchValue) }}"
               class="btn btn-warning"
               style="font-size:.85rem;">
                <i class="fas fa-share-square"></i> Transfer to New Holder
            </a>
        </div>
        @endif
    </div>
    @elseif($receivingRegister)
    <div style="background:#fef9c3;border-bottom:1px solid #fde68a;padding:10px 20px;font-size:.85rem;color:#92400e;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <span><i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>
        This unit has not been redistributed yet — it is still in the receiving stock.</span>
        <a href="{{ route('equipment-receiving.redistribute', $receivingRegister) }}?serial={{ urlencode($searchValue) }}" class="btn btn-sm btn-primary" style="font-size:.82rem;">
            <i class="fas fa-share-square"></i> Assign / Redistribute Now
        </a>
    </div>
    @else
    <div style="background:#f1f5f9;border-bottom:1px solid #e2e8f0;padding:10px 20px;font-size:.85rem;color:#475569;display:flex;align-items:center;gap:16px;flex-wrap:wrap;">
        <span><i class="fas fa-info-circle" style="margin-right:6px;"></i>
        No current assignment on record. This asset is not part of the Helpdesk Registry (Equipment Receiving) stock.</span>
    </div>
    @endif
    @endif

    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Date / Time</th>
                    <th>Event</th>
                    @if($searchType === 'asset_tag')
                        <th>Asset Tag</th>
                    @else
                        <th>Serial Number</th>
                    @endif
                    <th>Assigned To</th>
                    <th>Depot / Department</th>
                    <th>Description</th>
                    <th>Performed By</th>
                    <th>Related Record</th>
                </tr>
            </thead>
            <tbody>
            @forelse($history ?? [] as $event)
            <tr>
                <td class="text-sm" style="white-space:nowrap;">{{ $event->event_date?->format('d M Y H:i') }}</td>
                <td>
                    @php
                        $badgeClass = match(true) {
                            str_contains($event->event_type, 'Received')      => 'badge-success',
                            str_contains($event->event_type, 'Redistributed') => 'badge-purple',
                            str_contains($event->event_type, 'Inspection')    => 'badge-info',
                            str_contains($event->event_type, 'Disposed')      => 'badge-danger',
                            default                                            => 'badge-secondary',
                        };
                    @endphp
                    <span class="badge {{ $badgeClass }}">{{ $event->event_type }}</span>
                </td>
                <td>
                    <code style="font-size:.82rem;background:#f1f5f9;padding:2px 8px;border-radius:4px;">{{ $event->identifier_value }}</code>
                </td>
                @php
                    $redist = ($event->related_table === 'equipment_redistributions' && $redistMap->has($event->related_id))
                        ? $redistMap->get($event->related_id)
                        : null;
                @endphp
                <td class="text-sm">
                    @if($redist)
                        <span style="font-weight:600;">{{ $redist->recipient_name }}</span>
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td class="text-sm">
                    @if($redist && $redist->depot_department)
                        {{ $redist->depot_department }}
                    @else
                        <span class="text-muted">—</span>
                    @endif
                </td>
                <td>{{ $event->event_description }}</td>
                <td class="text-sm">{{ $event->performed_by_name ?? '—' }}</td>
                <td class="text-sm text-muted">
                    {{ $event->related_table ? str_replace('_',' ',ucwords($event->related_table,' _')).' #'.$event->related_id : '—' }}
                </td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center text-muted" style="padding:30px;">
                    @if($searchType === 'asset_tag')
                        No history found for asset tag "<strong>{{ $searchValue }}</strong>".
                    @else
                        No history found for serial number "<strong>{{ $searchValue }}</strong>".
                    @endif
                </td>
            </tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endif

@push('scripts')
<script>
(function() {
    const input    = document.getElementById('identifier-input');
    const typeField= document.getElementById('search-type');
    const dropdown = document.getElementById('suggest-dropdown');
    let debounceId = null;
    let lastQuery  = '';

    function hideDropdown() {
        dropdown.style.display = 'none';
        dropdown.innerHTML = '';
    }

    function renderResults(results) {
        if (!results.length) {
            dropdown.innerHTML = '<div class="text-sm text-muted" style="padding:10px 14px;">No matches found</div>';
            dropdown.style.display = 'block';
            return;
        }
        dropdown.innerHTML = results.map(r => `
            <div class="suggest-item" data-type="${r.identifier_type}" data-value="${r.identifier_value.replace(/"/g, '&quot;')}"
                 style="padding:8px 14px;cursor:pointer;border-bottom:1px solid #f1f5f9;">
                <div style="font-weight:600;font-size:.85rem;color:#1e293b;">${r.label}</div>
                <div class="text-sm text-muted">${r.meta ?? ''}</div>
            </div>
        `).join('');
        dropdown.style.display = 'block';

        dropdown.querySelectorAll('.suggest-item').forEach(el => {
            el.addEventListener('mouseenter', () => el.style.background = '#f8fafc');
            el.addEventListener('mouseleave', () => el.style.background = '#fff');
            el.addEventListener('click', () => {
                typeField.value = el.dataset.type;
                input.value = el.dataset.value;
                hideDropdown();
                document.getElementById('search-form').submit();
            });
        });
    }

    input.addEventListener('input', function() {
        typeField.value = 'auto';
        const q = input.value.trim();
        clearTimeout(debounceId);
        if (q.length < 2) {
            hideDropdown();
            return;
        }
        debounceId = setTimeout(() => {
            lastQuery = q;
            fetch(`{{ route('equipment-history.suggest') }}?q=` + encodeURIComponent(q))
                .then(res => res.json())
                .then(data => {
                    if (q === lastQuery) renderResults(data.results || []);
                })
                .catch(() => hideDropdown());
        }, 300);
    });

    document.addEventListener('click', function(e) {
        if (!e.target.closest('#suggest-dropdown') && e.target !== input) hideDropdown();
    });
})();
</script>
@endpush
@endsection
