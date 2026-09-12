@extends('admin.layouts.app')

@section('title', 'Newsletter')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Newsletter Subscribers</h5>
        <small class="text-muted">{{ $newsletters->total() }} subscribers total</small>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Email</th>
                    <th>Name</th>
                    <th>Status</th>
                    <th>Subscribed At</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($newsletters as $newsletter)
                    <tr>
                        <td class="small fw-semibold">{{ $newsletter->email }}</td>
                        <td class="small">{{ $newsletter->name ?: '—' }}</td>
                        <td>
                            <span class="badge {{ $newsletter->is_subscribed ? 'bg-success' : 'bg-secondary' }}">{{ $newsletter->is_subscribed ? 'Subscribed' : 'Unsubscribed' }}</span>
                        </td>
                        <td class="small text-muted">{{ $newsletter->subscribed_at?->format('d M Y H:i') ?: '—' }}</td>
                        <td class="text-end">
                            <form method="POST" action="{{ route('admin.newsletters.destroy', $newsletter->id) }}" onsubmit="return confirm('Delete this subscription?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No newsletter subscribers found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $newsletters->links() }}
    </div>
</div>
@endsection
