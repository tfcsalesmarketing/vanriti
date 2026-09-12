@extends('storefront.layouts.app')

@section('title', 'Policies')

@section('content')
<div class="vr-section" style="background:var(--vr-cream);">
    <div class="container">
        <nav class="vr-breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="mx-1">/</span>
            <span>Return Policy</span>
        </nav>
    </div>
</div>

<div class="vr-section">
    <div class="container">
        <h1 class="mb-4" style="font-weight:800;color:var(--vr-green-dark);">Return &amp; Refund Policy</h1>

        <div style="line-height:1.8;font-size:1.05rem;">
            {!! nl2br(e($content)) !!}
        </div>
    </div>
</div>
@endsection
