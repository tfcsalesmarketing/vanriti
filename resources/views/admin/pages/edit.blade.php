@extends('admin.layouts.app')

@section('title', 'Edit Page')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Edit Page</h5>
</div>

<form method="POST" action="{{ route('admin.pages.update', $page) }}">
    @csrf
    @method('PUT')
    @include('admin.pages._form')
</form>
@endsection