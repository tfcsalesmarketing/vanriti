@extends('storefront.layouts.app')

@section('title', 'Add New Address')
@section('robots', 'noindex, nofollow')

@section('content')
<div class="container py-4">
    <div class="row g-4">
        <div class="col-lg-3">
            @include('storefront.account.partials.nav')
        </div>
        <div class="col-lg-9">
            <h5 class="fw-bold mb-3">Add New Address</h5>
            @include('storefront.account.partials.address-form', [
                'route' => route('account.addresses.store'),
            ])
        </div>
    </div>
</div>
@endsection