@extends('admin.layouts.app')

@section('title', 'Edit Main Category')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Edit Main Category</h5>
</div>

<form method="POST" action="{{ route('admin.main-categories.update', $category->slug) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.main-categories._form')
</form>
@endsection
