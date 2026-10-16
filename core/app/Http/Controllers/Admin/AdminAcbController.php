<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AcbUser;
use App\Models\User;
use Illuminate\Http\Request;

class AdminAcbController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle  = 'ACB Members';
        $search     = $request->search;

        $acbMembers = AcbUser::with('user')
            ->when($search, function ($q) use ($search) {
                $q->whereHas('user', function ($u) use ($search) {
                    $u->where('username', 'like', "%{$search}%")
                      ->orWhere('firstname', 'like', "%{$search}%")
                      ->orWhere('lastname', 'like', "%{$search}%")
                      ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(20)
            ->withQueryString();

        return view('admin.acb.index', compact('pageTitle', 'acbMembers', 'search'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'notes'    => 'nullable|string|max:500',
        ]);

        $user = User::where('username', $request->username)->first();

        if (!$user) {
            $notify[] = ['error', 'No user found with that username.'];
            return back()->withNotify($notify);
        }

        if ($user->acbUser()->exists()) {
            $notify[] = ['warning', "@{$user->username} is already an ACB member."];
            return back()->withNotify($notify);
        }

        AcbUser::create([
            'user_id'  => $user->id,
            'added_by' => auth('admin')->id(),
            'notes'    => $request->notes,
        ]);

        $notify[] = ['success', "@{$user->username} has been added to ACB."];
        return back()->withNotify($notify);
    }

    public function destroy(AcbUser $acbUser)
    {
        $username = $acbUser->user->username ?? 'user';
        $acbUser->delete();

        $notify[] = ['success', "@{$username} removed from ACB."];
        return back()->withNotify($notify);
    }
}
