@extends('admin.layouts.app')

@section('title', 'Edit Blog')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Edit Blog</h5>
</div>

<form method="POST" action="{{ route('admin.blogs.update', $blog) }}" enctype="multipart/form-data">
    @csrf
    @method('PUT')
    @include('admin.blogs._form')
</form>
@endsection