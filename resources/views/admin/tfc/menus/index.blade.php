@extends('admin.layouts.app')

@section('title', 'TFC Navigation Menus')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h5 class="fw-bold mb-1">TFC Navigation Menus</h5>
        <p class="text-muted small mb-0">Manage header navigation and multi-column footer link structures.</p>
    </div>
    <a href="{{ route('admin.tfc.menus.create') }}" class="btn btn-primary btn-sm">
        <i class="bi bi-plus-lg me-1"></i>Add Navigation Item
    </a>
</div>

<div class="row g-4">
    @php
        $locationNames = [
            'header' => 'Header Main Navigation',
            'footer_col1' => 'Footer Column 1 (Quick Links)',
            'footer_col2' => 'Footer Column 2 (Support & Grievance)',
            'footer_legal' => 'Footer Legal & Statutory Compliance',
        ];
    @endphp

    @foreach ($locationNames as $locKey => $locTitle)
        <div class="col-12 col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3 d-flex align-items-center justify-content-between">
                    <h6 class="fw-bold mb-0 text-dark">{{ $locTitle }}</h6>
                    <span class="badge bg-light text-dark border">{{ isset($menus[$locKey]) ? $menus[$locKey]->count() : 0 }} items</span>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Label</th>
                                    <th>Target URL</th>
                                    <th>Order</th>
                                    <th>Active</th>
                                    <th class="text-end">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($menus[$locKey] ?? [] as $item)
                                    <tr>
                                        <td>
                                            <div class="fw-semibold small text-dark">{{ $item->title }}</div>
                                            @if ($item->open_new_tab)
                                                <span class="badge bg-light text-muted border" style="font-size: 0.65rem;">New Tab</span>
                                            @endif
                                        </td>
                                        <td><code>{{ $item->url }}</code></td>
                                        <td>{{ $item->sort_order }}</td>
                                        <td>
                                            <span class="badge {{ $item->is_active ? 'bg-success' : 'bg-secondary' }}">
                                                {{ $item->is_active ? 'Yes' : 'No' }}
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('admin.tfc.menus.edit', $item) }}" class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bi bi-pencil-square"></i>
                                            </a>
                                            <form action="{{ route('admin.tfc.menus.destroy', $item) }}" method="POST" class="d-inline" onsubmit="return confirm('Delete this menu item?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-4 text-muted small">No menu links configured.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    @endforeach
</div>
@endsection
