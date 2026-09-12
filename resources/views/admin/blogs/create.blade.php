@extends('admin.layouts.app')

@section('title', 'Add Blog')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Add Blog</h5>
</div>

<form method="POST" action="{{ route('admin.blogs.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.blogs._form')
</form>
@endsection