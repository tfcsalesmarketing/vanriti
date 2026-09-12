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

        $admin = Admin::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'password' => $validated['password'],
            'status' => $validated['status'],
        ]);

        $admin->roles()->sync($validated['roles']);

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
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:admins,email,' . $admin->id,
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

        if (!empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $admin->update($data);
        $admin->roles()->sync($validated['roles']);

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
}
