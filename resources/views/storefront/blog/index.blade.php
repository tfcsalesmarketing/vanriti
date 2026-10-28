@extends('storefront.layouts.app')

@php
    $pageTitle = $activeCategory ? $activeCategory->name . ' - Journal' : 'Journal & Wellness Rituals';
    $metaDesc = 'Explore natural skincare rituals, holistic wellness stories, and pure living guides from ' . store_name() . '.';
@endphp

@section('title', $pageTitle)
@section('meta_description', $metaDesc)
@section('canonical', ($blogs->currentPage() > 1 || $activeCategory || $search) ? canonical_url() : route('blog.index'))
@section('og_title', $pageTitle . ' - ' . store_name())
@section('og_description', $metaDesc)
@section('og_type', 'blog')

@push('styles')
<link rel="stylesheet" href="{{ asset_version('css/vr-journal-premium.css') }}">
@endpush

@section('content')
{{-- Structured Data for Blog Collection --}}
<script nonce="{{ $cspNonce }}" type="application/ld+json">
{
    "@@context": "https://schema.org",
    "@@type": "Blog",
    "name": {!! json_encode(store_name() . ' Journal', JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
    "description": {!! json_encode($metaDesc, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
    "url": {!! json_encode(route('blog.index'), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
    "publisher": {
        "@@type": "Organization",
        "name": {!! json_encode(store_name(), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
    }
}
</script>

{{-- Premium Journal Hero Banner --}}
<section class="vr-journal-hero">
    <div class="container text-center">
        <div class="vr-journal-eyebrow">
            <i class="ri-leaf-line"></i> The {{ store_name() }} Journal
        </div>
        <h1 class="vr-journal-title">Stories, Wellness Rituals & Pure Living</h1>
        <p class="vr-journal-lead">
            Thoughtful guides on clean botanicals, conscious self-care rituals, and timeless natural wellness curated for your everyday lifestyle.
        </p>

        {{-- Filter Navigation & Search --}}
        <div class="vr-journal-nav justify-content-center">
            <div class="vr-journal-pills justify-content-center">
                <a href="{{ route('blog.index') }}" class="vr-journal-pill {{ empty($activeCategory) && empty($search) ? 'active' : '' }}">
                    All Stories
                </a>
                @foreach ($categories as $cat)
                    <a href="{{ route('blog.index', ['category' => $cat->slug]) }}" 
                       class="vr-journal-pill {{ $activeCategory && $activeCategory->id === $cat->id ? 'active' : '' }}">
                        {{ $cat->name }}
                        @if ($cat->blogs_count)
                            <span class="pill-count">{{ $cat->blogs_count }}</span>
                        @endif
                    </a>
                @endforeach
            </div>

            <form action="{{ route('blog.index') }}" method="GET" class="vr-journal-search">
                @if ($activeCategory)
                    <input type="hidden" name="category" value="{{ $activeCategory->slug }}">
                @endif
                <i class="ri-search-line"></i>
                <input type="text" name="q" value="{{ $search }}" placeholder="Search topics, ingredients..." aria-label="Search Journal">
            </form>
        </div>
    </div>
</section>

{{-- Main Content Section --}}
<section class="py-5" style="background:#FAF8F3; min-height: 60vh;">
    <div class="container">

        {{-- Active Filter Feedback --}}
        @if ($activeCategory || $search !== '')
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 pb-2 border-bottom">
                <div class="d-flex align-items-center gap-2">
                    <span class="text-muted small">Filtering by:</span>
                    @if ($activeCategory)
                        <span class="badge bg-white text-dark border px-3 py-2 rounded-pill fw-semibold">
                            Category: {{ $activeCategory->name }}
                        </span>
                    @endif
                    @if ($search !== '')
                        <span class="badge bg-white text-dark border px-3 py-2 rounded-pill fw-semibold">
                            Keyword: "{{ $search }}"
                        </span>
                    @endif
                    <span class="text-muted small ms-2">({{ $blogs->total() }} {{ Str::plural('story', $blogs->total()) }})</span>
                </div>
                <a href="{{ route('blog.index') }}" class="small fw-bold text-decoration-none" style="color:var(--vr-green);">
                    <i class="ri-close-circle-line me-1"></i>Reset all filters
                </a>
            </div>
        @endif

        {{-- Hero Featured Magazine Post (Shown when on page 1 without active filter/search) --}}
        @if ($featured)
            <div class="vr-featured-post-card">
                <div class="row g-0 align-items-stretch">
                    <div class="col-lg-7">
                        <a href="{{ route('blog.show', $featured->slug) }}" class="d-block h-100">
                            <div class="vr-featured-img-wrap">
                                @if ($featured->featured_image)
                                    <img src="{{ image_url($featured->featured_image) }}" alt="{{ $featured->title }}" loading="lazy">
                                @else
                                    <div class="w-100 h-100 d-flex align-items-center justify-content-center text-muted">
                                        <i class="ri-article-line display-4 opacity-25"></i>
                                    </div>
                                @endif
                                <div class="vr-featured-badge">
                                    <i class="ri-sparkling-fill text-warning"></i> Featured Story
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-lg-5">
                        <div class="vr-featured-body h-100">
                            <div class="vr-post-meta">
                                @if ($featured->category)
                                    <a href="{{ route('blog.index', ['category' => $featured->category->slug]) }}" class="vr-category-badge">
                                        {{ $featured->category->name }}
                                    </a>
                                @endif
                                <span><i class="ri-time-line me-1"></i>{{ $featured->readingTime() }} min read</span>
                            </div>

                            <a href="{{ route('blog.show', $featured->slug) }}" class="text-decoration-none">
                                <h2 class="vr-featured-title">{{ $featured->title }}</h2>
                            </a>

                            <p class="vr-featured-excerpt">
                                {{ $featured->excerpt ?: Str::limit(strip_tags($featured->content), 180) }}
                            </p>

                            <div class="vr-author-row">
                                <div class="vr-author-info">
                                    <div class="vr-author-avatar">
                                        {{ strtoupper(substr($featured->author_name, 0, 1)) }}
                                    </div>
                                    <div>
                                        <div class="vr-author-name">{{ $featured->author_name }}</div>
                                        <div class="vr-author-date">{{ $featured->published_at->format('M d, Y') }}</div>
                                    </div>
                                </div>
                                <a href="{{ route('blog.show', $featured->slug) }}" class="vr-btn-read">
                                    Read Story <i class="ri-arrow-right-line"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        @endif

        {{-- Stories Grid --}}
        @if ($blogs->count() > 0)
            <div class="row g-4">
                @foreach ($blogs as $post)
                    <div class="col-md-6 col-lg-4">
                        <article class="vr-journal-card">
                            <div class="vr-journal-card-img">
                                <a href="{{ route('blog.show', $post->slug) }}" class="d-block w-100 h-100">
                                    @if ($post->featured_image)
                                        <img src="{{ image_url($post->featured_image) }}" alt="{{ $post->title }}" loading="lazy">
                                    @else
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light text-muted">
                                            <i class="ri-article-line fs-1 opacity-25"></i>
                                        </div>
                                    @endif
                                </a>
                                @if ($post->category)
                                    <a href="{{ route('blog.index', ['category' => $post->category->slug]) }}" class="vr-journal-card-cat text-decoration-none">
                                        {{ $post->category->name }}
                                    </a>
                                @endif
                            </div>

                            <div class="vr-journal-card-body">
                                <div class="vr-journal-card-meta">
                                    <span>{{ $post->published_at->format('M d, Y') }}</span>
                                    <span>&middot;</span>
                                    <span><i class="ri-time-line me-1"></i>{{ $post->readingTime() }} min read</span>
                                </div>

                                <a href="{{ route('blog.show', $post->slug) }}" class="text-decoration-none">
                                    <h3 class="vr-journal-card-title">{{ $post->title }}</h3>
                                </a>

                                <p class="vr-journal-card-excerpt">
                                    {{ $post->excerpt ?: Str::limit(strip_tags($post->content), 120) }}
                                </p>

                                <div class="vr-journal-card-footer">
                                    <span class="vr-journal-card-author">By {{ $post->author_name }}</span>
                                    <a href="{{ route('blog.show', $post->slug) }}" class="vr-journal-card-link">
                                        Read <i class="ri-arrow-right-line"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>

            {{-- Pagination --}}
            <div class="mt-5 pt-3 d-flex justify-content-center">
                {{ $blogs->links('storefront.partials.pagination') }}
            </div>
        @elseif (! $featured)
            <div class="vr-journal-empty">
                <div class="vr-journal-empty-icon">
                    <i class="ri-book-open-line"></i>
                </div>
                <h4>No articles found</h4>
                <p>We couldn't find any stories matching your current filter criteria. Explore our full library or try another search term.</p>
                <a href="{{ route('blog.index') }}" class="btn-vr">
                    <i class="ri-arrow-left-line me-1"></i> View All Stories
                </a>
            </div>
        @endif

        {{-- Luxury Journal Newsletter Banner --}}
        <div class="vr-journal-newsletter">
            <div class="row align-items-center g-4">
                <div class="col-lg-6">
                    <div class="d-inline-flex align-items-center gap-2 mb-2 text-warning small fw-bold text-uppercase" style="letter-spacing: 0.15em;">
                        <i class="ri-mail-star-line"></i> The Vanriti Circle
                    </div>
                    <h3>Rituals, Recipes & Timeless Wellness</h3>
                    <p class="mb-0">
                        Join our quiet circle. Receive mindful skincare rituals, natural remedies, and exclusive seasonal formulations directly to your inbox.
                    </p>
                </div>
                <div class="col-lg-6">
                    <form action="{{ route('newsletter.subscribe') }}" method="POST" class="vr-newsletter-form">
                        @csrf
                        <div class="input-group">
                            <input type="email" name="email" required placeholder="Enter your email address..." class="form-control" aria-label="Email Address">
                            <button type="submit" class="btn">Subscribe</button>
                        </div>
                        <div class="small opacity-75 mt-2 ps-2">No spam. Only meaningful, slow living wisdom. Unsubscribe anytime.</div>
                    </form>
                </div>
            </div>
        </div>

    </div>
</section>
@endsection
