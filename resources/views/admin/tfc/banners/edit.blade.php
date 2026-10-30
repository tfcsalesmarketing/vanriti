@extends('admin.layouts.app')

@section('title', 'Edit TFC Banner: ' . ($banner->title ?: 'Banner #' . $banner->id))

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold">Edit Banner: {{ $banner->title ?: '#' . $banner->id }}</h5>
</div>

<form method="POST" action="{{ route('admin.tfc.banners.update', $banner) }}">
    @csrf
    @method('PUT')
    @include('admin.tfc.banners._form')
</form>
@endsection
