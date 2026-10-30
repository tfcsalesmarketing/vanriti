@extends('tfc.layouts.app')

@section('title', 'Corporate News & Wellness Blogs | TFC Sales & Marketing')

@section('content')
<section class="tfc-page-header">
    <div class="container text-center">
        <span class="tfc-badge-gold mb-2 d-inline-block">Updates &amp; Insights</span>
        <h1 class="display-5 fw-bold text-white mb-2">TFC News &amp; Articles</h1>
        <p class="text-white-50 lead mb-0">Insights on Ayurvedic wellness, direct selling ethics, and entrepreneurship.</p>
    </div>
</section>

<section class="py-5">
    <div class="container">
        <div class="row g-4">
            @forelse ($blogs as $blog)
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card tfc-card h-100">
                        @if ($blog->featured_image)
                            <img src="{{ image_url($blog->featured_image) }}" alt="{{ $blog->title }}" class="card-img-top" style="height: 200px; object-fit: cover;">
                        @endif
                        <div class="card-body p-4 d-flex flex-column">
                            <small class="text-muted mb-2">
                                <i class="bi bi-calendar3 me-1"></i>{{ $blog->published_at ? $blog->published_at->format('d M Y') : $blog->created_at->format('d M Y') }}
                            </small>
                            <h5 class="fw-bold mb-2">
                                <a href="{{ tfc_route('blogs.show', $blog->slug) }}" class="text-dark text-decoration-none">
                                    {{ $blog->title }}
                                </a>
                            </h5>
                            @if ($blog->excerpt)
                                <p class="text-muted small mb-4 flex-grow-1">{{ $blog->excerpt }}</p>
                            @endif
                            <div class="mt-auto">
                                <a href="{{ tfc_route('blogs.show', $blog->slug) }}" class="text-primary fw-semibold small text-decoration-none">
                                    Read Full Article <i class="bi bi-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <p class="text-muted">No published articles at this moment. Please check back soon.</p>
                </div>
            @endforelse
        </div>

        @if ($blogs->hasPages())
            <div class="d-flex justify-content-center mt-5">
                {{ $blogs->links() }}
            </div>
        @endif
    </div>
</section>
@endsection
