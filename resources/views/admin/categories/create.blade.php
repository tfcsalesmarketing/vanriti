@extends('admin.layouts.app')

@section('title', 'Add Category')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Add Category</h5>
</div>

<form method="POST" action="{{ route('admin.categories.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.categories._form')
</form>
@endsection
