@extends('admin.layouts.app')

@section('title', 'Add Page')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Add Page</h5>
</div>

<form method="POST" action="{{ route('admin.pages.store') }}">
    @csrf
    @include('admin.pages._form')
</form>
@endsection