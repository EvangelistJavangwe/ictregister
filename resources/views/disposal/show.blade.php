@extends('layouts.app')
@section('title', 'Disposal: '.($disposal->disposal_ref_no ?? 'Request'))

@section('content')
<div class="page-header">
    <div>
        <h2><i class="fas fa-trash-alt"></i> {{ $disposal->disposal_ref_no ?? 'Disposal Request' }}</h2>
        <span class="badge badge-{{ $disposal->status==='Disposed'?'danger':($disposal->status==='Approved'?'success':($disposal->status==='Rejected'?'secondary':'warning')) }}">
            {{ $disposal->status }}
        </span>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->isAdminOrHod() && in_array($disposal->status, ['Requested','Pending Approval']))
        <a href="{{ route('disposal.edit', $disposal) }}" class="btn btn-secondary"><i class="fas fa-edit"></i> Edit</a>
        @endif
        <a href="{{ route('disposal.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>Asset Details</h3></div>
        <div class="detail-grid">
            <div class="detail-item"><div class="detail-label">Disposal Ref No.</div><div class="detail-value">{{ $disposal->disposal_ref_no ?? 'Not yet assigned' }}</div></div>
            <div class="detail-item"><div class="detail-label">Asset Description</div><div class="detail-value">{{ $disposal->asset_description }}</div></div>
            <div class="detail-item"><div class="detail-label">Asset Tag / Serial No.</div><div class="detail-value">{{ $disposal->asset_tag_serial_no ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Model / Brand</div><div class="detail-value">{{ $disposal->model_brand ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Department / User</div><div class="detail-value">{{ $disposal->department_user ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Date Acquired</div><div class="detail-value">{{ $disposal->date_acquired?->format('d M Y') ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Condition at Disposal</div><div class="detail-value">{{ $disposal->condition_at_disposal ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Data Wiped/Destroyed</div><div class="detail-value">{{ $disposal->data_wiped_destroyed ? 'Yes' : 'No' }}</div></div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Disposal Details</h3></div>
        <div class="detail-grid">
            <div class="detail-item"><div class="detail-label">Status</div><div class="detail-value">{{ $disposal->status }}</div></div>
            <div class="detail-item"><div class="detail-label">Disposal Method</div><div class="detail-value">{{ $disposal->disposal_method ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Date Disposed</div><div class="detail-value">{{ $disposal->date_disposed?->format('d M Y') ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Disposal Value</div><div class="detail-value">{{ $disposal->disposal_value ? '$'.number_format($disposal->disposal_value, 2) : '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Requested By</div><div class="detail-value">{{ $disposal->requester?->firstname.' '.$disposal->requester?->lastname ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Approved By</div><div class="detail-value">{{ $disposal->approver?->firstname.' '.$disposal->approver?->lastname ?? '—' }}</div></div>
        </div>
        @if($disposal->review_note)
        <div class="card-body" style="border-top:1px solid #e2e8f0;">
            <strong class="text-sm">Review Note:</strong>
            <p class="mt-1">{{ $disposal->review_note }}</p>
        </div>
        @endif
        @if($disposal->approved_signature)
        <div class="card-body" style="border-top:1px solid #e2e8f0;">
            <strong class="text-sm">Approval Signature:</strong><br>
            <img src="{{ $disposal->approved_signature }}" style="max-height:80px;border:1px solid #e2e8f0;border-radius:6px;margin-top:6px;">
        </div>
        @endif
    </div>
</div>

<div class="card mt-2">
    <div class="card-body">
        <strong class="text-sm">Reason for Disposal:</strong>
        <p class="mt-1">{{ $disposal->reason_for_disposal }}</p>
        @if($disposal->remarks)<br><strong class="text-sm">Remarks:</strong><p class="mt-1">{{ $disposal->remarks }}</p>@endif
    </div>
</div>

@if(auth()->user()->isAdminOrHod())
<!-- Actions -->
@if(in_array($disposal->status, ['Requested','Pending Approval']))
<div class="card mt-2">
    <div class="card-header"><h3>Actions</h3></div>
    <div class="card-body">
        <div class="grid-2">
            <div>
                <h4 style="font-size:.9rem;font-weight:600;color:#16a34a;margin-bottom:10px;"><i class="fas fa-check-circle"></i> Approve</h4>
                <form method="POST" action="{{ route('disposal.approve', $disposal) }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Approval Note (optional)</label>
                        <textarea name="review_note" class="form-control" rows="2"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Signature</label>
                        <div class="sig-pad-wrap">
                            <canvas id="approve-sig" width="400" height="100"></canvas>
                        </div>
                        <input type="hidden" name="approved_signature" id="approve-sig-data">
                        <button type="button" onclick="clearApproveSig()" class="btn btn-sm btn-secondary mt-1"><i class="fas fa-eraser"></i> Clear</button>
                    </div>
                    <button type="submit" class="btn btn-success" data-confirm="Approve this disposal request?"><i class="fas fa-check"></i> Approve</button>
                </form>
            </div>
            <div>
                <h4 style="font-size:.9rem;font-weight:600;color:#dc2626;margin-bottom:10px;"><i class="fas fa-times-circle"></i> Reject</h4>
                <form method="POST" action="{{ route('disposal.reject', $disposal) }}">
                    @csrf
                    <div class="form-group">
                        <label class="form-label">Reason for Rejection *</label>
                        <textarea name="review_note" class="form-control" rows="3" required></textarea>
                    </div>
                    <button type="submit" class="btn btn-danger" data-confirm="Reject this disposal request?"><i class="fas fa-times"></i> Reject</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endif

@if($disposal->status === 'Approved')
<div class="card mt-2">
    <div class="card-header"><h3>Mark as Disposed</h3></div>
    <div class="card-body">
        <form method="POST" action="{{ route('disposal.mark-disposed', $disposal) }}" class="d-flex gap-3 flex-wrap align-center">
            @csrf
            <div class="form-group" style="margin:0;">
                <label class="form-label">Date Disposed *</label>
                <input type="date" name="date_disposed" class="form-control" value="{{ today()->format('Y-m-d') }}" required>
            </div>
            <div class="form-group" style="margin:0;">
                <label class="form-label">Disposal Method *</label>
                <select name="disposal_method" class="form-control" required>
                    @foreach(['Auction','Donation','Destruction','Write-Off','Trade-In','Recycling'] as $m)
                    <option value="{{ $m }}" {{ $disposal->disposal_method === $m ? 'selected' : '' }}>{{ $m }}</option>
                    @endforeach
                </select>
            </div>
            <div style="align-self:flex-end;">
                <button type="submit" class="btn btn-danger" data-confirm="Confirm disposal?"><i class="fas fa-trash-alt"></i> Mark Disposed</button>
            </div>
        </form>
    </div>
</div>
@endif
@endif

@push('scripts')
<script>
const aSig = document.getElementById('approve-sig');
if (aSig) {
    const ctx = aSig.getContext('2d');
    let drawing = false, lx = 0, ly = 0;
    function gp(e) { const r = aSig.getBoundingClientRect(), t = e.touches?e.touches[0]:e; return [t.clientX-r.left,t.clientY-r.top]; }
    aSig.addEventListener('mousedown', e=>{drawing=true;[lx,ly]=gp(e);});
    aSig.addEventListener('mousemove', e=>{if(!drawing)return;const[x,y]=gp(e);ctx.beginPath();ctx.moveTo(lx,ly);ctx.lineTo(x,y);ctx.strokeStyle='#1e293b';ctx.lineWidth=2;ctx.lineCap='round';ctx.stroke();[lx,ly]=[x,y];document.getElementById('approve-sig-data').value=aSig.toDataURL();});
    aSig.addEventListener('mouseup',()=>drawing=false);
    function clearApproveSig(){ctx.clearRect(0,0,aSig.width,aSig.height);document.getElementById('approve-sig-data').value='';}
}
</script>
@endpush
@endsection
