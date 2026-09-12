@extends('admin.layouts.app')

@section('title', 'Coupons')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Coupons</h5>
        <small class="text-muted">{{ $coupons->total() }} coupons total</small>
    </div>
    <a href="{{ route('admin.coupons.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Coupon</a>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Code</th>
                    <th class="text-end">Discount</th>
                    <th class="text-end">Min Cart</th>
                    <th>Validity</th>
                    <th class="text-center">Usage</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($coupons as $coupon)
                    @php
                        $active = $coupon->is_active
                            && (!$coupon->expires_at || $coupon->expires_at->isFuture())
                            && (!$coupon->starts_at || $coupon->starts_at->isPast());
                        $expired = !$coupon->is_active ? null : (!$coupon->expires_at || $coupon->expires_at->isFuture());
                    @endphp
                    <tr>
                        <td>
                            <div class="fw-semibold small">{{ $coupon->code }}</div>
                            @if ($coupon->description)
                                <div class="text-muted" style="font-size:0.75rem;">{{ Str::limit($coupon->description, 40) }}</div>
                            @endif
                        </td>
                        <td class="text-end">
                            <span class="fw-semibold small">{{ $coupon->discount_type === 'percentage' ? $coupon->discount_value . '%' : format_price($coupon->discount_value) }}</span>
                            @if ($coupon->max_discount)
                                <div class="text-muted" style="font-size:0.75rem;">max {{ format_price($coupon->max_discount) }}</div>
                            @endif
                        </td>
                        <td class="text-end small">{{ $coupon->min_cart_value > 0 ? format_price($coupon->min_cart_value) : '—' }}</td>
                        <td class="small text-muted">
                            @if ($coupon->starts_at)
                                {{ $coupon->starts_at->format('d M Y') }}
                            @else
                                Anytime
                            @endif
                            @if ($coupon->expires_at)
                                <br>to {{ $coupon->expires_at->format('d M Y') }}
                            @endif
                        </td>
                        <td class="text-center">
                            {{ $coupon->usages_count }}
                            @if ($coupon->usage_limit)
                                <span class="text-muted" style="font-size:0.75rem;">/ {{ $coupon->usage_limit }}</span>
                            @endif
                        </td>
                        <td>
                            @if (!$coupon->is_active)
                                <span class="badge bg-secondary">Inactive</span>
                            @elseif ($coupon->expires_at && $coupon->expires_at->isPast())
                                <span class="badge bg-danger">Expired</span>
                            @elseif ($coupon->starts_at && $coupon->starts_at->isFuture())
                                <span class="badge bg-info">Scheduled</span>
                            @else
                                <span class="badge bg-success">Active</span>
                            @endif
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.coupons.edit', $coupon->id) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('admin.coupons.destroy', $coupon->id) }}" onsubmit="return confirm('Delete this coupon?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No coupons found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $coupons->links() }}
    </div>
</div>
@endsection
