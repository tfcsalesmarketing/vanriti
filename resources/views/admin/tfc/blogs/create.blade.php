@extends('admin.layouts.app')

@section('title', 'Write New TFC Article')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold">Write New TFC Article</h5>
</div>

<form method="POST" action="{{ route('admin.tfc.blogs.store') }}">
    @csrf
    @include('admin.tfc.blogs._form')
</form>
@endsection
