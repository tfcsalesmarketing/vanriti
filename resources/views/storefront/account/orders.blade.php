@extends('storefront.layouts.app')

@section('title', 'My Orders')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-3">
            @include('storefront.account.partials.nav')
        </div>
        <div class="col-lg-9">
            <h5 class="fw-bold mb-3">My Orders</h5>
            @if ($orders->isEmpty())
                <div class="vr-empty bg-white border rounded-4">
                    <i class="bi bi-bag"></i>
                    <p class="mt-2">You haven't placed any orders yet.</p>
                    <a href="{{ route('shop.index') }}" class="btn btn-vr btn-sm">Shop Now</a>
                </div>
            @else
                @include('storefront.account.partials.order-table', ['orders' => $orders])
                <div class="mt-3">
                    {{ $orders->links('storefront.partials.pagination') }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection