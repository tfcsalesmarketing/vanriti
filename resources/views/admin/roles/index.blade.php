@extends('admin.layouts.app')

@section('title', 'Roles & Permissions')

@php
    $groupMetadata = [
        'whatsapp' => ['title' => 'WhatsApp CRM & Conversations', 'icon' => 'bi-whatsapp', 'color' => '#25D366'],
        'catalogue' => ['title' => 'Catalogue & Stock Management', 'icon' => 'bi-box-seam', 'color' => '#059669'],
        'orders' => ['title' => 'Orders, Shipping & Logistics', 'icon' => 'bi-cart-check', 'color' => '#2563eb'],
        'customers' => ['title' => 'Customers, Growth & Marketing', 'icon' => 'bi-people', 'color' => '#7c3aed'],
        'content' => ['title' => 'Storefront & Content CMS', 'icon' => 'bi-window-stack', 'color' => '#d97706'],
        'system' => ['title' => 'System, Admins & Settings', 'icon' => 'bi-gear', 'color' => '#475569'],
        'general' => ['title' => 'Other Permissions', 'icon' => 'bi-shield-check', 'color' => '#64748b'],
    ];

    $groupedPermissions = $permissions->groupBy(function($item) {
        return $item->group ?: 'general';
    });
@endphp

@section('content')
<div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
        <h5 class="mb-0 fw-bold">Roles &amp; Permissions</h5>
        <small class="text-muted">{{ $roles->count() }} roles configured, {{ $permissions->count() }} granular permissions available</small>
    </div>
    <button type="button" class="btn btn-sm btn-primary d-inline-flex align-items-center gap-1.5 shadow-sm" data-bs-toggle="collapse" data-bs-target="#createRoleForm">
        <i class="bi bi-plus-lg"></i><span>Create Custom Role</span>
    </button>
</div>

{{-- Create Role Collapse Form --}}
<div class="collapse mb-4" id="createRoleForm">
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white py-3 border-bottom d-flex align-items-center justify-content-between">
            <span class="fw-bold"><i class="bi bi-shield-plus me-1.5 text-primary"></i>Create New Role</span>
            <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#createRoleForm" aria-label="Close"></button>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="{{ route('admin.roles.store') }}">
                @csrf
                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Role Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control form-control-sm" placeholder="e.g. WhatsApp Live Chat Agent" value="{{ old('name') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Role Slug</label>
                        <input type="text" name="slug" class="form-control form-control-sm" placeholder="e.g. whatsapp-chat-agent" value="{{ old('slug') }}">
                        <div class="form-text" style="font-size: 0.72rem;">Leave blank to auto-generate from name.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Description</label>
                        <input type="text" name="description" class="form-control form-control-sm" placeholder="Purpose or department of this role" value="{{ old('description') }}">
                    </div>
                </div>

                {{-- Granular Permissions Picker by Category --}}
                <div class="mb-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <label class="form-label small fw-bold mb-0 text-dark">Assign Permissions <span class="text-danger">*</span></label>
                        <span class="text-muted small" style="font-size: 0.75rem;">Select individual scopes or use group buttons</span>
                    </div>

                    <div class="row g-3">
                        @foreach ($groupedPermissions as $grpKey => $perms)
                            @php
                                $meta = $groupMetadata[$grpKey] ?? ['title' => ucfirst($grpKey), 'icon' => 'bi-folder', 'color' => '#0d6efd'];
                            @endphp
                            <div class="col-12 col-xl-6">
                                <div class="card h-100 border rounded-3 bg-light-subtle">
                                    <div class="card-header bg-white py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi {{ $meta['icon'] }} fs-6" style="color: {{ $meta['color'] }};"></i>
                                            <span class="fw-semibold small">{{ $meta['title'] }}</span>
                                            <span class="badge bg-secondary-subtle text-secondary rounded-pill" style="font-size: 0.65rem;">{{ $perms->count() }}</span>
                                        </div>
                                        <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size: 0.72rem;" onclick="toggleGroupCheckboxes(this, 'create_grp_{{ $grpKey }}')">
                                            Select All
                                        </button>
                                    </div>
                                    <div class="card-body p-3">
                                        <div class="row g-2">
                                            @foreach ($perms as $permission)
                                                @php
                                                    $isView = str_starts_with($permission->slug, 'view-');
                                                @endphp
                                                <div class="col-12">
                                                    <div class="form-check p-2 rounded-2 bg-white border border-light-subtle d-flex align-items-start gap-2 m-0">
                                                        <input type="checkbox" class="form-check-input mt-1 create_grp_{{ $grpKey }}" name="permissions[]" value="{{ $permission->id }}" id="create_perm_{{ $permission->id }}">
                                                        <label class="form-check-label w-100 cursor-pointer" for="create_perm_{{ $permission->id }}">
                                                            <div class="d-flex align-items-center justify-content-between gap-1">
                                                                <span class="fw-semibold small text-dark">{{ $permission->name }}</span>
                                                                @if ($isView)
                                                                    <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 0.65rem;">Read-Only</span>
                                                                @else
                                                                    <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">Full Access</span>
                                                                @endif
                                                            </div>
                                                            <code class="d-block text-muted" style="font-size: 0.68rem;">{{ $permission->slug }}</code>
                                                            @if ($permission->description)
                                                                <small class="text-muted d-block" style="font-size: 0.72rem; line-height: 1.25;">{{ $permission->description }}</small>
                                                            @endif
                                                        </label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <div class="mt-4 pt-2 border-top text-end">
                    <button type="button" class="btn btn-sm btn-light border px-3 me-2" data-bs-toggle="collapse" data-bs-target="#createRoleForm">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Save Role</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Roles Table --}}
