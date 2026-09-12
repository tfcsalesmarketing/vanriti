@extends('admin.layouts.app')

@section('title', 'Edit FAQ')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Edit FAQ</h5>
</div>

<form method="POST" action="{{ route('admin.faqs.update', $faq) }}">
    @csrf
    @method('PUT')
    @include('admin.faqs._form')
</form>
@endsection