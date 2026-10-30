@extends('admin.layouts.app')

@section('title', 'Edit TFC Page: ' . $page->title)

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h5 class="mb-0 fw-bold">Edit Page: {{ $page->title }}</h5>
        <div class="text-muted small">Route: <code>/policy/{{ $page->slug }}</code></div>
    </div>
    @if ($page->status === 'draft')
        <span class="badge bg-warning text-dark px-3 py-2"><i class="bi bi-shield-exclamation me-1"></i>Draft Safeguard Active</span>
    @endif
</div>

<form method="POST" action="{{ route('admin.tfc.pages.update', $page) }}">
    @csrf
    @method('PUT')
    @include('admin.tfc.pages._form')
</form>
@endsection
