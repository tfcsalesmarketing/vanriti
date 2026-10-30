@extends('admin.layouts.app')

@section('title', 'Edit Navigation Link: ' . $menu->title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <h5 class="mb-0 fw-bold">Edit Navigation Link: {{ $menu->title }}</h5>
</div>

<form method="POST" action="{{ route('admin.tfc.menus.update', $menu) }}">
    @csrf
    @method('PUT')
    @include('admin.tfc.menus._form')
</form>
@endsection
