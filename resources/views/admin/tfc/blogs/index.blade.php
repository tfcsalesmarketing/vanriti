@extends('admin.layouts.app')

@section('title', 'TFC Blogs & News Updates')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="fw-bold mb-1">TFC Blogs &amp; Corporate Announcements</h5>
        <p class="text-muted small mb-0">Publish corporate insights, distributor achievements, wellness tips, and direct-selling news.</p>
    </div>
    <a href="{{ route('admin.tfc.blogs.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>New Article
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th style="width: 70px;">Image</th>
                        <th>Article Title</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Published Date</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($blogs as $blog)
                        <tr>
                            <td>
                                @if ($blog->featured_image)
                                    <img src="{{ image_url($blog->featured_image) }}" alt="{{ $blog->title }}" class="rounded border" style="width: 50px; height: 50px; object-fit: cover;">
                                @else
                                    <div class="rounded border bg-light d-flex align-items-center justify-content-center text-muted" style="width: 50px; height: 50px;">
                                        <i class="bi bi-newspaper"></i>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <div class="fw-bold text-dark">{{ $blog->title }}</div>
                                @if ($blog->excerpt)
                                    <div class="text-muted small text-truncate" style="max-width: 350px;">{{ $blog->excerpt }}</div>
                                @endif
                            </td>
                            <td><code>/blogs/{{ $blog->slug }}</code></td>
                            <td>
                                <span class="badge {{ $blog->status === 'published' ? 'bg-success' : 'bg-secondary' }}">
                                    {{ ucfirst($blog->status) }}
                                </span>
                            </td>
                            <td class="small text-muted">{{ $blog->published_at ? $blog->published_at->format('d M Y, h:i A') : '—' }}</td>
                            <td class="text-end">
                                <a href="{{ tfc_route('blogs.show', $blog->slug) }}" target="_blank" class="btn btn-sm btn-light" title="View Article">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.tfc.blogs.edit', $blog) }}" class="btn btn-sm btn-outline-primary" title="Edit Article">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="{{ route('admin.tfc.blogs.destroy', $blog) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this article?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Article">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">No blog articles found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($blogs->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $blogs->links() }}
        </div>
    @endif
</div>
@endsection
