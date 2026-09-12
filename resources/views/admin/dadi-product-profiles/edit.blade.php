@extends('admin.layouts.app')

@section('title', 'Edit Dadi Product Profile')

@section('content')
@php
    $statusColors = [
        'draft' => 'secondary', 'pending_review' => 'warning', 'approved' => 'success', 'rejected' => 'danger',
    ];
    $productStatusColors = ['draft' => 'secondary', 'active' => 'success', 'inactive' => 'secondary'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Dadi Product Profile</h5>
        <small class="text-muted">
            Product: <strong>{{ $profile->product?->name ?: 'Product #'.$profile->product_id }}</strong>
            <span class="badge bg-{{ $productStatusColors[$profile->product?->status] ?? 'secondary' }}">{{ ucfirst($profile->product?->status ?: 'missing') }}</span>
        </small>
    </div>
    <a href="{{ route('admin.dadi.product-profiles.index') }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-arrow-left me-1"></i>Back</a>
</div>

<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3">
            <div class="col-md-3">
                <div class="text-muted small">SKU</div>
                <div class="fw-semibold">{{ $profile->product?->sku ?: '—' }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Selling Price (live)</div>
                <div class="fw-semibold">₹{{ number_format((float) $profile->product?->selling_price, 2) }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Stock (live)</div>
                <div class="fw-semibold">{{ $profile->product?->getAvailableStock() }} units</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted small">Approval Status</div>
                <span class="badge bg-{{ $statusColors[$profile->status->value] ?? 'secondary' }}">{{ $profile->status->label() }}</span>
            </div>
        </div>
        <hr>
        <div class="row g-3 text-muted small">
            <div class="col-md-4">
                Created by <strong>{{ $profile->creator?->name ?: '—' }}</strong> on {{ $profile->created_at?->format('d M Y H:i') }}
            </div>
            <div class="col-md-4">
                Reviewed by <strong>{{ $profile->reviewer?->name ?: '—' }}</strong>
                @if ($profile->reviewed_at) on {{ $profile->reviewed_at->format('d M Y H:i') }} @endif
            </div>
            <div class="col-md-4">
                Last modified: {{ $profile->updated_at?->format('d M Y H:i') }}
            </div>
        </div>
    </div>
</div>

@if ($errors->has('content'))
    <div class="alert alert-danger"><strong>Please fix the following:</strong> {{ $errors->first('content') }}</div>
@endif

<form method="POST" action="{{ route('admin.dadi.product-profiles.update', $profile->id) }}">
    @csrf
    @method('PUT')

    @include('admin.dadi-product-profiles._form', ['profile' => $profile])

    <button type="submit" class="btn btn-dark"><i class="bi bi-check-lg me-1"></i>Save Intelligence</button>
</form>

@if ($profile->status->value === 'draft' || $profile->status->value === 'rejected')
    <form method="POST" action="{{ route('admin.dadi.product-profiles.submit-review', $profile->id) }}" class="d-inline">
        @csrf
        <button type="submit" class="btn btn-outline-primary mt-3"><i class="bi bi-send me-1"></i>Submit for Review</button>
    </form>
@endif

@if ($profile->status->value === 'pending_review')
    <form method="POST" action="{{ route('admin.dadi.product-profiles.approve', $profile->id) }}" class="d-inline" onsubmit="return confirm('Approve this Dadi profile?')">
        @csrf
        <button type="submit" class="btn btn-success mt-3"><i class="bi bi-check-lg me-1"></i>Approve</button>
    </form>
    <form method="POST" action="{{ route('admin.dadi.product-profiles.reject', $profile->id) }}" class="d-inline" onsubmit="return confirm('Reject this Dadi profile?')">
        @csrf
        <button type="submit" class="btn btn-outline-danger mt-3"><i class="bi bi-x-lg me-1"></i>Reject</button>
    </form>
@endif

@if ($profile->status->value === 'approved')
    <p class="text-muted small mt-3">
        <i class="bi bi-info-circle me-1"></i>
        Approved intelligence is usable by Dadi, but current availability always follows the live product status and stock above.
    </p>
@endif
@endsection