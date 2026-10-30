@extends('admin.layouts.app')

@section('title', 'Create TFC Page')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold">Create New TFC Page / Policy</h5>
</div>

<form method="POST" action="{{ route('admin.tfc.pages.store') }}">
    @csrf
    @include('admin.tfc.pages._form')
</form>
@endsection
