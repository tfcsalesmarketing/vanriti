@extends('admin.layouts.app')

@section('title', 'Add Product')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Add Product</h5>
</div>

<form method="POST" action="{{ route('admin.products.store') }}">
    @csrf
    @include('admin.products._form')
</form>
@endsection
