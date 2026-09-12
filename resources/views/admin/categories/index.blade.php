@extends('admin.layouts.app')

@section('title', 'Categories')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Categories</h5>
        <small class="text-muted">{{ $categories->count() }} categories total</small>
    </div>
    <a href="{{ route('admin.categories.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Category</a>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Slug</th>
                    <th class="text-center">Products</th>
                    <th>Status</th>
                    <th class="text-center">Sort</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $roots = $categories->whereNull('parent_id');
                @endphp
                @forelse ($roots as $cat)
                    <tr>
                        <td class="fw-semibold">{{ $cat->name }}</td>
                        <td class="small text-muted">{{ $cat->slug }}</td>
                        <td class="text-center">{{ $cat->products_count }}</td>
                        <td>
                            <span class="badge {{ $cat->status === 'active' ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ ucfirst($cat->status) }}</span>
                        </td>
                        <td class="text-center">{{ $cat->sort_order }}</td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.categories.edit', $cat->slug) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('admin.categories.destroy', $cat->slug) }}" onsubmit="return confirm('Delete this category?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @php
                        $children = $categories->where('parent_id', $cat->id);
                    @endphp
                    @foreach ($children as $child)
                        <tr>
                            <td class="fw-semibold" style="padding-left: 2.5rem;"><i class="bi bi-arrow-return-right me-1 text-muted"></i>{{ $child->name }}</td>
                            <td class="small text-muted">{{ $child->slug }}</td>
                            <td class="text-center">{{ $child->products_count }}</td>
                            <td>
                                <span class="badge {{ $child->status === 'active' ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ ucfirst($child->status) }}</span>
                            </td>
                            <td class="text-center">{{ $child->sort_order }}</td>
                            <td class="text-end">
                                <div class="d-flex gap-1 justify-content-end">
                                    <a href="{{ route('admin.categories.edit', $child->slug) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                    <form method="POST" action="{{ route('admin.categories.destroy', $child->slug) }}" onsubmit="return confirm('Delete this category?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @php
                            $grandchildren = $categories->where('parent_id', $child->id);
                        @endphp
                        @foreach ($grandchildren as $gc)
                            <tr>
                                <td class="fw-semibold" style="padding-left: 4.5rem;"><i class="bi bi-arrow-return-right me-1 text-muted"></i><i class="bi bi-arrow-return-right me-1 text-muted"></i>{{ $gc->name }}</td>
                                <td class="small text-muted">{{ $gc->slug }}</td>
                                <td class="text-center">{{ $gc->products_count }}</td>
                                <td>
                                    <span class="badge {{ $gc->status === 'active' ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ ucfirst($gc->status) }}</span>
                                </td>
                                <td class="text-center">{{ $gc->sort_order }}</td>
                                <td class="text-end">
                                    <div class="d-flex gap-1 justify-content-end">
                                        <a href="{{ route('admin.categories.edit', $gc->slug) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                        <form method="POST" action="{{ route('admin.categories.destroy', $gc->slug) }}" onsubmit="return confirm('Delete this category?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    @endforeach
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No categories found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
