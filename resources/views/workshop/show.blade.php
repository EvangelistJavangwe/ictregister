@extends('layouts.app')
@section('title', 'Job: '.$workshop->entry_job_number)

@section('content')
<div class="page-header">
    <div>
        <h2><i class="fas fa-tools"></i> {{ $workshop->entry_job_number }}</h2>
        <div class="d-flex gap-2 mt-1">
            <span class="badge badge-{{ $workshop->priority_level==='Urgent'?'danger':($workshop->priority_level==='High'?'warning':'info') }}">{{ $workshop->priority_level }}</span>
            @if($workshop->status === 'Collected')
                <span class="badge" style="background:#ede9fe;color:#6d28d9;border:1px solid #ddd6fe;font-weight:700;"><i class="fas fa-box" style="font-size:.65rem;margin-right:3px;"></i>Collected</span>
            @elseif($workshop->status === 'Completed')
                <span class="badge" style="background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;font-weight:700;"><i class="fas fa-check-circle" style="font-size:.65rem;margin-right:3px;"></i>Completed</span>
            @elseif($workshop->status === 'In Progress')
                <span class="badge badge-info"><i class="fas fa-spinner" style="font-size:.65rem;margin-right:3px;"></i>In Progress</span>
            @else
                <span class="badge badge-warning"><i class="fas fa-clock" style="font-size:.65rem;margin-right:3px;"></i>Pending</span>
            @endif
            @if($workshop->isOverdue())<span class="badge badge-danger"><i class="fas fa-exclamation-triangle"></i> OVERDUE</span>@endif
        </div>
    </div>
    <div class="d-flex gap-2">
        @if(auth()->user()->isAdminOrHod())
        <form method="POST" action="{{ route('workshop.assign-self', $workshop) }}">
            @csrf
            <button type="submit" class="btn btn-secondary"><i class="fas fa-user-check"></i> Assign to Me</button>
        </form>
        @endif
        <a href="{{ route('workshop.edit', $workshop) }}" class="btn btn-primary"><i class="fas fa-edit"></i> Update</a>
        <a href="{{ route('workshop.index') }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
    </div>
</div>

