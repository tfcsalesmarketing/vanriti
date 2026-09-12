@extends('admin.layouts.app')

@section('title', 'Blogs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Blogs</h5>
        <small class="text-muted">{{ $blogs->total() }} blog posts total</small>
    </div>
    <a href="{{ route('admin.blogs.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Blog</a>
</div>

<div class="card mb-3">
    <div class="card-body py-2">
        <form method="GET" action="{{ route('admin.blogs.index') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <input type="text" name="q" class="form-control form-control-sm" placeholder="Search by title..." value="{{ request('q') }}">
            </div>
            <div class="col-md-1">
                <button type="submit" class="btn btn-sm btn-dark w-100"><i class="bi bi-search"></i></button>
            </div>
            <div class="col-md-2">
                <a href="{{ route('admin.blogs.index') }}" class="btn btn-sm btn-outline-secondary w-100">Clear</a>
            </div>
        </form>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Featured</th>
                    <th>Title / Slug</th>
                    <th>Category</th>
                    <th>Author</th>
                    <th>Status</th>
                    <th>Published</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($blogs as $blog)
                    <tr>
                        <td>
                            @if ($blog->featured_image)
                                <img src="{{ image_url($blog->featured_image) }}" alt="" class="img-thumb">
                            @else
                                <div class="rounded bg-light d-flex align-items-center justify-content-center" style="width:48px;height:48px;">
                                    <i class="bi bi-journal-text text-muted"></i>
                                </div>
                            @endif
                        </td>
                        <td>
                            <div class="fw-semibold small">{{ $blog->title }}</div>
                            <div class="text-muted small">{{ $blog->slug }}</div>
                        </td>
                        <td class="small">{{ $blog->category->name ?? '—' }}</td>
                        <td class="small">{{ $blog->author->name ?? '—' }}</td>
                        <td>
                            <span class="badge {{ $blog->status === 'published' ? 'badge-soft-success' : ($blog->status === 'archived' ? 'badge-soft-secondary' : 'badge-soft-warning') }}">{{ ucfirst($blog->status) }}</span>
                        </td>
                        <td class="small text-muted">{{ $blog->published_at?->format('d M Y') ?: '—' }}</td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.blogs.edit', $blog) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('admin.blogs.destroy', $blog) }}" onsubmit="return confirm('Delete this blog post?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No blog posts found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $blogs->links() }}
    </div>
</div>
@endsection