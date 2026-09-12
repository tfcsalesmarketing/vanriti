@extends('admin.layouts.app')

@section('title', 'Refunds')

@section('content')
@php
    $classes = ['completed'=>'success','approved'=>'primary','processing'=>'warning','requested'=>'secondary','under_review'=>'info','rejected'=>'danger'];
@endphp

<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Refunds</h5>
        <small class="text-muted">{{ $refunds->total() }} refunds total</small>
    </div>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.refunds.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search refund # or order #..." value="{{ request('q') }}">
            </div>
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
                <a href="{{ route('admin.refunds.index') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Refund #</th>
                    <th>Order #</th>
                    <th>Customer</th>
                    <th class="text-end">Amount</th>
                    <th>Type</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Requested</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($refunds as $refund)
                    <tr>
                        <td class="small text-primary fw-semibold">{{ $refund->refund_number }}</td>
                        <td class="small">{{ $refund->order?->order_number ?: '—' }}</td>
                        <td>
                            <div class="fw-semibold small">{{ $refund->user?->name ?: '—' }}</div>
                            <div class="text-muted small">{{ $refund->user?->email }}</div>
                        </td>
                        <td class="text-end fw-semibold small">{{ format_price($refund->amount) }}</td>
                        <td class="small text-capitalize">{{ $refund->type }}</td>
                        <td class="small">{{ $refund->method ?: '—' }}</td>
                        <td>
                            <span class="badge bg-{{ $classes[$refund->status] ?? 'secondary' }}">{{ ucwords(str_replace('_', ' ', $refund->status)) }}</span>
                        </td>
                        <td class="small">{{ $refund->requested_at?->format('d M Y') }}</td>
                        <td class="text-end">
                            @if (in_array($refund->status, ['requested', 'under_review']))
                                <div class="d-flex gap-1 justify-content-end">
                                    <form method="POST" action="{{ route('admin.refunds.approve', $refund) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="admin_note" value="">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Approve"><i class="bi bi-check-lg"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.refunds.reject', $refund) }}" class="d-inline">
                                        @csrf
                                        <input type="hidden" name="admin_note" value="">
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Reject"><i class="bi bi-x-lg"></i></button>
                                    </form>
                                </div>
                            @elseif ($refund->status === 'approved')
                                <div class="d-flex gap-1 justify-content-end">
                                    <form method="POST" action="{{ route('admin.refunds.process', $refund) }}" class="d-inline">
                                        @csrf
                                        <button type="submit" class="btn btn-sm btn-outline-primary" title="Process"><i class="bi bi-play-fill"></i></button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.refunds.complete', $refund) }}" class="d-inline">
                                        @csrf
                                        <input type="text" name="gateway_reference" class="form-control form-control-sm d-inline-block" style="width:110px;" placeholder="Ref #" title="Gateway reference">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Complete"><i class="bi bi-check2-circle"></i></button>
                                    </form>
                                </div>
                            @elseif ($refund->status === 'processing')
                                <form method="POST" action="{{ route('admin.refunds.complete', $refund) }}" class="d-inline">
                                    @csrf
                                    <div class="d-flex gap-1 justify-content-end">
                                        <input type="text" name="gateway_reference" class="form-control form-control-sm" style="width:120px;" placeholder="Gateway ref" title="Gateway reference">
                                        <button type="submit" class="btn btn-sm btn-outline-success" title="Complete"><i class="bi bi-check2-circle"></i>Complete</button>
                                    </div>
                                </form>
                            @else
                                <span class="text-muted small">—</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">No refunds found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $refunds->links() }}
    </div>
</div>
@endsection
