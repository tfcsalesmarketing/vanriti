@extends('admin.layouts.app')

@section('title', 'Edit Instagram Reel')

@section('content')
<div class="d-flex align-items-center gap-2 mb-4">
    <a href="{{ route('admin.reels.index') }}" class="btn btn-sm btn-outline-secondary">
        <i class="bi bi-arrow-left"></i>
    </a>
    <div>
        <h4 class="mb-0 fw-bold text-dark">Edit Instagram Reel</h4>
        <small class="text-muted">Update reel details, tagged product, or website visibility</small>
    </div>
</div>

<form method="POST" action="{{ route('admin.reels.update', $reel) }}" enctype="multipart/form-data" id="vrReelForm">
    @csrf
    @method('PUT')
    @include('admin.marketing.reels._form')
</form>
@endsection

