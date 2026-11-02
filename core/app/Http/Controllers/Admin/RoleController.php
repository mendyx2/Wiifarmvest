<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Middleware\AdminAccessMiddleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

/**
 * Staff → Roles (Pages/Admin/Staff/Roles.tsx): named sets of permissions
 * given to admins. Each permission shows what it opens in the panel.
 */
class RoleController extends Controller
{
    /** super-admin passes every access check; neither it nor the original "admin" role can be deleted. */
    private const SYSTEM = ['super-admin', 'admin'];

    public function index()
    {
        $pageTitle = 'Admin Roles';
        $roles     = Role::with('permissions')->where('guard_name', 'admin')->orderBy('name')->get();

        return Inertia::render('Admin/Staff/Roles', [
            'roles' => $roles->map(fn (Role $r) => [
                'id'          => $r->id,
                'name'        => (string) $r->name,
                'permissions' => $r->permissions->pluck('id')->all(),
                'admins'      => $r->users()->count(),
                'super'       => $r->name === 'super-admin',
                'system'      => in_array($r->name, self::SYSTEM, true),
            ]),
            'groups' => self::permissionGroups(),
            'urls'   => [
                'store'       => route('admin.roles.store'),
                'update'      => route('admin.roles.update', ':id'),
                'destroy'     => route('admin.roles.destroy', ':id'),
                'permissions' => route('admin.permissions.index'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /** The admin permissions by group, each with what it opens. */
    public static function permissionGroups(): array
    {
        return Permission::where('guard_name', 'admin')->orderBy('name')->get()
            ->groupBy(fn ($p) => (string) ($p->group ?: 'General'))
            ->sortKeys()
            ->map(fn ($perms, $group) => [
                'name'        => (string) $group,
                'permissions' => $perms->map(fn (Permission $p) => ['id' => $p->id, 'name' => (string) $p->name, 'opens' => AdminAccessMiddleware::grantedBy($p->name)])->values()->all(),
            ])->values()->all();
    }

    private function validated(Request $request, ?Role $role): void
    {
        $request->validate([
            'name'          => ['required', 'string', 'max:60', Rule::notIn(['super-admin']), Rule::unique('roles', 'name')->where('guard_name', 'admin')->ignore($role?->id)],
            'permissions'   => 'nullable|array',
            'permissions.*' => ['integer', Rule::exists('permissions', 'id')->where('guard_name', 'admin')],
        ], ['name.not_in' => 'This name is reserved.']);
    }

    private function adminRole(Role $role): Role
    {
        abort_if($role->guard_name !== 'admin', 404);
        return $role;
    }

    public function store(Request $request)
    {
        $this->validated($request, null);

        DB::transaction(function () use ($request) {
            $role = Role::create(['name' => $request->name, 'guard_name' => 'admin']);
            $role->syncPermissions(Permission::whereIn('id', (array) $request->permissions)->where('guard_name', 'admin')->get());
        });

        $notify[] = ['success', 'Role created successfully'];
        return back()->withNotify($notify);
    }

    public function update(Request $request, Role $role)
    {
        $this->adminRole($role);
        if ($role->name === 'super-admin') {
            $notify[] = ['error', 'The super-admin role has every permission and cannot be changed.'];
            return back()->withNotify($notify);
        }
        $this->validated($request, $role);

        DB::transaction(function () use ($request, $role) {
            $role->name = $request->name;
            $role->save();
            $role->syncPermissions(Permission::whereIn('id', (array) $request->permissions)->where('guard_name', 'admin')->get());
        });

        $notify[] = ['success', 'Role updated successfully'];
        return back()->withNotify($notify);
    }

    public function destroy(Role $role)
    {
        $this->adminRole($role);
        if (in_array($role->name, self::SYSTEM, true)) {
            $notify[] = ['error', 'Built-in roles cannot be deleted.'];
            return back()->withNotify($notify);
        }
        if ($role->users()->exists()) {
            $notify[] = ['error', 'This role is given to admins. Take it from them first.'];
            return back()->withNotify($notify);
        }

        $role->delete();

        $notify[] = ['success', 'Role deleted successfully'];
        return back()->withNotify($notify);
    }
}
