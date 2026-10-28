@extends('storefront.layouts.app')

@section('title', $blog->seo_title ?: $blog->title)
@section('meta_description', $blog->meta_description ?: ($blog->excerpt ?: Str::limit(strip_tags($blog->content), 160)))
@section('meta_keywords', $blog->keywords ?: setting('meta_keywords'))
@section('og_title', $blog->seo_title ?: $blog->title)
@section('og_description', $blog->meta_description ?: ($blog->excerpt ?: Str::limit(strip_tags($blog->content), 160)))
@section('canonical', route('blog.show', $blog))
@section('og_type', 'article')
@section('og_image', $blog->featured_image ? image_url($blog->featured_image) : image_url(null, 'favicon.ico'))
@section('og_image_alt', $blog->title)

@push('styles')
<link rel="stylesheet" href="{{ asset_version('css/vr-journal-premium.css') }}">
@endpush

@section('content')
{{-- Reading Progress Bar --}}
<div id="vrReadingProgress" class="vr-reading-progress"></div>

{{-- Breadcrumb Schema --}}
<script nonce="{{ $cspNonce }}" type="application/ld+json">
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
        @if ($blog->category)
        {
            "@@type": "ListItem",
            "position": 3,
            "name": {!! json_encode($blog->category->name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
            "item": {!! json_encode(route('blog.index', ['category' => $blog->category->slug]), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        },
        {
            "@@type": "ListItem",
            "position": 4,
            "name": {!! json_encode($blog->title, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
            "item": {!! json_encode(route('blog.show', $blog), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        }
        @else
        {
            "@@type": "ListItem",
            "position": 3,
            "name": {!! json_encode($blog->title, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!},
            "item": {!! json_encode(route('blog.show', $blog), JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
        }
        @endif
    ]
}
</script>

{{-- BlogPosting Schema --}}
<script nonce="{{ $cspNonce }}" type="application/ld+json">
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
        "name": {!! json_encode($blog->author_name, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT) !!}
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

{{-- Article Header Section --}}
<header class="vr-post-header">
    <div class="container" style="max-width: 900px;">
        <nav class="vr-post-breadcrumb" aria-label="Breadcrumb">
            <a href="{{ route('home') }}">Home</a>
            <span><i class="ri-arrow-right-s-line"></i></span>
            <a href="{{ route('blog.index') }}">Journal</a>
            @if ($blog->category)
                <span><i class="ri-arrow-right-s-line"></i></span>
                <a href="{{ route('blog.index', ['category' => $blog->category->slug]) }}">{{ $blog->category->name }}</a>
            @endif
            <span><i class="ri-arrow-right-s-line"></i></span>
            <span class="text-truncate" style="max-width: 280px;">{{ $blog->title }}</span>
        </nav>

        <div class="d-flex align-items-center gap-2 mb-3">
            @if ($blog->category)
                <a href="{{ route('blog.index', ['category' => $blog->category->slug]) }}" class="vr-category-badge">
                    {{ $blog->category->name }}
                </a>
            @endif
            <span class="text-muted small">&middot;</span>
            <span class="text-muted small"><i class="ri-time-line me-1"></i>{{ $blog->readingTime() }} min read</span>
        </div>

        <h1 class="vr-post-title">{{ $blog->title }}</h1>

        @if ($blog->excerpt)
            <p class="fs-5 text-muted mb-4" style="line-height: 1.65; max-width: 780px;">
                {{ $blog->excerpt }}
            </p>
        @endif

        <div class="vr-post-author-bar">
            <div class="vr-post-author">
                <div class="vr-post-author-avatar">
                    {{ strtoupper(substr($blog->author_name, 0, 1)) }}
                </div>
                <div class="vr-post-meta-details">
                    <span class="vr-post-author-name">{{ $blog->author_name }}</span>
                    <div class="vr-post-date-row">
                        <span>Published on {{ $blog->published_at->format('F d, Y') }}</span>
                    </div>
                </div>
            </div>

            {{-- Top Social Sharing --}}
            @php
                $shareUrl = urlencode(route('blog.show', $blog));
                $shareText = urlencode($blog->title . ' - ' . store_name());
            @endphp
            <div class="vr-share-bar">
                <span class="vr-share-label">Share:</span>
                <a href="https://api.whatsapp.com/send?text={{ $shareText }}%20{{ $shareUrl }}" 
                   target="_blank" rel="noopener" class="vr-share-btn btn-whatsapp" title="Share via WhatsApp">
                    <i class="ri-whatsapp-line"></i>
                </a>
                <a href="https://twitter.com/intent/tweet?text={{ $shareText }}&url={{ $shareUrl }}" 
                   target="_blank" rel="noopener" class="vr-share-btn btn-twitter" title="Share on X">
                    <i class="ri-twitter-x-line"></i>
                </a>
                <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" 
                   target="_blank" rel="noopener" class="vr-share-btn btn-facebook" title="Share on Facebook">
                    <i class="ri-facebook-fill"></i>
                </a>
                <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" 
                   target="_blank" rel="noopener" class="vr-share-btn btn-linkedin" title="Share on LinkedIn">
                    <i class="ri-linkedin-fill"></i>
                </a>
                <button type="button" class="vr-share-btn btn-copy js-copy-link" 
                        data-url="{{ route('blog.show', $blog) }}" title="Copy Article Link">
                    <i class="ri-link"></i>
                </button>
            </div>
        </div>
    </div>
</header>

{{-- Article Content Section --}}
<article class="py-5" style="background:#ffffff; min-height: 50vh;">
    <div class="container" style="max-width: 900px;">

        {{-- Featured Image Banner --}}
        @if ($blog->featured_image)
            <div class="vr-post-hero-image">
                <img src="{{ image_url($blog->featured_image) }}" alt="{{ $blog->title }}">
            </div>
        @endif

        {{-- Editorial Rich Body --}}
        <div class="vr-article-body" id="articleBody">
            {!! clean_html($blog->content) !!}
        </div>

        {{-- Bottom Share Bar & Engagement --}}
        <div class="mt-5 pt-4 border-top">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div class="d-flex align-items-center gap-2">
                    <i class="ri-share-forward-line fs-5 text-muted"></i>
                    <span class="fw-bold" style="color:var(--vr-green-dark);">Liked this article? Share it forward:</span>
                </div>
                <div class="vr-share-bar">
                    <a href="https://api.whatsapp.com/send?text={{ $shareText }}%20{{ $shareUrl }}" 
                       target="_blank" rel="noopener" class="vr-share-btn btn-whatsapp" title="Share via WhatsApp">
                        <i class="ri-whatsapp-line"></i>
                    </a>
                    <a href="https://twitter.com/intent/tweet?text={{ $shareText }}&url={{ $shareUrl }}" 
                       target="_blank" rel="noopener" class="vr-share-btn btn-twitter" title="Share on X">
                        <i class="ri-twitter-x-line"></i>
                    </a>
                    <a href="https://www.facebook.com/sharer/sharer.php?u={{ $shareUrl }}" 
                       target="_blank" rel="noopener" class="vr-share-btn btn-facebook" title="Share on Facebook">
                        <i class="ri-facebook-fill"></i>
                    </a>
                    <a href="https://www.linkedin.com/sharing/share-offsite/?url={{ $shareUrl }}" 
                       target="_blank" rel="noopener" class="vr-share-btn btn-linkedin" title="Share on LinkedIn">
                        <i class="ri-linkedin-fill"></i>
                    </a>
                    <button type="button" class="vr-share-btn btn-copy js-copy-link" 
                            data-url="{{ route('blog.show', $blog) }}" title="Copy Article Link">
                        <i class="ri-link"></i>
                    </button>
                </div>
            </div>
        </div>

        {{-- Author Bio Card --}}
        <div class="vr-author-card">
            <div class="vr-author-card-avatar">
                {{ strtoupper(substr($blog->author_name, 0, 1)) }}
            </div>
            <div class="vr-author-card-content flex-grow-1">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                    <h5>Written by {{ $blog->author_name }}</h5>
                    <span class="badge rounded-pill bg-light text-muted border px-3 py-1">Author & Wellness Guide</span>
                </div>
                <p>
                    Passionate about holistic skincare traditions, sustainable rituals, and natural living. Curating the timeless harmony between nature and everyday beauty at {{ store_name() }}.
                </p>
            </div>
        </div>

        {{-- Back to Journal Button --}}
        <div class="text-center mt-5">
            <a href="{{ route('blog.index') }}" class="btn-vr">
                <i class="ri-arrow-left-line me-1"></i> Back to all Journal Stories
            </a>
        </div>

    </div>
</article>

{{-- Related Articles Section --}}
@if ($related->isNotEmpty())
    <section class="py-5" style="background:#FAF8F3; border-top: 1px solid rgba(230, 226, 214, 0.7);">
        <div class="container">
            <div class="d-flex align-items-end justify-content-between flex-wrap gap-2 mb-4">
                <div>
                    <span class="vr-journal-eyebrow mb-1">
                        <i class="ri-sparkling-line"></i> Keep Exploring
                    </span>
                    <h2 class="h3 fw-bold mb-0" style="color:var(--vr-green-dark);">Related Stories & Guides</h2>
                </div>
                <a href="{{ route('blog.index') }}" class="fw-semibold text-decoration-none" style="color:var(--vr-green);">
                    View all stories <i class="ri-arrow-right-line"></i>
                </a>
            </div>

            <div class="row g-4">
                @foreach ($related as $rel)
                    <div class="col-md-4">
                        <article class="vr-journal-card">
                            <div class="vr-journal-card-img">
                                <a href="{{ route('blog.show', $rel->slug) }}" class="d-block w-100 h-100">
                                    @if ($rel->featured_image)
                                        <img src="{{ image_url($rel->featured_image) }}" alt="{{ $rel->title }}" loading="lazy">
                                    @else
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light text-muted">
                                            <i class="ri-article-line fs-1 opacity-25"></i>
                                        </div>
                                    @endif
                                </a>
                                @if ($rel->category)
                                    <span class="vr-journal-card-cat">{{ $rel->category->name }}</span>
                                @endif
                            </div>

                            <div class="vr-journal-card-body">
                                <div class="vr-journal-card-meta">
                                    <span>{{ $rel->published_at->format('M d, Y') }}</span>
                                    <span>&middot;</span>
                                    <span>{{ $rel->readingTime() }} min read</span>
                                </div>

                                <a href="{{ route('blog.show', $rel->slug) }}" class="text-decoration-none">
                                    <h3 class="vr-journal-card-title">{{ $rel->title }}</h3>
                                </a>

                                <p class="vr-journal-card-excerpt">
                                    {{ $rel->excerpt ?: Str::limit(strip_tags($rel->content), 100) }}
                                </p>

                                <div class="vr-journal-card-footer">
                                    <span class="vr-journal-card-author">By {{ $rel->author_name }}</span>
                                    <a href="{{ route('blog.show', $rel->slug) }}" class="vr-journal-card-link">
                                        Read <i class="ri-arrow-right-line"></i>
                                    </a>
                                </div>
                            </div>
                        </article>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
@endif

{{-- Copy to clipboard Toast --}}
<div id="copyToast" class="vr-copy-toast">
    <i class="ri-check-line text-warning"></i>
    <span>Article link copied to clipboard!</span>
</div>

@push('scripts')
<script nonce="{{ $cspNonce }}">
document.addEventListener('DOMContentLoaded', function () {
    // 1. Reading Progress Bar
    const progressBar = document.getElementById('vrReadingProgress');
    const articleBody = document.getElementById('articleBody');

    if (progressBar && articleBody) {
        window.addEventListener('scroll', function () {
            const articleRect = articleBody.getBoundingClientRect();
            const totalHeight = articleBody.offsetHeight;
            const scrollDistance = -articleRect.top;
            let percent = (scrollDistance / (totalHeight - window.innerHeight * 0.5)) * 100;
            percent = Math.min(Math.max(percent, 0), 100);
            progressBar.style.width = percent + '%';
        }, { passive: true });
    }

    // 2. Copy Link Button with Toast
    const copyBtns = document.querySelectorAll('.js-copy-link');
    const toast = document.getElementById('copyToast');

    copyBtns.forEach(function (btn) {
        btn.addEventListener('click', function () {
            const url = this.getAttribute('data-url') || window.location.href;
            if (navigator.clipboard) {
                navigator.clipboard.writeText(url).then(showToast);
            } else {
                const temp = document.createElement('input');
                temp.value = url;
                document.body.appendChild(temp);
                temp.select();
                document.execCommand('copy');
                document.body.removeChild(temp);
                showToast();
            }
        });
    });

    function showToast() {
        if (!toast) return;
        toast.classList.add('show');
        setTimeout(function () {
            toast.classList.remove('show');
        }, 3000);
    }
});
</script>
@endpush
@endsection
