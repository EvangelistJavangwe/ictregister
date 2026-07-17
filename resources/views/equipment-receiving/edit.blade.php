@extends('layouts.app')
@section('title', 'Edit Delivery: '.$equipmentReceiving->cross_ref_no)

@section('content')
<div class="page-header">
    <h2><i class="fas fa-edit"></i> Edit Delivery: {{ $equipmentReceiving->cross_ref_no }}</h2>
    <a href="{{ route('equipment-receiving.show', $equipmentReceiving) }}" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back</a>
</div>

<form method="POST" action="{{ route('equipment-receiving.update', $equipmentReceiving) }}">
    @csrf @method('PUT')

    <div class="card mb-2">
        <div class="card-header"><h3><i class="fas fa-truck" style="color:#2563eb;margin-right:6px;"></i> Delivery Information</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Date Received *</label>
                    <input type="date" name="date_received" class="form-control" value="{{ $equipmentReceiving->date_received?->format('Y-m-d') }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Supplier / Source *</label>
                    <input type="text" name="supplier" class="form-control" value="{{ old('supplier', $equipmentReceiving->supplier) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Delivery Note No. *</label>
                    <input type="text" name="delivery_note_no" class="form-control @error('delivery_note_no') is-invalid @enderror" value="{{ old('delivery_note_no', $equipmentReceiving->delivery_note_no) }}" required>
                    @error('delivery_note_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">LPO / PO No. *</label>
                    <input type="text" name="po_no" class="form-control @error('po_no') is-invalid @enderror" value="{{ old('po_no', $equipmentReceiving->po_no) }}" required>
                    @error('po_no')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-2">
        <div class="card-header"><h3><i class="fas fa-desktop" style="color:#2563eb;margin-right:6px;"></i> Equipment Details</h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group" style="grid-column:span 2;">
                    <label class="form-label">Item Description *</label>
                    <input type="text" name="item_description" class="form-control" value="{{ old('item_description', $equipmentReceiving->item_description) }}" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Brand / Model *</label>
                    <input type="text" name="brand_model" class="form-control @error('brand_model') is-invalid @enderror" value="{{ old('brand_model', $equipmentReceiving->brand_model) }}" required>
                    @error('brand_model')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Serial Number</label>
                    <input type="text" name="serial_number" class="form-control" value="{{ old('serial_number', $equipmentReceiving->serial_number) }}">
                </div>
                <div class="form-group">
                    <label class="form-label">Quantity Received *</label>
                    <input type="number" name="qty_received" class="form-control" value="{{ old('qty_received', $equipmentReceiving->qty_received) }}" min="1" required>
                </div>
                <div class="form-group">
                    <label class="form-label">Accessories Included *</label>
                    <input type="text" name="accessories_included" class="form-control @error('accessories_included') is-invalid @enderror" value="{{ old('accessories_included', $equipmentReceiving->accessories_included) }}" required>
                    @error('accessories_included')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
            </div>
            <div class="form-group">
                <label class="form-label">Remarks *</label>
                <textarea name="remarks" class="form-control @error('remarks') is-invalid @enderror" required>{{ old('remarks', $equipmentReceiving->remarks) }}</textarea>
                @error('remarks')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>
    </div>

    {{-- Warranty --}}
    <div class="card mb-2">
        <div class="card-header"><h3><i class="fas fa-shield-alt" style="color:#16a34a;margin-right:6px;"></i> Warranty Information <span class="text-muted text-sm" style="font-weight:400;">(optional)</span></h3></div>
        <div class="card-body">
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Warranty Start Date</label>
                    <input type="date" name="warranty_start_date" class="form-control @error('warranty_start_date') is-invalid @enderror"
                        value="{{ old('warranty_start_date', $equipmentReceiving->warranty_start_date?->format('Y-m-d')) }}">
                    @error('warranty_start_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group">
                    <label class="form-label">Warranty Expiry Date</label>
                    <input type="date" name="warranty_end_date" class="form-control @error('warranty_end_date') is-invalid @enderror"
                        value="{{ old('warranty_end_date', $equipmentReceiving->warranty_end_date?->format('Y-m-d')) }}">
                    @error('warranty_end_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                </div>
                <div class="form-group" style="grid-column:span 2;">
                    <label class="form-label">Warranty Notes</label>
                    <input type="text" name="warranty_notes" class="form-control"
                        value="{{ old('warranty_notes', $equipmentReceiving->warranty_notes) }}"
                        placeholder="e.g. 3-year on-site support, contact number...">
                </div>
            </div>
        </div>
    </div>

    <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Changes</button>
        <a href="{{ route('equipment-receiving.show', $equipmentReceiving) }}" class="btn btn-secondary">Cancel</a>
    </div>
</form>
@endsection
