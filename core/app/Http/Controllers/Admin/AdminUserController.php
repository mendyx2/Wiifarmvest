<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

/**
 * Staff → Admins (Pages/Admin/Staff/Admins.tsx): the people who can sign in
 * to the admin panel, their roles and whether they are active.
 *
 * Your own account is changed on the Profile page, not here. The first
 * admin account (id 1) is the owner's: the old list hid it, and it cannot
 * be changed, deactivated or deleted from this page.
 */
class AdminUserController extends Controller
{
    private const OWNER_ID = 1;

    public function index()
    {
        $pageTitle = 'Manage Admins';
        $me        = Auth::guard('admin')->id();

        return Inertia::render('Admin/Staff/Admins', [
            'admins' => Admin::with('roles')->orderBy('name')->get()->map(fn (Admin $a) => [
                'id'          => $a->id,
                'name'        => (string) $a->name,
                'username'    => (string) $a->username,
                'email'       => (string) $a->email,
                'active'      => $a->is_active !== false,
                'roles'       => $a->roles->pluck('id')->all(),
                'roleNames'   => $a->roles->pluck('name')->all(),
                'permissions' => $a->getAllPermissions()->pluck('name')->sort()->values()->all(),
                'me'          => $a->id === $me,
                'owner'       => $a->id === self::OWNER_ID,
            ]),
            'roles' => $this->roles()->map(fn (Role $r) => ['id' => $r->id, 'name' => (string) $r->name]),
            'urls'  => [
                'store'   => route('admin.admins.store'),
                'update'  => route('admin.admins.update', ':id'),
                'destroy' => route('admin.admins.destroy', ':id'),
                'status'  => route('admin.admins.toggle-status', ':id'),
                'profile' => route('admin.profile'),
                'roles'   => route('admin.roles.index'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    private function roles()
    {
        return Role::where('guard_name', 'admin')->orderBy('name')->get();
    }

    /** An admin this page may change: not yourself, not the owner. */
    private function editable(Admin $admin): ?string
    {
        if ($admin->id === Auth::guard('admin')->id()) {
            return 'Change your own account on the Profile page.';
        }
        if ($admin->id === self::OWNER_ID) {
            return 'The owner account cannot be changed here.';
        }
        return null;
    }

    private function rules(?Admin $admin): array
    {
        return [
            'name'     => 'required|string|max:255',
            'username' => ['required', 'string', 'min:3', 'max:40', 'regex:/^[A-Za-z0-9_.-]+$/', Rule::unique('admins', 'username')->ignore($admin?->id)],
            'email'    => ['nullable', 'email', 'max:255', Rule::unique('admins', 'email')->ignore($admin?->id)],
            'password' => [$admin ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'roles'    => 'required|array|min:1',
            'roles.*'  => ['integer', Rule::exists('roles', 'id')->where('guard_name', 'admin')],
        ];
    }

    public function store(Request $request)
    {
        $request->validate($this->rules(null));

        DB::transaction(function () use ($request) {
            $admin            = new Admin();
            $admin->name      = $request->name;
            $admin->username  = $request->username;
            $admin->email     = $request->email ?: null;
            $admin->password  = Hash::make($request->password);
            $admin->is_active = true;
            $admin->save();
            $admin->syncRoles(Role::whereIn('id', $request->roles)->where('guard_name', 'admin')->get());
        });

        $notify[] = ['success', 'Admin created successfully'];
        return back()->withNotify($notify);
    }

    public function update(Request $request, Admin $admin)
    {
        if ($error = $this->editable($admin)) {
            $notify[] = ['error', $error];
            return back()->withNotify($notify);
        }
        // The e-mail was required here although the form allowed none, and it was never saved
        $request->validate($this->rules($admin));

        DB::transaction(function () use ($request, $admin) {
            $admin->name     = $request->name;
            $admin->username = $request->username;
            $admin->email    = $request->email ?: null;
            if ($request->filled('password')) {
                $admin->password = Hash::make($request->password);
            }
            $admin->save();
            $admin->syncRoles(Role::whereIn('id', $request->roles)->where('guard_name', 'admin')->get());
        });

        $notify[] = ['success', 'Admin updated successfully'];
        return back()->withNotify($notify);
    }

    public function destroy(Admin $admin)
    {
        if ($error = $this->editable($admin)) {
            $notify[] = ['error', $error];
            return back()->withNotify($notify);
        }

        $admin->syncRoles([]);
        $admin->delete();

        $notify[] = ['success', 'Admin deleted successfully'];
        return back()->withNotify($notify);
    }

    /** Activate (state=enable) or deactivate (state=disable). A deactivated admin is signed out. */
    public function toggleStatus(Request $request, Admin $user)
    {
        $request->validate(['state' => 'required|in:enable,disable']);
        if ($error = $this->editable($user)) {
            $notify[] = ['error', $error];
            return back()->withNotify($notify);
        }

        $active = $request->state === 'enable';
        if (($user->is_active !== false) === $active) {
            $notify[] = ['info', $user->name . ' is already ' . ($active ? 'active' : 'inactive')];
            return back()->withNotify($notify);
        }
        $user->is_active = $active;
        $user->save();

        $notify[] = ['success', $user->name . ($active ? ' activated' : ' deactivated: they are signed out on their next page')];
        return back()->withNotify($notify);
    }
}
