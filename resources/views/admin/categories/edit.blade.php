@extends('admin.layouts.app')

@section('title', 'Edit Category')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Edit Category</h5>
</div>

<form method="POST" action="{{ route('admin.categories.update', $category->slug) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.categories._form', ['excludeIds' => $excludeIds])
</form>
@endsection
