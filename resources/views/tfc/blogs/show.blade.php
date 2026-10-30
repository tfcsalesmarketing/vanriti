@extends('tfc.layouts.app')

@section('title', ($blog->meta_title ?: $blog->title) . ' | TFC Sales & Marketing')
@section('meta_description', $blog->meta_description ?: $blog->excerpt)

@section('content')
<div class="container py-4" style="max-width: 900px;">
    {{-- Breadcrumb --}}
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ tfc_route('home') }}" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="{{ tfc_route('blogs') }}" class="text-decoration-none">Blogs</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ $blog->title }}</li>
        </ol>
    </nav>

    <article>
        <div class="mb-4">
            <span class="badge tfc-badge-gold mb-2">TFC Corporate Update</span>
            <h1 class="display-6 fw-bold text-dark mb-3">{{ $blog->title }}</h1>
            <div class="text-muted small">
                <i class="bi bi-clock-history me-1"></i>Published on {{ $blog->published_at ? $blog->published_at->format('d M Y, h:i A') : $blog->created_at->format('d M Y') }}
            </div>
        </div>

        @if ($blog->featured_image)
            <div class="mb-4 text-center">
                <img src="{{ image_url($blog->featured_image) }}" alt="{{ $blog->title }}" class="img-fluid rounded-4 shadow-sm" style="max-height: 420px; width: 100%; object-fit: cover;">
            </div>
        @endif

        @if ($blog->excerpt)
            <p class="lead text-muted mb-4 border-start border-4 border-warning ps-3 py-1">
                {{ $blog->excerpt }}
            </p>
        @endif

        <div class="article-content text-dark mb-5" style="font-size: 1.05rem; line-height: 1.8;">
            {!! clean_html($blog->content) !!}
        </div>
    </article>

    @if ($recentBlogs->isNotEmpty())
        <div class="mt-5 pt-5 border-top">
            <h5 class="fw-bold mb-4">Related Articles</h5>
            <div class="row g-4">
                @foreach ($recentBlogs as $rb)
                    <div class="col-md-4">
                        <div class="card tfc-card h-100 p-3">
                            <small class="text-muted mb-1">{{ $rb->published_at ? $rb->published_at->format('d M Y') : '' }}</small>
                            <h6 class="fw-bold mb-2">
                                <a href="{{ tfc_route('blogs.show', $rb->slug) }}" class="text-dark text-decoration-none">
                                    {{ $rb->title }}
                                </a>
                            </h6>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif
</div>
@endsection
