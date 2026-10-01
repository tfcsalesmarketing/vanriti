@extends('admin.layouts.app')

@section('title', 'Contact Inquiries')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Contact Inquiries</h5>
        <small class="text-muted">{{ $messages->total() }} inquiries total</small>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Email</th>
                    <th>Mobile</th>
                    <th>Subject</th>
                    <th>Status</th>
                    <th>Date</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($messages as $message)
                    <tr>
                        <td class="small fw-semibold">{{ $message->name }}</td>
                        <td class="small">{{ $message->email }}</td>
                        <td class="small">{{ $message->mobile ?: '—' }}</td>
                        <td class="small text-muted text-truncate" style="max-width:200px;">{{ $message->subject ?: '—' }}</td>
                        <td>
                            <span class="badge {{ $message->status === 'new' ? 'bg-warning' : 'bg-success' }}">{{ ucfirst($message->status) }}</span>
                        </td>
                        <td class="small text-muted">{{ $message->created_at->format('d M Y H:i') }}</td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.contact-messages.show', $message) }}" class="btn btn-sm btn-outline-primary" title="View"><i class="bi bi-eye"></i></a>
                                <form method="POST" action="{{ route('admin.contact-messages.destroy', $message) }}" data-confirm="Delete this inquiry?">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">No contact inquiries found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $messages->links() }}
    </div>
</div>
@endsection
