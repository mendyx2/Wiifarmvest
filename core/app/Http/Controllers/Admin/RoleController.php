<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RoleController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin.auth');
        $this->middleware('admin.permission:manage roles');
    }

    public function index()
    {   $pageTitle='Roles';
        $roles = Role::with('permissions')
                    ->where('guard_name', 'admin')
                    ->orderBy('name')
                    ->paginate(10);
        
        $permissions = Permission::where('guard_name', 'admin')
                                ->orderBy('group')
                                ->orderBy('name')
                                ->get()
                                ->groupBy('group');
        
        $permissionGroups = $permissions->keys();

        return view('admin.roles.index', compact('pageTitle', 'roles', 'permissions', 'permissionGroups'));
    }

    public function create()
    {
        $permissions = Permission::where('guard_name', 'admin')
                                ->orderBy('group')
                                ->orderBy('name')
                                ->get()
                                ->groupBy('group'); // This returns a Collection
        $pageTitle='Create Roles';
        
        return view('admin.roles.create', compact('permissions', 'pageTitle'));
    }

    public function edit(Role $role)
    {
        if ($role->guard_name !== 'admin') {
            abort(404);
        }
         $pageTitle='Edit Roles';
        $permissions = Permission::where('guard_name', 'admin')
                                ->orderBy('group')
                                ->orderBy('name')
                                ->get()
                                ->groupBy('group'); // This returns a Collection
        
        $rolePermissions = $role->permissions->pluck('id')->toArray();

        return view('admin.roles.edit', compact('role', 'permissions', 'rolePermissions', 'pageTitle'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'admin'
            ]);

            if (!empty($validated['permissions'])) {
                // FIX: Get permission objects from IDs and sync
                $permissions = Permission::whereIn('id', $validated['permissions'])
                                        ->where('guard_name', 'admin')
                                        ->get();
                $role->syncPermissions($permissions);
            }

            DB::commit();

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error creating role: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function store22(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:roles,name',
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        try {
            DB::beginTransaction();

            $role = Role::create([
                'name' => $validated['name'],
                'guard_name' => 'admin'
            ]);

            if (!empty($validated['permissions'])) {
                // FIX: Get permission objects from IDs and sync
                $permissions = Permission::whereIn('id', $validated['permissions'])
                                        ->where('guard_name', 'admin')
                                        ->get();
                $role->syncPermissions($permissions);
            }

            DB::commit();

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error creating role: ' . $e->getMessage())
                ->withInput();
        }
    }

    
    public function update(Request $request, Role $role)
    {
        if ($role->guard_name != 'admin') {
            abort(404);
        }

        if ($role->name == 'super-admin') {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Cannot modify super-admin role.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->ignore($role->id),
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id',
        ]);

        try {
            DB::beginTransaction();

            $role->update(['name' => $validated['name']]);

            if (!empty($validated['permissions'])) {
                $permissions = Permission::whereIn('id', $validated['permissions'])
                                        ->where('guard_name', 'admin')
                                        ->get();
                $role->syncPermissions($permissions);
            } else {
                $role->syncPermissions([]);
            }

            DB::commit();

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error updating role: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function update22(Request $request, Role $role)
    {
        if ($role->guard_name !== 'admin') {
            abort(404);
        }

        if ($role->name === 'super-admin') {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Cannot modify super-admin role.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('roles')->ignore($role->id)
            ],
            'permissions' => 'nullable|array',
            'permissions.*' => 'exists:permissions,id'
        ]);

        try {
            DB::beginTransaction();

            $role->update(['name' => $validated['name']]);

            if (isset($validated['permissions'])) {
                // FIX: Get permission objects from IDs and sync
                $permissions = Permission::whereIn('id', $validated['permissions'])
                                        ->where('guard_name', 'admin')
                                        ->get();
                $role->syncPermissions($permissions);
            } else {
                $role->syncPermissions([]);
            }

            DB::commit();

            return redirect()->route('admin.roles.index')
                ->with('success', 'Role updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error updating role: ' . $e->getMessage())
                ->withInput();
        }
    }
    public function destroy(Role $role)
    {
        if ($role->guard_name !== 'admin') {
            abort(404);
        }

        if (in_array($role->name, ['super-admin', 'admin'])) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Cannot delete system roles.');
        }

        if ($role->users()->count() > 0) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Cannot delete role that has users assigned.');
        }

        try {
            $role->delete();
            return redirect()->route('admin.roles.index')
                ->with('success', 'Role deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->route('admin.roles.index')
                ->with('error', 'Error deleting role: ' . $e->getMessage());
        }
    }

    public function show(Role $role)
    {   $pageTitle='Show Roles';
        if ($role->guard_name !== 'admin') {
            abort(404);
        }

        $role->load('permissions', 'users');
        return view('admin.roles.show', compact('pageTitle', 'role'));
    }
}