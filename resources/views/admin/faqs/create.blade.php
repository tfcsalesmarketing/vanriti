@extends('admin.layouts.app')

@section('title', 'Add FAQ')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Add FAQ</h5>
</div>

<form method="POST" action="{{ route('admin.faqs.store') }}">
    @csrf
    @include('admin.faqs._form')
</form>
@endsection