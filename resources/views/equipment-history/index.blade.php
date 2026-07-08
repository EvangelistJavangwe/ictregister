@extends('layouts.app')
@section('title', 'Equipment History')

@section('content')
<div class="page-header">
    <h2><i class="fas fa-history"></i> Equipment History Tracker</h2>
</div>

<div class="card">
    <div class="card-body">
        <form method="GET" class="d-flex gap-3 flex-wrap align-center" id="search-form">
            <div class="form-group" style="margin:0;min-width:200px;">
                <label class="form-label">Search By</label>
                <select name="type" id="search-type" class="form-control" onchange="updateSearchField()">
                    <option value="serial_number" {{ request('type','serial_number') === 'serial_number' ? 'selected' : '' }}>Serial Number</option>
                    <option value="asset_tag"     {{ request('type') === 'asset_tag' ? 'selected' : '' }}>Asset Tag</option>
                </select>
            </div>
            <div class="form-group" style="margin:0;flex:2;min-width:280px;">
                <label class="form-label" id="identifier-label">Serial Number</label>
                <input type="text"
                    name="identifier"
                    id="identifier-input"
                    class="form-control"
                    value="{{ request('identifier') }}"
                    placeholder="Enter serial number..."
                    autocomplete="off">
            </div>
            <div style="align-self:flex-end;">
                <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> Track Equipment</button>
                @if(request('identifier'))
                <a href="{{ route('equipment-history.index') }}" class="btn btn-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>
</div>

@if($searchValue !== null)
<div class="card mt-2">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <h3 style="display:flex;align-items:center;gap:8px;">
            @if($searchType === 'asset_tag')
                <i class="fas fa-tag" style="color:#2563eb;"></i>
                Asset Tag: <strong>{{ $searchValue }}</strong>
                <span class="badge badge-info">Asset Tag</span>
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
const labels = {
    serial_number: { label: 'Serial Number', placeholder: 'Enter serial number...' },
    asset_tag:     { label: 'Asset Tag',     placeholder: 'Enter asset tag number...' },
};

function updateSearchField() {
    const type = document.getElementById('search-type').value;
    const cfg  = labels[type] || labels.serial_number;
    document.getElementById('identifier-label').textContent  = cfg.label;
    document.getElementById('identifier-input').placeholder  = cfg.placeholder;
}

// Run on page load to match current selection
updateSearchField();
</script>
@endpush
@endsection
