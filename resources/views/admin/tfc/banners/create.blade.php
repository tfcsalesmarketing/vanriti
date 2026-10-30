@extends('admin.layouts.app')

@section('title', 'Create TFC Banner')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold">Create New TFC Banner</h5>
</div>

<form method="POST" action="{{ route('admin.tfc.banners.store') }}">
    @csrf
    @include('admin.tfc.banners._form')
</form>
@endsection
