@extends('admin.layouts.app')

@section('title', 'FAQs')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">FAQs</h5>
        <small class="text-muted">{{ $faqs->total() }} FAQs total</small>
    </div>
    <a href="{{ route('admin.faqs.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add FAQ</a>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Category</th>
                    <th>Question</th>
                    <th>Answer</th>
                    <th>Sort</th>
                    <th>Status</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($faqs as $faq)
                    <tr>
                        <td><span class="badge badge-soft-primary">{{ $faq->category_group }}</span></td>
                        <td class="fw-semibold small">{{ $faq->question }}</td>
                        <td class="small text-muted text-truncate" style="max-width:320px;">{{ $faq->answer }}</td>
                        <td class="small">{{ $faq->sort_order }}</td>
                        <td>
                            <span class="badge {{ $faq->status === 'active' ? 'badge-soft-success' : 'badge-soft-secondary' }}">{{ ucfirst($faq->status) }}</span>
                        </td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.faqs.edit', $faq) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                <form method="POST" action="{{ route('admin.faqs.destroy', $faq) }}" onsubmit="return confirm('Delete this FAQ?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No FAQs found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $faqs->links() }}
    </div>
</div>
@endsection