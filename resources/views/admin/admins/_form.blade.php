@php
    $admin = $admin ?? null;
@endphp

<div class="row g-3">
    <div class="col-md-8">
        <div class="card mb-3">
            <div class="card-header fw-semibold">Account Information</div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $admin->name ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control form-control-sm" value="{{ old('email', $admin->email ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Phone</label>
                        <input type="text" name="phone" class="form-control form-control-sm" value="{{ old('phone', $admin->phone ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Status <span class="text-danger">*</span></label>
                        <select name="status" class="form-select form-select-sm" required>
                            <option value="active" {{ old('status', $admin->status ?? 'active') === 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ old('status', $admin->status ?? '') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            <option value="suspended" {{ old('status', $admin->status ?? '') === 'suspended' ? 'selected' : '' }}>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Password @if(!$admin)<span class="text-danger">*</span>@endif</label>
                        <input type="password" name="password" class="form-control form-control-sm" @if(!$admin) required @endif>
                        @if($admin)
                            <div class="form-text">Leave blank to keep the current password.</div>
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Confirm Password</label>
                        <input type="password" name="password_confirmation" class="form-control form-control-sm">
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header fw-semibold">Roles <span class="text-danger">*</span></div>
            <div class="card-body">
                <div class="row">
                    @forelse ($roles as $role)
                        <div class="col-md-6 col-lg-4 mb-2">
                            <div class="form-check">
                                <input type="checkbox" class="form-check-input" name="roles[]" value="{{ $role->id }}" id="role_{{ $role->id }}"
                                    {{ in_array($role->id, old('roles', $adminRoleIds ?? [])) ? 'checked' : '' }}>
                                <label class="form-check-label small" for="role_{{ $role->id }}">{{ $role->name }}</label>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-muted small">No roles available.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<div class="d-flex gap-2 mt-3">
    <button type="submit" class="btn btn-sm btn-primary"><i class="bi bi-check-lg me-1"></i>{{ $admin ? 'Save Admin' : 'Create Admin' }}</button>
    <a href="{{ route('admin.admins.index') }}" class="btn btn-sm btn-outline-secondary">Cancel</a>
</div>