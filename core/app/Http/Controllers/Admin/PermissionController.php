<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Permission;
use Illuminate\Validation\Rule;

class PermissionController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin.auth');
        $this->middleware('admin.permission:manage permissions');
    }

    public function index()
    {  
        $pageTitle='Permission';
        $permissions = Permission::where('guard_name', 'admin')
                                ->orderBy('group')
                                ->orderBy('name')
                                ->paginate(15);
        
        $groups = Permission::where('guard_name', 'admin')
                          ->distinct()
                          ->pluck('group')
                          ->toArray();

        return view('admin.permissions.index', compact('pageTitle', 'permissions', 'groups'));
    }

    public function create()
    {   
        $pageTitle='Create Permission';
        $groups = Permission::where('guard_name', 'admin')
                          ->distinct()
                          ->pluck('group')
                          ->toArray();
 
 
        return view('admin.permissions.create', compact('pageTitle', 'groups'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:permissions,name',
            'group' => 'required|string|max:255'
        ]);

        try {
            Permission::create([
                'name' => $validated['name'],
                'group' => $validated['group'],
                'guard_name' => 'admin'
            ]);

            return redirect()->route('admin.permissions.index')
                ->with('success', 'Permission created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error creating permission: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function edit(Permission $permission)
    {   
        $pageTitle='Edit Permission';
        if ($permission->guard_name !== 'admin') {
            abort(404);
        }

        $groups = Permission::where('guard_name', 'admin')
                          ->distinct()
                          ->pluck('group')
                          ->toArray();

        return view('admin.permissions.edit', compact('pageTitle', 'permission', 'groups'));
    }

    public function update(Request $request, Permission $permission)
    {
        if ($permission->guard_name !== 'admin') {
            abort(404);
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('permissions')->ignore($permission->id)
            ],
            'group' => 'required|string|max:255'
        ]);

        try {
            $permission->update($validated);

            return redirect()->route('admin.permissions.index')
                ->with('success', 'Permission updated successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error updating permission: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function destroy(Permission $permission)
    {
        if ($permission->guard_name !== 'admin') {
            abort(404);
        }

        if ($permission->roles()->count() > 0) {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'Cannot delete permission that is assigned to roles.');
        }

        try {
            $permission->delete();
            return redirect()->route('admin.permissions.index')
                ->with('success', 'Permission deleted successfully.');

        } catch (\Exception $e) {
            return redirect()->route('admin.permissions.index')
                ->with('error', 'Error deleting permission: ' . $e->getMessage());
        }
    }

    public function show(Permission $permission)
    {   
        $pageTitle='Show Permission';
        if ($permission->guard_name !== 'admin') {
            abort(404);
        }

        $permission->load('roles');
        return view('admin.permissions.show', compact('permission'));
    }

    public function bulkCreate()
    {   
        $pageTitle='Bult Create Permission';
        $groups = Permission::where('guard_name', 'admin')
                          ->distinct()
                          ->pluck('group')
                          ->toArray();

        return view('admin.permissions.bulk-create', compact('pageTitle', 'groups'));
    }

    public function bulkStore(Request $request)
    {
        $validated = $request->validate([
            'permissions' => 'required|array',
            'permissions.*.name' => 'required|string',
            'permissions.*.group' => 'required|string'
        ]);

        try {
            foreach ($validated['permissions'] as $permData) {
                Permission::firstOrCreate(
                    ['name' => $permData['name'], 'guard_name' => 'admin'],
                    ['group' => $permData['group']]
                );
            }

            return redirect()->route('admin.permissions.index')
                ->with('success', 'Permissions created successfully.');

        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', 'Error creating permissions: ' . $e->getMessage());
        }
    }
}