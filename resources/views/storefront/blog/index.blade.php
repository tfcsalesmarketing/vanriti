@extends('storefront.layouts.app')

@section('title', 'Journal')

@section('content')
<div class="vr-section" style="background:var(--vr-cream);">
    <div class="container text-center">
        <h1 class="vr-section-title mb-2">Journal</h1>
        <p class="vr-section-sub">Stories, guides and updates from Vanriti</p>
    </div>
</div>

<div class="vr-section">
    <div class="container">
        @if ($featured)
            <a href="{{ route('blog.show', $featured->slug) }}" class="text-decoration-none mb-4 d-block">
                <div class="row g-4 align-items-center mb-5">
                    <div class="col-lg-7">
                        @if ($featured->featured_image)
                            <div style="border-radius:var(--vr-radius);overflow:hidden;aspect-ratio:16/9;">
                                <img src="{{ image_url($featured->featured_image) }}" alt="{{ $featured->title }}" style="width:100%;height:100%;object-fit:cover;">
                            </div>
                        @endif
                    </div>
                    <div class="col-lg-5">
                        @if ($featured->category)
                            <span class="vr-pill-badge" style="background:var(--vr-green-soft);color:var(--vr-green);">{{ $featured->category->name }}</span>
                        @endif
                        <h2 class="mt-2 mb-3" style="font-weight:800;color:var(--vr-green-dark);">{{ $featured->title }}</h2>
                        <p class="text-muted mb-3" style="line-height:1.7;">{{ Str::limit($featured->excerpt, 200) }}</p>
                        <div class="small text-muted mb-3">
                            {{ $featured->published_at->format('M d, Y') }}
                            @if ($featured->author)
                                &middot; {{ $featured->author->name }}
                            @endif
                        </div>
                        <span class="btn-vr">Read More</span>
                    </div>
                </div>
            </a>
        @endif

        @if ($blogs->count() > ($featured ? 1 : 0))
            <div class="row g-4">
                @foreach ($blogs->skip($featured ? 1 : 0) as $post)
                    <div class="col-md-6 col-lg-4">
                        <a href="{{ route('blog.show', $post->slug) }}" class="text-decoration-none">
                            <div class="vr-card">
                                @if ($post->featured_image)
                                    <div class="img-wrap" style="aspect-ratio:16/10;">
                                        <img src="{{ image_url($post->featured_image) }}" alt="{{ $post->title }}">
                                    </div>
                                @endif
                                <div class="card-body">
                                    @if ($post->category)
                                        <span class="cat-name">{{ $post->category->name }}</span>
                                    @endif
                                    <h5 class="mt-1 mb-2" style="font-weight:700;color:var(--vr-green-dark);">{{ $post->title }}</h5>
                                    <p class="small text-muted mb-2" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden;">{{ $post->excerpt }}</p>
                                    <div class="small text-muted mt-auto">
                                        {{ $post->published_at->format('M d, Y') }}
                                        @if ($post->author)
                                            &middot; {{ $post->author->name }}
                                        @endif
                                    </div>
                                    <span class="vr-link-underline small fw-semibold mt-2 d-inline-block" style="color:var(--vr-green);">Read More</span>
                                </div>
                            </div>
                        </a>
                    </div>
                @endforeach
            </div>

            <div class="mt-4">
                {{ $blogs->links('storefront.partials.pagination') }}
            </div>
        @elseif (!$featured)
            <div class="vr-empty">
                <i class="bi bi-journal-text"></i>
                <h4 class="mt-3 fw-bold">No posts yet</h4>
                <p>We're working on something. Check back soon!</p>
            </div>
        @endif
    </div>
</div>
@endsection
