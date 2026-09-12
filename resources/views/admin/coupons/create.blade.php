@extends('admin.layouts.app')

@section('title', 'Add Coupon')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Add Coupon</h5>
</div>

<form method="POST" action="{{ route('admin.coupons.store') }}">
    @csrf
    @include('admin.coupons._form')
</form>
@endsection
