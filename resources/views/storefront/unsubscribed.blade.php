@extends('storefront.layouts.app')

@section('title', 'Unsubscribed')

@section('content')
<div class="vr-section">
    <div class="container">
        <div class="mx-auto" style="max-width:640px;text-align:center;">
            <h1 class="mb-3" style="font-weight:800;color:var(--vr-green-dark);">You have been unsubscribed</h1>

            <p style="line-height:1.8;">
                This email address will no longer receive offers and news from {{ store_name() }}.
                Changed your mind? You can subscribe again any time from the footer of any page.
            </p>

            <a href="{{ route('home') }}" class="btn btn-dark mt-3">Continue shopping</a>
        </div>
    </div>
</div>
@endsection
