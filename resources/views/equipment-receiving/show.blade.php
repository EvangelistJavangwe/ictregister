@extends('layouts.app')
@section('title', 'Record: '.$equipmentReceiving->cross_ref_no)

@section('content')
@php
    $totalInspected = $equipmentReceiving->totalInspected();
    $pendingInspect = $equipmentReceiving->pendingInspection();
    $deployed       = $equipmentReceiving->deployedCount();
    $available      = $equipmentReceiving->availableStock();
    $qty            = $equipmentReceiving->qty_received;
@endphp

<div class="page-header">
    <div>
        <h2><i class="fas fa-box-open"></i> {{ $equipmentReceiving->cross_ref_no }}</h2>
        <div style="display:flex;gap:6px;flex-wrap:wrap;margin-top:4px;">
            @if($pendingInspect <= 0)
                <span class="badge badge-success"><i class="fas fa-check-double"></i> Fully Inspected</span>
            @elseif($totalInspected > 0)
                <span class="badge badge-warning">Partially Inspected ({{ $totalInspected }}/{{ $qty }})</span>
            @else
                <span class="badge badge-secondary">Pending Inspection</span>
            @endif
            @if($deployed >= $qty)
                <span class="badge badge-success"><i class="fas fa-user-check"></i> Fully Deployed</span>
            @elseif($deployed > 0)
                <span class="badge badge-info"><i class="fas fa-user-check"></i> {{ $deployed }}/{{ $qty }} Deployed</span>
            @endif
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        @if($pendingInspect > 0)
        <a href="{{ route('equipment-receiving.inspect', $equipmentReceiving) }}" class="btn btn-primary">
            <i class="fas fa-clipboard-check"></i> Inspect &amp; Transfer
        </a>
        @endif
        <a href="{{ route('equipment-receiving.redistribute', $equipmentReceiving) }}" class="btn btn-success">
            <i class="fas fa-share-square"></i> Redistribute
        </a>
        <a href="{{ route('equipment-receiving.edit', $equipmentReceiving) }}" class="btn btn-secondary">
            <i class="fas fa-edit"></i> Edit
        </a>
        <a href="{{ route('equipment-receiving.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Back
        </a>
    </div>
</div>

{{-- Stock Flow Summary --}}
<div class="card mb-2">
    <div class="card-header"><h3><i class="fas fa-layer-group"></i> Stock Flow</h3></div>
    <div class="card-body" style="display:flex;flex-wrap:wrap;gap:0;align-items:stretch;">
        @php
            $steps = [
                ['label'=>'Received',          'value'=>$qty,            'color'=>'#2563eb', 'icon'=>'fa-truck'],
                ['label'=>'Inspected',          'value'=>$totalInspected, 'color'=>'#16a34a', 'icon'=>'fa-clipboard-check'],
                ['label'=>'Pending Inspection', 'value'=>$pendingInspect, 'color'=>'#d97706', 'icon'=>'fa-hourglass-half'],
                ['label'=>'Deployed',           'value'=>$deployed,       'color'=>'#7c3aed', 'icon'=>'fa-user-check'],
                ['label'=>'Unassigned',         'value'=>$available,      'color'=>$available>0?'#0891b2':'#94a3b8', 'icon'=>'fa-cubes'],
            ];
        @endphp
        @foreach($steps as $i => $step)
        <div style="flex:1;min-width:120px;text-align:center;padding:16px 8px;{{ $i < count($steps)-1 ? 'border-right:1px solid #f1f5f9;' : '' }}">
            <div style="font-size:1.8rem;font-weight:800;color:{{ $step['color'] }};">{{ $step['value'] }}</div>
            <div style="font-size:.75rem;color:#64748b;margin-top:4px;font-weight:600;text-transform:uppercase;letter-spacing:.04em;">
                <i class="fas {{ $step['icon'] }}" style="margin-right:4px;"></i>{{ $step['label'] }}
            </div>
        </div>
        @if($i < count($steps)-1)
        <div style="display:flex;align-items:center;padding:0 4px;color:#cbd5e1;font-size:1.1rem;">›</div>
        @endif
        @endforeach
    </div>
    @if($pendingInspect > 0)
    <div style="background:#fff7ed;border-top:1px solid #fed7aa;padding:10px 20px;border-radius:0 0 10px 10px;font-size:.85rem;color:#92400e;">
        <i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>
        <strong>{{ $pendingInspect }}</strong> item(s) still awaiting inspection.
        <a href="{{ route('equipment-receiving.inspect', $equipmentReceiving) }}" style="color:#92400e;font-weight:600;margin-left:8px;">Inspect now →</a>
    </div>
    @endif
