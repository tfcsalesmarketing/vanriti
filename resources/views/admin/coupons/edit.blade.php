@extends('admin.layouts.app')

@section('title', 'Edit Coupon: ' . $coupon->code)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Edit Coupon: {{ $coupon->code }}</h5>
</div>

<form method="POST" action="{{ route('admin.coupons.update', $coupon->id) }}">
    @csrf
    @method('PUT')
    @include('admin.coupons._form')
</form>
@endsection
