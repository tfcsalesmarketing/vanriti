@extends('admin.layouts.app')

@section('title', 'Edit TFC Article: ' . $blog->title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-bold">Edit Article: {{ $blog->title }}</h5>
        <div class="text-muted small">Route: <code>/blogs/{{ $blog->slug }}</code></div>
    </div>
</div>

<form method="POST" action="{{ route('admin.tfc.blogs.update', $blog) }}">
    @csrf
    @method('PUT')
    @include('admin.tfc.blogs._form')
</form>
@endsection
