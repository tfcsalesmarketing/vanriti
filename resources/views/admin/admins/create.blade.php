@extends('admin.layouts.app')

@section('title', 'Add Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Add Admin</h5>
</div>

<form method="POST" action="{{ route('admin.admins.store') }}">
    @csrf
    @include('admin.admins._form')
</form>
@endsection