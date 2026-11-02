<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ProcessAcbBonusJob;
use App\Models\AcbUser;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * ACB (Pages/Admin/Acb.tsx): the members who earn the ACB bonus from their
 * downline's purchases (ProcessAcbBonusJob pays only uplines in this list).
 */
class AdminAcbController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'ACB Members';
        $search    = trim((string) $request->search);

        $members = AcbUser::with('user')
            ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($u) => $u->where(fn ($w) => $w->where('username', 'like', "%{$search}%")->orWhere('firstname', 'like', "%{$search}%")->orWhere('lastname', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))))
            ->latest('id')
            ->paginate(getPaginate())
            ->withQueryString();

        return Inertia::render('Admin/Acb', [
            'total'   => AcbUser::count(),
            // The bonus the job pays, per generation of upline
            'rates'   => collect(ProcessAcbBonusJob::RATES)->map(fn ($r, $gen) => ['gen' => $gen, 'percent' => $r * 100])->values(),
            'search'  => $search,
            'members' => $members->through(fn (AcbUser $a) => [
                'id'      => $a->id,
                'member'  => $a->user ? ['name' => (string) $a->user->fullname, 'username' => (string) $a->user->username, 'email' => (string) $a->user->email, 'url' => route('admin.users.detail', $a->user_id)] : null,
                'balance' => showAmount($a->user->acb ?? 0),
                'notes'   => (string) $a->notes,
                'added'   => showDateTime($a->created_at),
            ]),
            'urls' => ['self' => route('admin.acb.index'), 'store' => route('admin.acb.store'), 'destroy' => route('admin.acb.destroy', ':id')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:40',
            'notes'    => 'nullable|string|max:500',
        ]);

        $user = User::where('username', trim($request->username))->first();
        if (!$user) {
            return back()->withErrors(['username' => 'No member has this username.']);
        }
        if ($user->acbUser()->exists()) {
            return back()->withErrors(['username' => "@{$user->username} is already an ACB member."]);
        }

        AcbUser::create(['user_id' => $user->id, 'added_by' => auth('admin')->id(), 'notes' => $request->notes]);

        $notify[] = ['success', "@{$user->username} has been added to ACB."];
        return back()->withNotify($notify);
    }

    /** Take a member out of ACB. Their ACB balance stays; they earn no new ACB bonus. */
    public function destroy(AcbUser $acbUser)
    {
        $username = $acbUser->user->username ?? 'member';
        $acbUser->delete();

        $notify[] = ['success', "@{$username} removed from ACB."];
        return back()->withNotify($notify);
    }
}
