@extends('storefront.layouts.app')

@section('title', $blog->seo_title ?: $blog->title)
@section('meta_description', $blog->meta_description ?: setting('meta_description'))
@section('meta_keywords', setting('meta_keywords'))
@section('og_title', $blog->seo_title ?: $blog->title)
@section('og_description', $blog->meta_description ?: setting('meta_description'))

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
            "name": "Journal",
            "item": {!! json_encode(route('blog.index'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        },
        {
            "@@type": "ListItem",
            "position": 3,
            "name": {!! json_encode($blog->title, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
            "item": {!! json_encode(route('blog.show', $blog), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        }
    ]
}
</script>
<script type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "BlogPosting",
    "headline": {!! json_encode($blog->title, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
    @if ($blog->excerpt)"description": {!! json_encode(strip_tags($blog->excerpt), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},@endif
    "image": {!! json_encode($blog->featured_image ? image_url($blog->featured_image) : image_url(null, 'favicon.ico'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
    "datePublished": {!! json_encode($blog->published_at?->toAtomString(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
    "dateModified": {!! json_encode($blog->updated_at?->toAtomString(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
    "author": {
        "@@type": "Person",
        "name": {!! json_encode($blog->author?->name ?? store_name(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
    },
    "publisher": {
        "@@type": "Organization",
        "name": {!! json_encode(store_name(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
        "logo": {
            "@@type": "ImageObject",
            "url": {!! json_encode(image_url(setting('store_logo'), 'favicon.ico'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        }
    },
    "mainEntityOfPage": {
        "@@type": "WebPage",
        "@@id": {!! json_encode(route('blog.show', $blog), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
    }
}
</script>
<div class="vr-section" style="background:var(--vr-cream);">
    <div class="container">
        <nav class="vr-breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span class="mx-1">/</span>
            <a href="{{ route('blog.index') }}">Journal</a>
            <span class="mx-1">/</span>
            <span>{{ Str::limit($blog->title, 50) }}</span>
        </nav>
    </div>
</div>

<div class="vr-section">
    <div class="container">
        <div class="mb-4">
            @if ($blog->category)
                <span class="vr-pill-badge" style="background:var(--vr-green-soft);color:var(--vr-green);">{{ $blog->category->name }}</span>
            @endif
            <h1 class="mt-2 mb-3" style="font-weight:800;color:var(--vr-green-dark);">{{ $blog->title }}</h1>
            <div class="small text-muted">
                {{ $blog->published_at->format('F d, Y') }}
                @if ($blog->author)
                    &middot; By {{ $blog->author->name }}
                @endif
            </div>
        </div>

        @if ($blog->featured_image)
            <div style="border-radius:var(--vr-radius);overflow:hidden;margin-bottom:2rem;">
                <img src="{{ image_url($blog->featured_image) }}" alt="{{ $blog->title }}" style="width:100%;height:auto;">
            </div>
        @endif

        <div class="blog-content" style="line-height:1.8;font-size:1.05rem;">
            {!! $blog->content !!}
        </div>

        <div class="mt-5 pt-4 border-top">
            <p class="small text-muted">
                <i class="bi bi-share me-1"></i> Share this post with someone who might find it useful.
            </p>
        </div>
    </div>
</div>

@if ($related->count())
    <div class="vr-section" style="background:var(--vr-cream);">
        <div class="container">
            <h3 class="vr-section-title mb-4">Related Posts</h3>
            <div class="row g-4">
                @foreach ($related as $rel)
                    <div class="col-md-4">
                        <a href="{{ route('blog.show', $rel->slug) }}" class="text-decoration-none">
                            <div class="vr-card">
                                @if ($rel->featured_image)
                                    <div class="img-wrap" style="aspect-ratio:16/10;">
                                        <img src="{{ image_url($rel->featured_image) }}" alt="{{ $rel->title }}">
                                    </div>
                                @endif
                                <div class="card-body">
                                    @if ($rel->category)
                                        <span class="cat-name">{{ $rel->category->name }}</span>
                                    @endif
                                    <h6 class="mt-1 mb-0" style="font-weight:700;color:var(--vr-green-dark);">{{ Str::limit($rel->title, 60) }}</h6>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
@endif
@endsection
