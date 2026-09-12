@extends('admin.layouts.app')

@section('title', 'Dadi Product Profiles')

@section('content')
@php
    $statusColors = [
        'draft' => 'secondary', 'pending_review' => 'warning', 'approved' => 'success', 'rejected' => 'danger',
    ];
    $productStatusColors = ['draft' => 'secondary', 'active' => 'success', 'inactive' => 'secondary'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Dadi Product Profiles</h5>
        <small class="text-muted">{{ $profiles->total() }} profiles total</small>
    </div>
    <a href="{{ route('admin.dadi.product-profiles.create') }}" class="btn btn-sm btn-dark"><i class="bi bi-plus-lg me-1"></i>New Profile</a>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.dadi.product-profiles.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search product..." value="{{ request('q') }}">
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" {{ request('status') === $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-dark"><i class="bi bi-search me-1"></i>Filter</button>
            </div>
            <div class="col-auto">
                <a href="{{ route('admin.dadi.product-profiles.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Product</th>
                    <th>Section</th>
                    <th>Concerns</th>
                    <th>Approval</th>
                    <th>Reviewed By</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($profiles as $profile)
                    <tr>
                        <td>
                            <div class="small fw-semibold">{{ $profile->product?->name ?: 'Product #'.$profile->product_id }}</div>
                            <div class="text-muted" style="font-size:0.75rem;">{{ $profile->product?->sku ?: '—' }}</div>
                            <span class="badge bg-{{ $productStatusColors[$profile->product?->status] ?? 'secondary' }}" style="font-size:0.65rem;">{{ ucfirst($profile->product?->status ?: 'missing') }}</span>
                        </td>
                        <td>
                            @foreach ($profile->sections ?? [] as $section)
                                <span class="badge bg-light text-dark me-1">{{ ucfirst($section) }}</span>
                            @endforeach
                        </td>
                        <td>
                            @foreach (array_slice($profile->concerns ?? [], 0, 3) as $concern)
                                <span class="badge bg-light text-dark me-1" style="font-size:0.7rem;">{{ $concern }}</span>
                            @endforeach
                            @if (count($profile->concerns ?? []) > 3)
                                <span class="text-muted small">+{{ count($profile->concerns) - 3 }}</span>
                            @endif
                        </td>
                        <td>
                            <span class="badge bg-{{ $statusColors[$profile->status->value] ?? 'secondary' }}">{{ $profile->status->label() }}</span>
                            @if ($profile->reviewed_at)
                                <div class="text-muted" style="font-size:0.7rem;">{{ $profile->reviewed_at->format('d M Y') }}</div>
                            @endif
                        </td>
                        <td class="small">{{ $profile->reviewer?->name ?: '—' }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.dadi.product-profiles.edit', $profile->id) }}" class="btn btn-sm btn-outline-dark">Open</a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No Dadi product profiles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $profiles->links() }}
    </div>
</div>
@endsection