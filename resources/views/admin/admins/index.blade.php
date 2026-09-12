@extends('admin.layouts.app')

@section('title', 'Admins')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Admins</h5>
        <small class="text-muted">{{ $admins->total() }} admins total</small>
    </div>
    <a href="{{ route('admin.admins.create') }}" class="btn btn-sm btn-primary"><i class="bi bi-plus-lg me-1"></i>Add Admin</a>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Admin</th>
                    <th>Contact</th>
                    <th>Roles</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($admins as $admin)
                    <tr>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                <div class="rounded bg-light d-flex align-items-center justify-content-center fw-bold text-muted" style="width:40px;height:40px;">
                                    {{ strtoupper(substr($admin->name, 0, 1)) }}
                                </div>
                                <div>
                                    <div class="fw-semibold small">{{ $admin->name }}</div>
                                    @if ($admin->is_super_admin)
                                        <span class="badge badge-soft-warning">Super Admin</span>
                                    @endif
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small">{{ $admin->email }}</div>
                            <div class="small text-muted">{{ $admin->phone ?: '—' }}</div>
                        </td>
                        <td>
                            @forelse ($admin->roles as $role)
                                <span class="badge badge-soft-primary">{{ $role->name }}</span>
                            @empty
                                <span class="badge badge-soft-secondary">No roles</span>
                            @endforelse
                        </td>
                        <td>
                            <span class="badge {{ $admin->status === 'active' ? 'badge-soft-success' : ($admin->status === 'suspended' ? 'badge-soft-danger' : 'badge-soft-secondary') }}">{{ ucfirst($admin->status) }}</span>
                        </td>
                        <td class="small text-muted">{{ $admin->last_login_at ? $admin->last_login_at->format('d M Y, h:i A') : '—' }}</td>
                        <td class="text-end">
                            <div class="d-flex gap-1 justify-content-end">
                                <a href="{{ route('admin.admins.edit', $admin->id) }}" class="btn btn-sm btn-outline-primary" title="Edit"><i class="bi bi-pencil"></i></a>
                                @if (!$admin->is_super_admin && $admin->id !== auth('admin')->id())
                                    <form method="POST" action="{{ route('admin.admins.destroy', $admin->id) }}" onsubmit="return confirm('Delete this admin?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">No admins found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="card-footer">
        {{ $admins->links() }}
    </div>
</div>
@endsection