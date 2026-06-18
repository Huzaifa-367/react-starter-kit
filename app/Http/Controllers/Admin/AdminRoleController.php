<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class AdminRoleController extends Controller
{
    /**
     * Display a listing of the roles with permissions and user counts.
     */
    public function index(): Response
    {
        $roles = Role::with('permissions')->withCount(['permissions', 'users'])->get();
        $permissions = Permission::all(['id', 'name']);

        return Inertia::render('admin/roles/index', [
            'roles' => $roles,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created role.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
        ]);

        Role::create([
            'name' => $request->name,
            'guard_name' => 'web',
        ]);

        return back()->with('status', 'Role created successfully.');
    }

    /**
     * Update the specified role's details.
     */
    public function update(Request $request, Role $role): RedirectResponse
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name,' . $role->id],
        ]);

        $role->update([
            'name' => $request->name,
        ]);

        return back()->with('status', 'Role updated successfully.');
    }

    /**
     * Sync permissions to the specified role.
     */
    public function syncPermissions(Request $request, Role $role): RedirectResponse
    {
        $request->validate([
            'permissions' => ['required', 'array'],
            'permissions.*' => ['string', 'exists:permissions,name'],
        ]);

        $role->syncPermissions($request->permissions);

        // Reset Spatie cache
        app(\Spatie\Permission\PermissionRegistrar::class)->forgetCachedPermissions();

        return back()->with('status', 'Role permissions updated successfully.');
    }

    /**
     * Remove the specified role.
     */
    public function destroy(Role $role): RedirectResponse
    {
        if ($role->users()->exists()) {
            return back()->withErrors(['error' => 'You cannot delete a role that is assigned to users.']);
        }

        $role->delete();

        return back()->with('status', 'Role deleted successfully.');
    }
}