<div class="grid-2">
    <div class="card">
        <div class="card-header"><h3>Equipment Information</h3></div>
        <div class="detail-grid">
            <div class="detail-item"><div class="detail-label">Job Number</div><div class="detail-value">{{ $workshop->entry_job_number }}</div></div>
            <div class="detail-item"><div class="detail-label">Date/Time Received</div><div class="detail-value">{{ $workshop->date_time_received?->format('d M Y H:i') }}</div></div>
            <div class="detail-item"><div class="detail-label">Equipment Type</div><div class="detail-value">{{ $workshop->equipment_type }}</div></div>
            <div class="detail-item"><div class="detail-label">Brand/Make/Model</div><div class="detail-value">{{ $workshop->brand_make_model ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Serial / Asset Tag</div><div class="detail-value">{{ $workshop->serial_number_asset_tag ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Physical Condition</div><div class="detail-value">{{ $workshop->physical_condition_on_receipt ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Accessories Received</div><div class="detail-value">{{ $workshop->accessories_received ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Cross Ref / Form 208</div><div class="detail-value">{{ $workshop->cross_ref_form208 ?? '—' }}</div></div>
        </div>
    </div>
    <div class="card">
        <div class="card-header"><h3>Sender / Contact</h3></div>
        <div class="detail-grid">
            <div class="detail-item"><div class="detail-label">Depot Name</div><div class="detail-value">{{ $workshop->depot_name ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Department</div><div class="detail-value">{{ $workshop->department ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Extension Number</div><div class="detail-value">{{ $workshop->contact_person ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Phone Number</div><div class="detail-value">{{ $workshop->phone_number ?? '—' }}</div></div>
        </div>
    </div>
</div>

<div class="card mt-2">
    <div class="card-header"><h3><i class="fas fa-laptop"></i> Devices on this Job ({{ $workshop->devices->count() }})</h3></div>
    <div class="table-wrap">
        <table>
            <thead>
                <tr><th>#</th><th>Equipment Type</th><th>Brand/Make/Model</th><th>Serial / Asset Tag</th><th>Physical Condition</th><th>Status</th><th>Collected</th></tr>
            </thead>
            <tbody>
            @foreach($workshop->devices as $i => $device)
                <tr>
                    <td>{{ $i + 1 }}{{ $i === 0 ? ' (Primary)' : '' }}</td>
                    <td>{{ $device->equipment_type }}</td>
                    <td>{{ $device->brand_make_model ?? '—' }}</td>
                    <td>{{ $device->serial_number_asset_tag ?? '—' }}</td>
                    <td>{{ $device->physical_condition_on_receipt ?? '—' }}</td>
                    <td>
                        @if($device->status === 'Collected')
                            <span class="badge" style="background:#ede9fe;color:#6d28d9;border:1px solid #ddd6fe;font-weight:700;">Collected</span>
                        @elseif($device->status === 'Completed')
                            <span class="badge" style="background:#d1fae5;color:#065f46;border:1px solid #a7f3d0;font-weight:700;">Completed</span>
                        @elseif($device->status === 'In Progress')
                            <span class="badge badge-info">In Progress</span>
                        @else
                            <span class="badge badge-warning">Pending</span>
                        @endif
                    </td>
                    <td class="text-sm">
                        @if($device->status === 'Collected')
                            {{ $device->collector_name ?? '—' }}{{ $device->date_collected ? ' ('.$device->date_collected->format('d M Y').')' : '' }}
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="card mt-2">
    <div class="card-header"><h3>Nature of Fault</h3></div>
    <div class="card-body"><p>{{ $workshop->nature_of_fault }}</p></div>
</div>

<div class="grid-2 mt-2">
    <div class="card">
        <div class="card-header"><h3>Repair & Technician</h3></div>
        <div class="detail-grid">
            <div class="detail-item"><div class="detail-label">Technician Assigned</div><div class="detail-value">{{ $workshop->technician ? $workshop->technician->firstname.' '.$workshop->technician->lastname : '— Unassigned —' }}</div></div>
            <div class="detail-item"><div class="detail-label">Priority Level</div><div class="detail-value">{{ $workshop->priority_level }}</div></div>
            <div class="detail-item"><div class="detail-label">Due Date</div><div class="detail-value {{ $workshop->isOverdue() ? 'text-danger fw-bold' : '' }}">{{ $workshop->due_date?->format('d M Y H:i') ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Time Taken</div><div class="detail-value">{{ $workshop->time_taken_value ? $workshop->time_taken_value.' '.$workshop->time_taken_unit : '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Date Repair Completed</div><div class="detail-value">{{ $workshop->date_repair_completed?->format('d M Y') ?? '—' }}</div></div>
        </div>
        @if($workshop->repair_action_taken)
        <div class="card-body" style="border-top:1px solid #e2e8f0;">
            <strong class="text-sm">Repair Action Taken:</strong>
            <p class="mt-1">{{ $workshop->repair_action_taken }}</p>
        </div>
        @endif
    </div>
    <div class="card">
        <div class="card-header"><h3>Collection</h3></div>
        <div class="detail-grid">
            <div class="detail-item"><div class="detail-label">Date Collected</div><div class="detail-value">{{ $workshop->date_collected?->format('d M Y') ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Collector Name</div><div class="detail-value">{{ $workshop->collector_name ?? '—' }}</div></div>
            <div class="detail-item"><div class="detail-label">Outgoing Cross Ref</div><div class="detail-value">{{ $workshop->cross_ref_form208_outgoing ?? '—' }}</div></div>
        </div>
        @if($workshop->remarks_comments)
        <div class="card-body" style="border-top:1px solid #e2e8f0;">
            <strong class="text-sm">Remarks:</strong>
            <p class="mt-1">{{ $workshop->remarks_comments }}</p>
        </div>
        @endif
    </div>
</div>

<!-- Comments Section -->
<div class="card mt-2">
    <div class="card-header"><h3><i class="fas fa-comments"></i> Comments {{ $workshop->comments->count() ? '('.$workshop->comments->count().')' : '' }}</h3></div>
    <div class="card-body">
        @forelse($workshop->comments as $comment)
        <div style="padding:12px;border-radius:8px;background:#f8fafc;margin-bottom:10px;border:1px solid #e2e8f0;">
            <div class="d-flex justify-between align-center">
                <strong class="text-sm">{{ $comment->commenter->firstname ?? 'Unknown' }} {{ $comment->commenter->lastname ?? '' }}</strong>
                <span class="text-sm text-muted">{{ $comment->created_at->diffForHumans() }}</span>
            </div>
            @if($comment->is_overdue_comment)<span class="badge badge-warning text-sm">Overdue Comment</span>@endif
            <p style="margin-top:6px;font-size:.875rem;">{{ $comment->comment }}</p>
        </div>
        @empty
        <p class="text-muted text-sm">No comments yet.</p>
        @endforelse

        @if(auth()->user()->isAdminOrHod())
        <hr class="divider">
        <form method="POST" action="{{ route('workshop.comment', $workshop) }}">
            @csrf
            <div class="form-group">
                <label class="form-label">Add Comment</label>
                <textarea name="comment" class="form-control" rows="3" placeholder="Enter your comment..." required></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Post Comment</button>
        </form>
        @endif
    </div>
</div>
@endsection
