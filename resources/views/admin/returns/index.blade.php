@extends('admin.layouts.app')

@section('title', 'Returns')

@section('content')
@php
    $classes = ['returned'=>'success','approved'=>'primary','pickup_scheduled'=>'info','picked_up'=>'info','requested'=>'secondary','under_review'=>'warning','rejected'=>'danger'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Returns</h5>
        <small class="text-muted">{{ $returns->total() }} returns total</small>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.returns.index') }}" class="row g-2 align-items-end">
            <div class="col-md-3">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Status</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status }}" {{ request('status') === $status ? 'selected' : '' }}>{{ ucwords(str_replace('_', ' ', $status)) }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-dark w-100"><i class="bi bi-search"></i></button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.returns.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Return #</th>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th>Reason</th>
                    <th class="text-center">Items</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-end">Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($returns as $return)
                    <tr>
                        <td class="small text-primary fw-semibold">{{ $return->return_number }}</td>
                        <td class="small">{{ $return->order?->order_number ?: '—' }}</td>
                        <td>
                            <div class="fw-semibold small">{{ $return->user?->name ?: '—' }}</div>
                            <div class="text-muted small">{{ $return->user?->email }}</div>
                        </td>
                        <td class="small">{{ $return->reason ?: '—' }}</td>
                        <td class="text-center small">{{ $return->items_count }}</td>
                        <td>
                            <span class="badge bg-{{ $classes[$return->status] ?? 'secondary' }}">{{ ucwords(str_replace('_', ' ', $return->status)) }}</span>
                        </td>
                        <td class="small">{{ $return->requested_at?->format('d M Y') }}</td>
                        <td class="text-end">
                            <a href="{{ route('admin.returns.show', $return) }}" class="btn btn-sm btn-outline-info" title="View"><i class="bi bi-eye"></i></a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">No returns found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $returns->links() }}
    </div>
</div>
@endsection
