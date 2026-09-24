@extends('storefront.layouts.app')

@section('title', 'My Account')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-3">
            @include('storefront.account.partials.nav')
        </div>
        <div class="col-lg-9">
            <div class="card border-0 shadow-sm mb-4" style="border-radius: 14px;">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <span class="vr-icon-link" style="width:54px;height:54px;font-size:1.4rem;border:0;background:var(--vr-green-soft);">
                            <i class="bi bi-person-fill"></i>
                        </span>
                        <div>
                            <h5 class="mb-0 fw-bold">Hello, {{ auth('web')->user()->name }}</h5>
                            <p class="text-muted small mb-0">{{ auth('web')->user()->email }}</p>
                        </div>
                    </div>
                    <div class="row g-3 mt-3 vr-stagger">
                        <div class="col-6 col-md-3">
                            <div class="rounded-3 p-3 text-center" style="background:#fff;box-shadow:var(--vr-shadow-xs);">
                                <div class="fw-bold fs-4" style="color:var(--vr-green-dark);">{{ $stats['orders'] }}</div>
                                <div class="small text-muted">Orders</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rounded-3 p-3 text-center" style="background:#fff;box-shadow:var(--vr-shadow-xs);">
                                <div class="fw-bold fs-4" style="color:var(--vr-green-dark);">{{ format_price($stats['spent']) }}</div>
                                <div class="small text-muted">Lifetime Spend</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rounded-3 p-3 text-center" style="background:#fff;box-shadow:var(--vr-shadow-xs);">
                                <div class="fw-bold fs-4" style="color:var(--vr-green-dark);">{{ $stats['delivered'] }}</div>
                                <div class="small text-muted">Delivered</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="rounded-3 p-3 text-center" style="background:#fff;box-shadow:var(--vr-shadow-xs);">
                                <div class="fw-bold fs-4" style="color:var(--vr-green-dark);">{{ $stats['pending_returns'] }}</div>
                                <div class="small text-muted">Returns</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Recent Orders</h6>
                <a href="{{ route('account.orders') }}" class="small vr-link-underline">View all</a>
            </div>

            @if ($recentOrders->isEmpty())
                <div class="vr-empty bg-white border rounded-4">
                    <i class="bi bi-bag"></i>
                    <p class="mt-2">No orders yet.</p>
                    <a href="{{ route('shop.index') }}" class="btn btn-vr btn-sm">Start Shopping</a>
                </div>
            @else
                @include('storefront.account.partials.order-table', ['orders' => $recentOrders])
            @endif
        </div>
    </div>
</div>
@endsection