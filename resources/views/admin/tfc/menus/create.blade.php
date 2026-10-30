@extends('admin.layouts.app')

@section('title', 'Add Navigation Link')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold">Add TFC Navigation Link</h5>
</div>

<form method="POST" action="{{ route('admin.tfc.menus.store') }}">
    @csrf
    @include('admin.tfc.menus._form')
</form>
@endsection
