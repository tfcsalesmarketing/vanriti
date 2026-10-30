@extends('admin.layouts.app')

@section('title', 'TFC Pages & Statutory Documents')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="fw-bold mb-1">TFC Pages &amp; Statutory Policies</h5>
        <p class="text-muted small mb-0">Manage corporate information and mandatory direct-selling statutory disclosures.</p>
    </div>
    <a href="{{ route('admin.tfc.pages.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add New Page
    </a>
</div>

<div class="card border-0 shadow-sm">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Page Title</th>
                        <th>Slug</th>
                        <th>Status</th>
                        <th>Updated At</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($pages as $page)
                        <tr>
                            <td>
                                <div class="fw-bold text-dark">{{ $page->title }}</div>
                                @if ($page->excerpt)
                                    <div class="text-muted small text-truncate" style="max-width: 380px;">{{ $page->excerpt }}</div>
                                @endif
                            </td>
                            <td><code>/policy/{{ $page->slug }}</code></td>
                            <td>
                                @if ($page->status === 'published')
                                    <span class="badge bg-success-subtle text-success border border-success-subtle">Published</span>
                                @else
                                    <span class="badge bg-warning-subtle text-warning border border-warning-subtle">Draft (Safeguarded)</span>
                                @endif
                            </td>
                            <td class="small text-muted">{{ $page->updated_at->format('d M Y, h:i A') }}</td>
                            <td class="text-end">
                                <a href="{{ tfc_route('policy', $page->slug) }}" target="_blank" class="btn btn-sm btn-light" title="View Page">
                                    <i class="bi bi-eye"></i>
                                </a>
                                <a href="{{ route('admin.tfc.pages.edit', $page) }}" class="btn btn-sm btn-outline-primary" title="Edit Page">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <form action="{{ route('admin.tfc.pages.destroy', $page) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this page permanently?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete Page">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">No pages created yet.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    @if ($pages->hasPages())
        <div class="card-footer bg-white py-3">
            {{ $pages->links() }}
        </div>
    @endif
</div>
@endsection
