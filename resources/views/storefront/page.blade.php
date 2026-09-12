@extends('storefront.layouts.app')

@section('title', $page->meta_title ?: $page->title)
@section('meta_description', $page->meta_description ?: setting('meta_description'))
@section('meta_keywords', setting('meta_keywords'))
@section('og_title', $page->meta_title ?: $page->title)
@section('og_description', $page->meta_description ?: setting('meta_description'))

@section('content')
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@@type": "ListItem",
            "position": 1,
            "name": {!! json_encode(store_name(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
            "item": {!! json_encode(route('home'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        },
        {
            "@@type": "ListItem",
            "position": 2,
            "name": {!! json_encode($page->title, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
            "item": {!! json_encode(request()->url(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        }
    ]
}
</script>
<div class="container py-5">
    <div style="background:#ffffff;border-radius:16px;box-shadow:0 8px 30px rgba(38,61,37,0.08);padding:2rem;">
        {!! $page->content !!}
    </div>
</div>
@endsection
