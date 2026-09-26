<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Role;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index()
    {
        $admins = Admin::with('roles')->latest()->paginate(20);

        return view('admin.admins.index', compact('admins'));
    }

    public function create()
    {
        $roles = Role::orderBy('name')->get();

        return view('admin.admins.create', compact('roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email',
            'phone' => 'nullable|string|max:255',
            'password' => 'required|min:8|confirmed',
            'status' => 'required|in:active,inactive,suspended',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        // Never allow creating an admin with a system role (super-admin etc.)
        // via bulk sync: those roles can only be assigned by the bootstrap
        // seeder to the explicitly configured super-admin.
        $assignable = $this->assignableRoleIds();
        $roles = array_values(array_intersect($validated['roles'], $assignable));

        if ($roles === []) {
            return back()
                ->withErrors(['roles' => 'At least one assignable role is required.'])
                ->withInput();
        }

        $admin = Admin::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'status' => $validated['status'],
        ]);

        $admin->roles()->sync($roles);

        return redirect()->route('admin.admins.index')->with('success', 'Admin created successfully.');
    }

    public function edit(Admin $admin)
    {
        $roles = Role::orderBy('name')->get();
        $adminRoleIds = $admin->roles->pluck('id')->toArray();

        return view('admin.admins.edit', compact('admin', 'roles', 'adminRoleIds'));
    }

    public function update(Request $request, Admin $admin)
    {
        // Admins holding a system role (super-admin, manager, support) can only
        // be re-configured by a super admin, so a delegated "manage-admins"
        // holder cannot downgrade or lock out a superior account.
        $holdsSystemRole = $admin->roles()->where('roles.is_system', true)->exists();

        if ($holdsSystemRole && ! (auth('admin')->user()?->is_super_admin)) {
            return back()->with('error', 'This admin holds a system role and can only be managed by a super admin.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email,'.$admin->id,
            'phone' => 'nullable|string|max:255',
            'password' => 'nullable|min:8|confirmed',
            'status' => 'required|in:active,inactive,suspended',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        // System roles are never mutable through this controller: keep whatever
        // system roles the admin already holds, only sync the assignable set.
        $assignable = $this->assignableRoleIds();
        $keptSystem = $admin->roles()->where('roles.is_system', true)->pluck('roles.id')->all();
        $roles = array_values(array_intersect($validated['roles'], $assignable));
        $saved = array_values(array_unique(array_merge($roles, $keptSystem)));

        $admin->update($data);
        $admin->roles()->sync($saved);

        return redirect()->route('admin.admins.index')->with('success', 'Admin updated successfully.');
    }

    public function destroy(Admin $admin)
    {
        if ($admin->is_super_admin) {
            return back()->with('error', 'Cannot delete a super admin.');
        }

        if ($admin->id === auth('admin')->id()) {
            return back()->with('error', 'You cannot delete your own account.');
        }

        $admin->roles()->detach();
        $admin->delete();

        return back()->with('success', 'Admin deleted successfully.');
    }

    /**
     * Role ids that may be granted via the admin UI: all non-system roles.
     * System roles (super-admin, manager, support) are bootstrap-only.
     *
     * @return array<int>
     */
    private function assignableRoleIds(): array
    {
        return Role::where('is_system', false)->pluck('id')->all();
    }
}
