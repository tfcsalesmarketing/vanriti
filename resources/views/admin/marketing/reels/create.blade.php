@extends('admin.layouts.app')

@section('title', 'Add Instagram Reel')

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.reels.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0 fw-bold text-dark">Add Instagram Reel</h4>
        <small class="text-muted">Publish a new reel to the "Watch &amp; Shop" homepage section</small>
    </div>
</div>

<form method="POST" action="{{ route('admin.reels.store') }}" enctype="multipart/form-data">
    @csrf
    @include('admin.marketing.reels._form')
</form>
@endsection
