<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
 
class AdminUserController extends Controller
{
    public function __construct()
    {
        $this->middleware('admin.auth');
        $this->middleware('admin.role:super-admin');
    }

    public function index()
    {
        $currentAdminId = Auth::guard('admin')->id();
        $pageTitle = 'Manage Admin ';
        $admins = Admin::with('roles')
                    ->where('id', '!=', $currentAdminId)
                    ->orderBy('name')
                    ->paginate(10); 
        
        $roles = Role::where('guard_name', 'admin')->get();
        
        return view('admin.admins.index', compact('admins', 'roles', 'pageTitle'));
    }

    public function create()
    {
        $pageTitle = 'Create Admin ';
        $roles = Role::where('guard_name', 'admin')->get();
        return view('admin.admins.create', compact('roles', 'pageTitle'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => 'required|string|unique:admins,username',
            'email' => 'nullable|email|unique:admins,email',
            'password' => 'required|string|min:8|confirmed',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id',
        ]);

        try {
            DB::beginTransaction();
            $admin =  new Admin();
            $admin->name        = $validated['name'];
            $admin->username    = $validated['username'];
            $admin->password    = Hash::make($validated['password']);
            $admin->email       = $request->email;
            $admin->is_active   = 1;
            $admin->save();


            // $admin = Admin::create([
            //     'name' => $validated['name'],
            //     'username' => $validated['username'],
            //     'email' => $validated['email'],
            //     'password' => Hash::make($validated['password']),
            //     'is_active' => 1,
            // ]);

            // FIX: Get role objects from IDs and sync
            $roles = Role::whereIn('id', $validated['roles'])->get();
            $admin->syncRoles($roles);

            DB::commit();

            return redirect()->route('admin.admins.index')
            ->with('success', 'Admin user created successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error creating admin user: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function show(Admin $admin)
    {
        $pageTitle = 'Manage Admin ';
        $admin->load('roles', 'permissions');
        //dd($user);

        return view('admin.admins.show', compact('admin', 'pageTitle'));
    }

    public function edit(Admin $admin)
    {
        $pageTitle = 'Edit Admin ';
        if ($admin->id == Auth::guard('admin')->id()) {
            return redirect()->route('admin.admins.index')
                ->with('error', 'You cannot edit your own account here.');
        }

        $roles = Role::where('guard_name', 'admin')->get();
        $adminRoles = $admin->roles->pluck('id')->toArray();
        
        return view('admin.admins.edit', compact('admin', 'roles', 'adminRoles', 'pageTitle'));
    }

    public function update(Request $request, Admin $admin)
    {
        if ($admin->id == Auth::guard('admin')->id()) {
            return redirect()->route('admin.admins.index')
                ->with('error', 'You cannot edit your own account here.');
        }

        //dd($admin);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'username' => [
                'required',
                'string',
                Rule::unique('admins')->ignore($admin->id)
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('admins')->ignore($admin->id)
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id'
            
        ]);
        //dd($request);

        try {
            DB::beginTransaction();
            $admin->name        = $validated['name'];
            $admin->username    = $validated['username'];
            //$admin->email       = $validated['email'];
            $admin->is_active   = $request->boolean('is_active');
            
            


            // $updateData = [
            //     'name' => $validated['name'],
            //     'username' => $validated['username'],
            //     'email' => $request->email,
            //     'is_active' => $request->boolean('is_active'),
            // ];

            if (!empty($validated['password'])) {
                $admin->password       =  Hash::make($validated['password']);
                //$updateData['password'] = Hash::make($validated['password']);
            }
            $admin->save();
            //$admin->update($updateData);

            // FIX: Get role objects from IDs and sync
            $roles = Role::whereIn('id', $validated['roles'])->get();
            $admin->syncRoles($roles);

            DB::commit();

            return redirect()->route('admin.admins.index')
                ->with('success', 'Admin user updated successfully.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()
                ->with('error', 'Error updating admin user: ' . $e->getMessage())
                ->withInput();
        }
    }

    public function update1(Request $request, Admin $user)
    {
        if ($user->id == Auth::guard('admin')->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot edit your own account here.');
        }
        ///dd($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => [
                'nullable',
                'email',
                Rule::unique('admins')->ignore($user->id)
            ],
            'password' => 'nullable|string|min:8|confirmed',
            'roles' => 'required|array',
            'roles.*' => 'exists:roles,id'
            
        ]);

        $updateData = [
            'name' => $validated['name'],
            
            'email' => $request->email,
            'is_active' => $request->boolean('is_active'),
        ];
        /* 
        'username' => [
                'required',
                'string',
                Rule::unique('admins')->ignore($user->id)
            ],
        'username' => $validated['username'],
        $request->boolean('is_active');
            //'is_active' => $validated['is_active'] ?? true,

        */
        if (!empty($validated['password'])) {
            $updateData['password'] = Hash::make($validated['password']);
        }

        $user->update($updateData);
        $user->syncRoles($validated['roles']);

        return redirect()->route('admin.admins.index')
            ->with('success', 'Admin user updated successfully.');
    }

    public function destroy(Admin $admin)
    {
        if ($admin->id == Auth::guard('admin')->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $admin->delete();

        return redirect()->route('admin.admins.index')
            ->with('success', 'Admin user deleted successfully.');
    }

    public function toggleStatus(Admin $user)
    {
        if ($user->id == Auth::guard('admin')->id()) {
            return redirect()->route('admin.users.index')
                ->with('error', 'You cannot change your own account status.');
        }

        $user->update(['is_active' => !$user->is_active]);

        $status = $user->is_active ? 'activated' : 'deactivated';
        return redirect()->route('admin.admins.index')
            ->with('success', "Admin user {$status} successfully.");
    }
}