<div class="card table-card border-0 shadow-sm rounded-4">
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th style="width: 25%;">Role Name &amp; Slug</th>
                    <th style="width: 35%;">Description</th>
                    <th style="width: 15%;">Permissions Assigned</th>
                    <th style="width: 10%;">Admins</th>
                    <th style="width: 15%;" class="text-end">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($roles as $role)
                    <tr>
                        <td>
                            <div class="fw-semibold text-dark">{{ $role->name }}</div>
                            <code class="small text-muted" style="font-size: 0.72rem;">{{ $role->slug }}</code>
                            @if ($role->is_system)
                                <span class="badge bg-secondary-subtle text-secondary ms-1" style="font-size: 0.68rem;">System Role</span>
                            @elseif ($role->slug === 'whatsapp-agent')
                                <span class="badge bg-success-subtle text-success ms-1" style="font-size: 0.68rem;">WhatsApp Role</span>
                            @endif
                        </td>
                        <td class="small text-muted">{{ $role->description ?: '—' }}</td>
                        <td>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1 rounded-pill">
                                {{ $role->permissions_count }} permissions
                            </span>
                        </td>
                        <td>
                            <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1 rounded-pill">
                                <i class="bi bi-person me-0.5"></i>{{ $role->admins_count }}
                            </span>
                        </td>
                        <td class="text-end">
                            <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-2.5 py-1" data-bs-toggle="collapse" data-bs-target="#editRole_{{ $role->id }}" title="Edit Permissions">
                                <i class="bi bi-pencil me-1"></i><span class="small">Configure</span>
                            </button>
                            @if (!$role->is_system)
                                <form method="POST" action="{{ route('admin.roles.destroy', $role->id) }}" class="d-inline ms-1" data-confirm="Are you sure you want to delete this role? Admins assigned to this role will lose its permissions.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger rounded-circle p-1" style="width: 28px; height: 28px;" title="Delete Role"><i class="bi bi-trash"></i></button>
                                </form>
                            @endif
                        </td>
                    </tr>
                    <tr class="collapse-row">
                        <td colspan="5" class="p-0 border-0">
                            <div class="collapse" id="editRole_{{ $role->id }}">
                                <div class="p-4 bg-light border-top border-bottom">
                                    <div class="d-flex align-items-center justify-content-between mb-3">
                                        <div class="fw-bold text-dark"><i class="bi bi-sliders me-1.5 text-primary"></i>Modify Role Permissions: {{ $role->name }}</div>
                                        <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#editRole_{{ $role->id }}"></button>
                                    </div>
                                    <form method="POST" action="{{ route('admin.roles.update', $role->id) }}">
                                        @csrf
                                        @method('PUT')
                                        <div class="row g-3 mb-4">
                                            <div class="col-md-4">
                                                <label class="form-label small fw-semibold">Role Name <span class="text-danger">*</span></label>
                                                <input type="text" name="name" class="form-control form-control-sm" value="{{ old('name', $role->name) }}" {{ $role->is_system ? 'readonly' : '' }} required>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small fw-semibold">Slug</label>
                                                <input type="text" name="slug" class="form-control form-control-sm" value="{{ old('slug', $role->slug) }}" {{ $role->is_system ? 'readonly' : '' }}>
                                            </div>
                                            <div class="col-md-4">
                                                <label class="form-label small fw-semibold">Description</label>
                                                <input type="text" name="description" class="form-control form-control-sm" value="{{ old('description', $role->description) }}">
                                            </div>
                                        </div>

                                        {{-- Edit Granular Permissions by Category --}}
                                        <div class="row g-3">
                                            @foreach ($groupedPermissions as $grpKey => $perms)
                                                @php
                                                    $meta = $groupMetadata[$grpKey] ?? ['title' => ucfirst($grpKey), 'icon' => 'bi-folder', 'color' => '#0d6efd'];
                                                @endphp
                                                <div class="col-12 col-xl-6">
                                                    <div class="card h-100 border rounded-3 bg-white">
                                                        <div class="card-header bg-light-subtle py-2 px-3 border-bottom d-flex align-items-center justify-content-between">
                                                            <div class="d-flex align-items-center gap-2">
                                                                <i class="bi {{ $meta['icon'] }} fs-6" style="color: {{ $meta['color'] }};"></i>
                                                                <span class="fw-semibold small">{{ $meta['title'] }}</span>
                                                                <span class="badge bg-secondary-subtle text-secondary rounded-pill" style="font-size: 0.65rem;">{{ $perms->count() }}</span>
                                                            </div>
                                                            <button type="button" class="btn btn-xs btn-outline-secondary py-0 px-2" style="font-size: 0.72rem;" onclick="toggleGroupCheckboxes(this, 'edit_{{ $role->id }}_grp_{{ $grpKey }}')">
                                                                Select All
                                                            </button>
                                                        </div>
                                                        <div class="card-body p-3">
                                                            <div class="row g-2">
                                                                @foreach ($perms as $permission)
                                                                    @php
                                                                        $isView = str_starts_with($permission->slug, 'view-');
                                                                        $checked = $role->permissions->contains('id', $permission->id);
                                                                    @endphp
                                                                    <div class="col-12">
                                                                        <div class="form-check p-2 rounded-2 bg-light-subtle border d-flex align-items-start gap-2 m-0">
                                                                            <input type="checkbox" class="form-check-input mt-1 edit_{{ $role->id }}_grp_{{ $grpKey }}" name="permissions[]" value="{{ $permission->id }}" id="edit_{{ $role->id }}_perm_{{ $permission->id }}"
                                                                                {{ $checked ? 'checked' : '' }}>
                                                                            <label class="form-check-label w-100 cursor-pointer" for="edit_{{ $role->id }}_perm_{{ $permission->id }}">
                                                                                <div class="d-flex align-items-center justify-content-between gap-1">
                                                                                    <span class="fw-semibold small text-dark">{{ $permission->name }}</span>
                                                                                    @if ($isView)
                                                                                        <span class="badge bg-info-subtle text-info border border-info-subtle" style="font-size: 0.65rem;">Read-Only</span>
                                                                                    @else
                                                                                        <span class="badge bg-success-subtle text-success border border-success-subtle" style="font-size: 0.65rem;">Full Access</span>
                                                                                    @endif
                                                                                </div>
                                                                                <code class="d-block text-muted" style="font-size: 0.68rem;">{{ $permission->slug }}</code>
                                                                                @if ($permission->description)
                                                                                    <small class="text-muted d-block" style="font-size: 0.72rem; line-height: 1.25;">{{ $permission->description }}</small>
                                                                                @endif
                                                                            </label>
                                                                        </div>
                                                                    </div>
                                                                @endforeach
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>

                                        <div class="mt-4 pt-2 border-top text-end">
                                            <button type="button" class="btn btn-sm btn-light border px-3 me-2" data-bs-toggle="collapse" data-bs-target="#editRole_{{ $role->id }}">Cancel</button>
                                            <button type="submit" class="btn btn-sm btn-primary px-4"><i class="bi bi-check-lg me-1"></i>Update Permissions</button>
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

@push('scripts')
<script nonce="{{ $cspNonce ?? '' }}">
function toggleGroupCheckboxes(btn, className) {
    const boxes = document.querySelectorAll('.' + className);
    if (!boxes.length) return;
    
    // Check if at least one is unchecked
    let hasUnchecked = Array.from(boxes).some(b => !b.checked);
    boxes.forEach(b => b.checked = hasUnchecked);
    btn.textContent = hasUnchecked ? 'Deselect All' : 'Select All';
}
</script>
@endpush