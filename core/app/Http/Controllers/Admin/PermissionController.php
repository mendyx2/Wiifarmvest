<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AdminAccessMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;

/**
 * Staff → Permissions (Pages/Admin/Staff/Permissions.tsx).
 *
 * A permission only opens pages when config/admin_access.php names it. The
 * page shows what each one opens; the ones the access map uses cannot be
 * renamed or deleted here, because that would silently lock those pages.
 */
class PermissionController extends Controller
{
    public function index()
    {
        $pageTitle = 'Admin Permissions';
        $roles     = Permission::where('guard_name', 'admin')->withCount('roles')->get()->keyBy('id');

        return Inertia::render('Admin/Staff/Permissions', [
            'groups' => collect(RoleController::permissionGroups())->map(fn ($g) => [
                'name'        => $g['name'],
                'permissions' => array_map(fn ($p) => $p + ['roles' => (int) $roles[$p['id']]->roles_count, 'locked' => (bool) $p['opens']], $g['permissions']),
            ])->all(),
            'urls' => [
                'store'   => route('admin.permissions.store'),
                'update'  => route('admin.permissions.update', ':id'),
                'destroy' => route('admin.permissions.destroy', ':id'),
                'roles'   => route('admin.roles.index'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    private function adminPermission(Permission $permission): Permission
    {
        abort_if($permission->guard_name !== 'admin', 404);
        return $permission;
    }

    /** Add one or several permissions (the single and "bulk" forms were two pages). */
    public function store(Request $request)
    {
        $request->validate([
            'permissions'         => 'required|array|min:1|max:50',
            'permissions.*.name'  => ['required', 'string', 'max:100', 'distinct:ignore_case', Rule::unique('permissions', 'name')->where('guard_name', 'admin')],
            'permissions.*.group' => 'required|string|max:60',
        ], ['permissions.*.name.unique' => ':input already exists.', 'permissions.*.name.distinct' => ':input is listed twice.']);

        DB::transaction(function () use ($request) {
            foreach ($request->permissions as $row) {
                Permission::create(['name' => trim($row['name']), 'group' => trim($row['group']), 'guard_name' => 'admin']);
            }
        });

        $count    = count($request->permissions);
        $notify[] = ['success', $count === 1 ? 'Permission created successfully' : $count . ' permissions created successfully'];
        return back()->withNotify($notify);
    }

    public function update(Request $request, Permission $permission)
    {
        $this->adminPermission($permission);
        $request->validate([
            'name'  => ['required', 'string', 'max:100', Rule::unique('permissions', 'name')->where('guard_name', 'admin')->ignore($permission->id)],
            'group' => 'required|string|max:60',
        ]);
        if ($request->name !== $permission->name && AdminAccessMiddleware::grantedBy($permission->name)) {
            return back()->withErrors(['name' => 'The access map uses this name; renaming it would lock the pages it opens.']);
        }

        $permission->name  = trim($request->name);
        $permission->group = trim($request->group);
        $permission->save();

        $notify[] = ['success', 'Permission updated successfully'];
        return back()->withNotify($notify);
    }

    public function destroy(Permission $permission)
    {
        $this->adminPermission($permission);
        if (AdminAccessMiddleware::grantedBy($permission->name)) {
            $notify[] = ['error', 'The access map uses this permission; deleting it would lock the pages it opens.'];
            return back()->withNotify($notify);
        }
        if ($permission->roles()->exists()) {
            $notify[] = ['error', 'This permission is in a role. Take it out of the role first.'];
            return back()->withNotify($notify);
        }

        $permission->delete();

        $notify[] = ['success', 'Permission deleted successfully'];
        return back()->withNotify($notify);
    }
}
