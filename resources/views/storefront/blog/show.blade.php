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
    <div class="container">
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
            <p class="fs-5 text-muted mb-4" style="line-height: 1.65; max-width: 820px;">
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

{{-- Main Article + Sidebar Layout --}}
<div class="py-5" style="background:#ffffff; min-height: 50vh;">
    <div class="container">
        <div class="row g-5">

            {{-- Left Column: Article Body --}}
            <main class="col-lg-8">
                {{-- Featured Image Banner --}}
                @if ($blog->featured_image)
                    <div class="vr-post-hero-image mt-0">
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
                <div class="mt-4">
                    <a href="{{ route('blog.index') }}" class="btn-vr">
                        <i class="ri-arrow-left-line me-1"></i> Back to all Journal Stories
                    </a>
                </div>
            </main>

            {{-- Right Column: Editorial Sidebar with Recent Blogs --}}
            <aside class="col-lg-4">
                <div class="vr-blog-sidebar">

                    {{-- Recent Blogs Widget --}}
                    @if ($recent->isNotEmpty())
                        <div class="vr-sidebar-card">
                            <div class="vr-sidebar-title">
                                <i class="ri-history-line"></i> Recent Stories
                            </div>
                            <div class="vr-recent-list">
                                @foreach ($recent as $rec)
                                    <a href="{{ route('blog.show', $rec->slug) }}" class="vr-recent-item">
                                        <div class="vr-recent-thumb">
                                            @if ($rec->featured_image)
                                                <img src="{{ image_url($rec->featured_image) }}" alt="{{ $rec->title }}" loading="lazy">
                                            @else
                                                <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light text-muted">
                                                    <i class="ri-article-line fs-4 opacity-35"></i>
                                                </div>
                                            @endif
                                        </div>
                                        <div class="vr-recent-info">
                                            @if ($rec->category)
                                                <span class="vr-recent-cat">{{ $rec->category->name }}</span>
                                            @endif
                                            <div class="vr-recent-title" title="{{ $rec->title }}">
                                                {{ Str::limit($rec->title, 45) }}
                                            </div>
                                            <div class="vr-recent-meta">
                                                <span>{{ $rec->published_at->format('M d, Y') }}</span>
                                                <span>&middot;</span>
                                                <span>{{ $rec->readingTime() }}m read</span>
                                            </div>
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Categories Widget --}}
                    @if ($categories->isNotEmpty())
                        <div class="vr-sidebar-card">
                            <div class="vr-sidebar-title">
                                <i class="ri-price-tag-3-line"></i> Explore Topics
                            </div>
                            <div class="vr-sidebar-categories">
                                @foreach ($categories as $cat)
                                    <a href="{{ route('blog.index', ['category' => $cat->slug]) }}" class="vr-sidebar-cat-pill">
                                        <span>{{ $cat->name }}</span>
                                        @if ($cat->blogs_count)
                                            <span class="badge">{{ $cat->blogs_count }}</span>
                                        @endif
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Mini Newsletter Card --}}
                    <div class="vr-sidebar-newsletter">
                        <div class="d-flex align-items-center gap-2 mb-2 text-warning small fw-bold text-uppercase" style="letter-spacing: 0.12em;">
                            <i class="ri-leaf-line"></i> The Vanriti Circle
                        </div>
                        <h5>Join Our Inner Circle</h5>
                        <p>Mindful skincare rituals and timeless natural remedies sent to your inbox.</p>
                        <form action="{{ route('newsletter.subscribe') }}" method="POST">
                            @csrf
                            <input type="email" name="email" required placeholder="Enter your email..." class="form-control" aria-label="Email Address">
                            <button type="submit" class="btn-subscribe">Subscribe Free</button>
                        </form>
                    </div>

                </div>
            </aside>

        </div>
    </div>
</div>

{{-- Bottom Popular Blogs Section (4 in a row desktop, 1 on mobile) --}}
@if ($popular->isNotEmpty())
    <section class="vr-popular-section">
        <div class="container">
            <div class="vr-popular-header">
                <div>
                    <span class="vr-popular-eyebrow">
                        <i class="ri-fire-line"></i> Trending & Popular
                    </span>
                    <h2 class="vr-popular-title">Popular Stories From The Journal</h2>
                </div>
                <a href="{{ route('blog.index') }}" class="fw-semibold text-decoration-none" style="color:var(--vr-green);">
                    View all stories <i class="ri-arrow-right-line"></i>
                </a>
            </div>

            <div class="row g-4">
                @foreach ($popular as $pop)
                    <div class="col-xl-3 col-lg-3 col-md-6 col-12">
                        <article class="vr-journal-card">
                            <div class="vr-journal-card-img">
                                <a href="{{ route('blog.show', $pop->slug) }}" class="d-block w-100 h-100">
                                    @if ($pop->featured_image)
                                        <img src="{{ image_url($pop->featured_image) }}" alt="{{ $pop->title }}" loading="lazy">
                                    @else
                                        <div class="w-100 h-100 d-flex align-items-center justify-content-center bg-light text-muted">
                                            <i class="ri-article-line fs-1 opacity-25"></i>
                                        </div>
                                    @endif
                                </a>
                                @if ($pop->category)
                                    <span class="vr-journal-card-cat">{{ $pop->category->name }}</span>
                                @endif
                            </div>

                            <div class="vr-journal-card-body">
                                <div class="vr-journal-card-meta">
                                    <span>{{ $pop->published_at->format('M d, Y') }}</span>
                                    <span>&middot;</span>
                                    <span><i class="ri-time-line me-1"></i>{{ $pop->readingTime() }} min read</span>
                                </div>

                                <a href="{{ route('blog.show', $pop->slug) }}" class="text-decoration-none" title="{{ $pop->title }}">
                                    <h3 class="vr-journal-card-title">{{ Str::limit($pop->title, 50) }}</h3>
                                </a>

                                <p class="vr-journal-card-excerpt">
                                    {{ $pop->excerpt ? Str::limit($pop->excerpt, 75) : Str::limit(strip_tags($pop->content), 75) }}
                                </p>

                                <div class="vr-journal-card-footer">
                                    <span class="vr-journal-card-author text-truncate" style="max-width: 110px;">By {{ $pop->author_name }}</span>
                                    <a href="{{ route('blog.show', $pop->slug) }}" class="vr-journal-card-link">
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