</div>

{{-- Delivery & Equipment --}}
<div class="grid-2 mb-2">
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-truck"></i> Delivery Information</h3></div>
        <div class="detail-grid">
            <div class="detail-item"><div class="detail-label">Cross Ref No.</div><div class="detail-value">{{ $equipmentReceiving->cross_ref_no }}</div></div>
            <div class="detail-item"><div class="detail-label">Date Received</div><div class="detail-value">{{ $equipmentReceiving->date_received?->format('d M Y') }}</div></div>
            <div class="detail-item"><div class="detail-label">Supplier</div><div class="detail-value">{{ $equipmentReceiving->supplier }}</div></div>
            <div class="detail-item"><div class="detail-label">Delivery Note No.</div><div class="detail-value">{{ $equipmentReceiving->delivery_note_no ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">LPO / PO No.</div><div class="detail-value">{{ $equipmentReceiving->po_no ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Recorded By</div><div class="detail-value">{{ $equipmentReceiving->creator ? $equipmentReceiving->creator->firstname.' '.$equipmentReceiving->creator->lastname : '—' }}</div></div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3><i class="fas fa-desktop"></i> Equipment Details</h3></div>
        <div class="detail-grid">
            <div class="detail-item"><div class="detail-label">Item Description</div><div class="detail-value">{{ $equipmentReceiving->item_description }}</div></div>
            <div class="detail-item"><div class="detail-label">Brand / Model</div><div class="detail-value">{{ $equipmentReceiving->brand_model ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Cost Price</div><div class="detail-value">{{ $equipmentReceiving->cost_price !== null ? 'USD '.number_format($equipmentReceiving->cost_price, 2) : '—' }}</div></div>
            <div class="detail-item">
                <div class="detail-label">Serial Numbers <span class="text-muted" style="font-weight:400;">(available in stock)</span></div>
                <div class="detail-value">
                    @php $availableSerials = $equipmentReceiving->availableSerials(); @endphp
                    @if(!empty($availableSerials))
                        @foreach($availableSerials as $i => $sn)
                            <div style="display:flex;align-items:center;gap:6px;{{ $loop->first ? '' : 'margin-top:3px;' }}">
                                <span style="font-size:.7rem;color:#94a3b8;min-width:16px;">{{ $i+1 }}.</span>
                                <code style="font-size:.82rem;background:#f1f5f9;padding:1px 6px;border-radius:4px;">{{ $sn }}</code>
                            </div>
                        @endforeach
                    @elseif(empty($equipmentReceiving->serial_numbers) && $equipmentReceiving->serial_number)
                        <code style="font-size:.82rem;background:#f1f5f9;padding:1px 6px;border-radius:4px;">{{ $equipmentReceiving->serial_number }}</code>
                    @else
                        <span class="text-muted">— none remaining, see Redistribution History below —</span>
                    @endif
                </div>
            </div>
            <div class="detail-item"><div class="detail-label">Qty Received</div><div class="detail-value" style="font-size:1.1rem;font-weight:700;color:#2563eb;">{{ $equipmentReceiving->qty_received }}</div></div>
            <div class="detail-item"><div class="detail-label">Accessories</div><div class="detail-value">{{ $equipmentReceiving->accessories_included ?? '—' }}</div></div>
        </div>
        @if($equipmentReceiving->remarks)
        <div class="card-body" style="border-top:1px solid #f1f5f9;">
            <span class="text-sm text-muted" style="font-weight:600;">Remarks:</span>
            <p class="mt-1 text-sm">{{ $equipmentReceiving->remarks }}</p>
        </div>
        @endif
    </div>
</div>

{{-- Receiving Signatures --}}
@if($equipmentReceiving->user_signature || $equipmentReceiving->inspector_signature)
<div class="card mb-2">
    <div class="card-header"><h3><i class="fas fa-signature"></i> Receiving Signatures</h3></div>
    <div class="card-body" style="display:flex;gap:32px;flex-wrap:wrap;">
        @if($equipmentReceiving->user_signature)
        <div>
            <div class="text-sm text-muted mb-1" style="font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Receiver</div>
            <img src="{{ $equipmentReceiving->user_signature }}" style="max-height:80px;border:1px solid #e2e8f0;border-radius:6px;">
        </div>
        @endif
        @if($equipmentReceiving->inspector_signature)
        <div>
            <div class="text-sm text-muted mb-1" style="font-weight:600;text-transform:uppercase;letter-spacing:.04em;">Authorising Officer</div>
            <img src="{{ $equipmentReceiving->inspector_signature }}" style="max-height:80px;border:1px solid #e2e8f0;border-radius:6px;">
        </div>
        @endif
    </div>
</div>
@endif

{{-- Warranty --}}
@if($equipmentReceiving->warranty_end_date)
@php
    $wStatus  = $equipmentReceiving->warrantyStatus();
    $wDays    = $equipmentReceiving->warrantyDaysLeft();
    $wColors  = ['active'=>['bg'=>'#dcfce7','color'=>'#16a34a','icon'=>'fa-shield-alt'],
                 'expiring'=>['bg'=>'#fef9c3','color'=>'#ca8a04','icon'=>'fa-exclamation-triangle'],
                 'expired'=>['bg'=>'#fee2e2','color'=>'#dc2626','icon'=>'fa-times-circle']];
    $wc = $wColors[$wStatus] ?? ['bg'=>'#f1f5f9','color'=>'#64748b','icon'=>'fa-shield-alt'];
@endphp
<div class="card mb-2">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <h3><i class="fas fa-shield-alt" style="color:#16a34a;margin-right:6px;"></i> Warranty</h3>
        <span class="badge" style="background:{{ $wc['bg'] }};color:{{ $wc['color'] }};font-size:.8rem;padding:5px 12px;">
            <i class="fas {{ $wc['icon'] }}"></i>
            @if($wStatus === 'active') Under Warranty ({{ $wDays }} day{{ $wDays == 1 ? '' : 's' }} left)
            @elseif($wStatus === 'expiring') Expiring Soon ({{ $wDays }} day{{ $wDays == 1 ? '' : 's' }} left)
            @else Warranty Expired
            @endif
        </span>
    </div>
    <div class="detail-grid">
        <div class="detail-item">
            <div class="detail-label">Warranty Start</div>
            <div class="detail-value">{{ $equipmentReceiving->warranty_start_date?->format('d M Y') ?? '—' }}</div>
        </div>
        <div class="detail-item">
            <div class="detail-label">Warranty Expiry</div>
            <div class="detail-value" style="font-weight:700;color:{{ $wc['color'] }};">
                {{ $equipmentReceiving->warranty_end_date->format('d M Y') }}
            </div>
        </div>
        @if($equipmentReceiving->warranty_notes)
        <div class="detail-item" style="grid-column:span 2;">
            <div class="detail-label">Warranty Notes</div>
            <div class="detail-value">{{ $equipmentReceiving->warranty_notes }}</div>
        </div>
        @endif
    </div>
    @if($wStatus === 'expired')
    <div style="background:#fff7ed;border-top:1px solid #fed7aa;padding:10px 20px;border-radius:0 0 10px 10px;font-size:.85rem;color:#92400e;">
        <i class="fas fa-exclamation-triangle" style="margin-right:6px;"></i>
        Warranty expired <strong>{{ now()->diffInDays($equipmentReceiving->warranty_end_date) }} day{{ now()->diffInDays($equipmentReceiving->warranty_end_date) == 1 ? '' : 's' }}</strong> ago. This event has been recorded in the asset tracking history.
    </div>
    @elseif($wStatus === 'expiring')
    <div style="background:#fefce8;border-top:1px solid #fef08a;padding:10px 20px;border-radius:0 0 10px 10px;font-size:.85rem;color:#854d0e;">
        <i class="fas fa-clock" style="margin-right:6px;"></i>
        Warranty expires in <strong>{{ $wDays }} day{{ $wDays == 1 ? '' : 's' }}</strong>. Consider renewing or replacing this asset.
    </div>
    @endif
</div>
@endif

{{-- Inspection Records --}}
<div class="card mb-2">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <h3><i class="fas fa-clipboard-check"></i> Inspection &amp; Transfer Records</h3>
        @if($pendingInspect > 0)
        <a href="{{ route('equipment-receiving.inspect', $equipmentReceiving) }}" class="btn btn-sm btn-primary">
            <i class="fas fa-plus"></i> Add Inspection
        </a>
        @endif
    </div>

    @if($equipmentReceiving->inspections->isEmpty())
    <div class="card-body text-center text-muted" style="padding:28px;">
        No inspection records yet.
        @if($pendingInspect > 0)
        <a href="{{ route('equipment-receiving.inspect', $equipmentReceiving) }}" style="display:block;margin-top:8px;" class="btn btn-sm btn-primary"><i class="fas fa-clipboard-check"></i> Start Inspection</a>
        @endif
    </div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Ref No.</th>
                    <th>Date</th>
                    <th>Qty Inspected</th>
                    <th>Status</th>
                    <th>Inspector</th>
                    <th>Condition</th>
                    <th>Functional Test</th>
                    <th>Inspector Signature</th>
                    <th>Remarks</th>
                </tr>
            </thead>
            <tbody>
            @foreach($equipmentReceiving->inspections as $ins)
            <tr>
                <td><strong>{{ $ins->inspection_ref_no }}</strong></td>
                <td class="text-sm">{{ $ins->inspection_date?->format('d M Y') ?? '—' }}</td>
                <td style="text-align:center;font-weight:700;color:#2563eb;">{{ $ins->qty_inspected }}</td>
                <td>
                    <span class="badge {{ $ins->inspection_status==='Accepted'?'badge-success':($ins->inspection_status==='Rejected'?'badge-danger':'badge-warning') }}">
                        {{ $ins->inspection_status }}
                    </span>
                </td>
                <td class="text-sm">{{ $ins->inspector ? $ins->inspector->firstname.' '.$ins->inspector->lastname : '—' }}</td>
                <td class="text-sm">{{ $ins->physical_condition ?? '—' }}</td>
                <td class="text-sm">{{ $ins->functional_test_result ?? '—' }}</td>
                <td>
                    @if($ins->inspector_signature)
                    <button type="button" class="btn btn-sm btn-outline" onclick="toggleBlock('sigs-ins-{{ $ins->id }}')">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <div id="sigs-ins-{{ $ins->id }}" style="display:none;margin-top:6px;">
                        <img src="{{ $ins->inspector_signature }}" style="max-height:70px;border:1px solid #e2e8f0;border-radius:4px;">
                    </div>
                    @else
                    <span class="text-muted text-sm">—</span>
                    @endif
                </td>
                <td class="text-sm">{{ $ins->remarks ?? '—' }}</td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-body text-sm text-muted" style="border-top:1px solid #f1f5f9;">
        Total inspected: <strong>{{ $totalInspected }}</strong> of <strong>{{ $qty }}</strong> &nbsp;|&nbsp;
        Pending: <strong style="color:#d97706;">{{ $pendingInspect }}</strong>
    </div>
    @endif
</div>

{{-- Redistribution History --}}
<div class="card">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;">
        <h3><i class="fas fa-share-square"></i> Redistribution History</h3>
        <a href="{{ route('equipment-receiving.redistribute', $equipmentReceiving) }}" class="btn btn-sm btn-success">
            <i class="fas fa-plus"></i> Add Redistribution
        </a>
    </div>

    @if($equipmentReceiving->redistributions->isEmpty())
    <div class="card-body text-center text-muted" style="padding:28px;">No redistributions recorded yet.</div>
    @else
    <div class="table-wrap">
        <table>
            <thead>
                <tr>
                    <th>Ref No.</th><th>Date</th><th>Type</th><th>Recipient</th><th>Job Title</th>
                    <th>Depot / Dept</th><th>Qty</th><th>Serial Numbers</th><th>Asset Tags</th><th>Issued By</th><th>Signatures</th>
                </tr>
            </thead>
            <tbody>
            @foreach($equipmentReceiving->redistributions as $r)
            <tr>
                <td><strong>{{ $r->redistribution_ref_no }}</strong></td>
                <td class="text-sm">{{ $r->redistribution_date?->format('d M Y') }}</td>
                <td><span class="badge {{ $r->redistribution_type==='Individual'?'badge-info':'badge-purple' }}">{{ $r->redistribution_type }}</span></td>
                <td>{{ $r->recipient_name }}</td>
                <td class="text-sm">{{ $r->recipient_job_title ?? '—' }}</td>
                <td class="text-sm">{{ $r->depot_department ?? '—' }}</td>
                <td style="text-align:center;font-weight:700;color:#7c3aed;">{{ $r->qty_redistributed }}</td>
                <td>
                    @if(!empty($r->serial_numbers))
                        @foreach($r->serial_numbers as $sn)
                        <div><code style="font-size:.78rem;background:#f1f5f9;padding:1px 5px;border-radius:3px;">{{ $sn }}</code></div>
                        @endforeach
                    @else
                        <span class="text-muted text-sm">—</span>
                    @endif
                </td>
                <td>
                    @if(!empty($r->asset_tags))
                        @foreach($r->serial_numbers ?? [] as $sn)
                        <div style="font-size:.78rem;">
                            @if(isset($r->asset_tags[$sn]) && $r->asset_tags[$sn])
                                <span style="background:#dbeafe;color:#1d4ed8;padding:1px 6px;border-radius:3px;font-weight:600;">{{ $r->asset_tags[$sn] }}</span>
                            @else
                                <span class="text-muted">—</span>
                            @endif
                        </div>
                        @endforeach
                    @else
                        <span class="text-muted text-sm">—</span>
                    @endif
                </td>
                <td class="text-sm">{{ $r->issued_by ?? '—' }}</td>
                <td>
                    @if($r->receiver_signature || $r->issuer_signature)
                    <button type="button" class="btn btn-sm btn-outline" onclick="toggleBlock('sigs-red-{{ $r->id }}')">
                        <i class="fas fa-eye"></i> View
                    </button>
                    <div id="sigs-red-{{ $r->id }}" style="display:none;margin-top:6px;">
                        @if($r->receiver_signature)
                        <div class="mb-1">
                            <div class="text-sm text-muted" style="font-weight:600;">Receiver:</div>
                            <img src="{{ $r->receiver_signature }}" style="max-height:60px;border:1px solid #e2e8f0;border-radius:4px;">
                        </div>
                        @endif
                        @if($r->issuer_signature)
                        <div>
                            <div class="text-sm text-muted" style="font-weight:600;">Issuer:</div>
                            <img src="{{ $r->issuer_signature }}" style="max-height:60px;border:1px solid #e2e8f0;border-radius:4px;">
                        </div>
                        @endif
                    </div>
                    @else
                    <span class="text-muted text-sm">—</span>
                    @endif
                </td>
            </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <div class="card-body text-sm text-muted" style="border-top:1px solid #f1f5f9;">
        Total deployed: <strong>{{ $deployed }}</strong> of <strong>{{ $qty }}</strong>
    </div>
    @endif
</div>

@push('scripts')
<script>
function toggleBlock(id) {
    const el = document.getElementById(id);
    el.style.display = el.style.display === 'none' ? 'block' : 'none';
}
</script>
@endpush
@endsection
