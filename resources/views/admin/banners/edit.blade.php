@extends('admin.layouts.app')

@section('title', 'Edit Banner')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Edit Banner</h5>
</div>

<form method="POST" action="{{ route('admin.banners.update', $banner) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.banners._form')
</form>
@endsection