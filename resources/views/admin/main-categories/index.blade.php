@extends('admin.layouts.app')

@section('title', 'Main Categories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Main Categories</h5>
        <small class="text-muted">{{ $categories->count() }} main categories</small>
    </div>
    <a href="{{ route('admin.main-categories.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Main Category</a>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th class="text-center">Sub-categories</th>
                    <th class="text-center">Products</th>
                    <th>Status</th>
                    <th class="text-center">Sort</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($categories as $cat)
                    <tr>
                        <td class="fw-semibold">{{ $cat->name }}</td>
                        <td class="small text-muted">{{ $cat->slug }}</td>
                        <td class="text-center">{{ $cat->children_count }}</td>
                        <td class="text-center">{{ $cat->products_count }}</td>
                        <td>
                            <span class="badge {{ $cat->status === 'active' ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ ucfirst($cat->status) }}</span>
                        </td>
                        <td class="text-center">{{ $cat->sort_order }}</td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.main-categories.edit', $cat->slug) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('admin.main-categories.destroy', $cat->slug) }}" onsubmit="return confirm('Delete this main category?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No main categories found. Add your first main category.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
