<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RoleController extends Controller
{
    public function index()
    {
        $roles = Role::withCount(['permissions', 'admins'])->orderBy('name')->get();
        $permissions = Permission::query()->whereIn('id', $this->grantablePermissionIds())->orderBy('name')->get();

        return view('admin.roles.index', compact('roles', 'permissions'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|unique:roles,slug',
            'description' => 'nullable|string|max:500',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $validated['slug'] = $validated['slug'] ?? Str::slug($validated['name']);

        $permissionIds = $this->onlyGrantable($validated['permissions']);

        if ($permissionIds === []) {
            return back()
                ->withErrors(['permissions' => 'Choose at least one permission you are allowed to grant.'])
                ->withInput();
        }

        $role = Role::create([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'description' => $validated['description'] ?? null,
        ]);

        $role->permissions()->sync($permissionIds);

        return redirect()->route('admin.roles.index')->with('success', 'Role created successfully.');
    }

    public function update(Request $request, Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'System roles cannot be edited.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'nullable|unique:roles,slug,'.$role->id,
            'description' => 'nullable|string|max:500',
            'permissions' => 'required|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        $role->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'description' => $validated['description'] ?? null,
        ]);

        $grantable = $this->grantablePermissionIds();
        $kept = $role->permissions()->whereNotIn('permissions.id', $grantable)->pluck('permissions.id')->all();
        $role->permissions()->sync(array_values(array_unique(array_merge(
            $this->onlyGrantable($validated['permissions']),
            $kept,
        ))));

        return redirect()->route('admin.roles.index')->with('success', 'Role updated successfully.');
    }

    public function destroy(Role $role)
    {
        if ($role->is_system) {
            return back()->with('error', 'Cannot delete a system role.');
        }

        $role->admins()->detach();
        $role->permissions()->detach();
        $role->delete();

        return back()->with('success', 'Role deleted successfully.');
    }

    /**
     * Permissions this admin may attach to a role. Payment, settings, role,
     * and admin management stay with the super admin.
     *
     * @return array<int>
     */
    private function grantablePermissionIds(): array
    {
        $admin = auth('admin')->user();
        $restricted = ['manage-admins', 'manage-roles', 'manage-settings', 'manage-payments'];

        if ($admin?->is_super_admin) {
            return Permission::query()->pluck('id')->all();
        }

        $held = $admin
            ? $admin->roles()->with('permissions')->get()->flatMap->permissions->pluck('slug')->unique()->all()
            : [];

        return Permission::query()
            ->whereIn('slug', $held)
            ->whereNotIn('slug', $restricted)
            ->pluck('id')
            ->all();
    }

    /**
     * @param  array<int, mixed>  $ids
     * @return array<int>
     */
    private function onlyGrantable(array $ids): array
    {
        $allowed = $this->grantablePermissionIds();

        return array_values(array_intersect(array_map('intval', $ids), $allowed));
    }
}
