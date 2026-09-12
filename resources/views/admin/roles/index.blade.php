@extends('admin.layouts.app')

@section('title', 'Roles & Permissions')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Roles & Permissions</h5>
        <small class="text-muted">{{ $roles->count() }} roles, {{ $permissions->count() }} permissions</small>
    </div>
    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="collapse" data-bs-target="#createRoleForm"><i class="bi bi-plus-lg me-1"></i>Create Role</button>
</div>

<div class="collapse mb-3" id="createRoleForm">
    <div class="card">
        <div class="card-header fw-semibold">Create Role</div>
        <div class="card-body">
            <form method="POST" action="{{ route('admin.roles.store') }}">
                @csrf
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Slug</label>
                        <input type="text" name="slug" class="form-control form-control-sm" value="{{ old('slug') }}">
                        <div class="form-text">Leave blank to auto-generate from name.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control form-control-sm" value="{{ old('description') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Permissions <span class="text-danger">*</span></label>
                        <div class="row">
                            @foreach ($permissions as $permission)
                                <div class="col-md-4 col-lg-3 mb-2">
                                    <div class="form-check">
                                        <input type="checkbox" class="form-check-input" name="permissions[]" value="{{ $permission->id }}" id="create_perm_{{ $permission->id }}">
                                        <label class="form-check-label small" for="create_perm_{{ $permission->id }}">{{ $permission->name }}</label>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>Create Role</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="card table-card">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead>
                <tr>
                    <th>Role</th>
                    <th>Description</th>
                    <th>Permissions</th>
                    <th>Admins</th>
                    <th class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                    <tr>
                        <td>
                            <div class="fw-semibold small">{{ $role->name }}</div>
                            <div class="small text-muted">{{ $role->slug }}</div>
                            @if ($role->is_system)
                                <span class="badge badge-soft-secondary">System</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $role->description ?: '—' }}</td>
                        <td><span class="badge badge-soft-primary">{{ $role->permissions_count }}</span></td>
                        <td><span class="badge badge-soft-info">{{ $role->admins_count }}</span></td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse" data-bs-target="#editRole_{{ $role->id }}"><i class="bi bi-pencil"></i></button>
                            @if (!$role->is_system)
                                <form method="POST" action="{{ route('admin.roles.destroy', $role->id) }}" class="d-inline" onsubmit="return confirm('Delete this role?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger" title="Delete"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    <tr class="collapse-row">
                        <td colspan="5" class="p-0">
                            <div class="collapse" id="editRole_{{ $role->id }}">
                                <div class="p-3 border-top">
                                    <form method="POST" action="{{ route('admin.roles.update', $role->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="row g-3">
                                            <div class="col-md-4">
                                                <label class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $role->name) }}" required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small fw-semibold">Slug</label>
                                                <input type="text" name="slug" class="form-control form-control-sm" value="{{ old('slug', $role->slug) }}">
                                                <div class="form-text">Leave blank to auto-generate from name.</div>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small fw-semibold">Description</label>
                                                <input type="text" name="description" class="form-control form-control-sm" value="{{ old('description', $role->description) }}">
                                            </div>
                                            <div class="col-12">
                                                <label class="form-label small fw-semibold">Permissions</label>
                                                <div class="row">
                                                    @foreach ($permissions as $permission)
                                                        <div class="col-md-4 col-lg-3 mb-2">
                                                            <div class="form-check">
                                                                <input type="checkbox" class="form-check-input" name="permissions[]" value="{{ $permission->id }}" id="edit_{{ $role->id }}_perm_{{ $permission->id }}"
                                                                    {{ $role->permissions->contains('id', $permission->id) ? 'checked' : '' }}>
                                                                <label class="form-check-label small" for="edit_{{ $role->id }}_perm_{{ $permission->id }}">{{ $permission->name }}</label>
                                                            </div>
                                                        </div>
                                                    @endforeach
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>Update Role</button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center py-4 text-muted">No roles found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection