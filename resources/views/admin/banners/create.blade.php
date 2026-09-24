@extends('admin.layouts.app')

@section('title', 'Add Banner')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Add Banner</h5>
</div>

<form method="POST" action="{{ route('admin.banners.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.banners._form')
</form>
@endsection