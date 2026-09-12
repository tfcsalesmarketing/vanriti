@extends('admin.layouts.app')

@section('title', 'Edit Admin')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h5 class="mb-0 fw-bold">Edit Admin</h5>
</div>

<form method="POST" action="{{ route('admin.admins.update', $admin->id) }}">
    @csrf
    @method('PUT')
    @include('admin.admins._form')
</form>
@endsection