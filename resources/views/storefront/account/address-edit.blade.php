@extends('storefront.layouts.app')

@section('title', 'Edit Address')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="container py-4" style="max-width: 680px;">
    <a href="{{ route('account.addresses') }}" class="small vr-link-underline mb-3 d-inline-block"><i class="bi bi-arrow-left me-1"></i>Back to addresses</a>
    <div class="card border-0 shadow-sm" style="border-radius: 16px;">
        <div class="card-body p-4">
            <h5 class="fw-bold mb-4">Edit Address</h5>
            @include('storefront.account.partials.address-form', ['address' => $address, 'route' => route('account.addresses.update', $address), 'method' => 'PUT'])
        </div>
    </div>
</div>
@endsection