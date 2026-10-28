<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WelcomePackage;
use App\Support\AdminPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

/** Welcome Pack → All Welcome Packages (Pages/Admin/WelcomePack/Index.tsx). */
class AdminWelcomePackageController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Welcome Packages';
        $tab       = in_array($request->tab, ['pending', 'redeemed', 'all'], true) ? $request->tab : 'pending';
        $search    = trim((string) $request->search);
        $member    = trim((string) $request->user);

        $packages = WelcomePackage::with(['user:id,firstname,lastname,username', 'redeemedBy.user:id,firstname,lastname,username'])
            ->when($tab === 'pending', fn ($q) => $q->pending())
            ->when($tab === 'redeemed', fn ($q) => $q->redeemed())
            ->when($search !== '', fn ($q) => $q->where(fn ($q) => $q
                ->where('code', 'like', "%{$search}%")
                ->orWhereHas('user', fn ($u) => $u
                    ->where('username', 'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%"))))
            ->when($member !== '', fn ($q) => $q->whereHas('user', fn ($u) => $u->where('username', $member)))
            ->latest('id')
            ->paginate(25)
            ->withQueryString()
            ->through(fn (WelcomePackage $p) => [
                'id'       => $p->id,
                'user'     => AdminPresenter::user($p->user),
                'code'     => (string) $p->code,
                'source'   => (string) $p->source,
                'status'   => (string) $p->status,
                'amount'   => showAmount($p->amount),
                'stockist' => $p->redeemedBy ? ($p->redeemedBy->business_name ?: $p->redeemedBy->user?->fullname) : null,
                'date'     => showDateTime($p->status === WelcomePackage::STATUS_REDEEMED ? $p->redeemed_at : $p->created_at, 'd M Y'),
                'time'     => showDateTime($p->status === WelcomePackage::STATUS_REDEEMED ? $p->redeemed_at : $p->created_at, 'H:i'),
            ]);

        return Inertia::render('Admin/WelcomePack/Index', [
            'packages' => $packages,
            'stats'    => [
                'pending'       => showAmount(WelcomePackage::pending()->sum('amount')),
                'pendingCount'  => WelcomePackage::pending()->count(),
                'redeemed'      => showAmount(WelcomePackage::redeemed()->sum('amount')),
                'redeemedCount' => WelcomePackage::redeemed()->count(),
            ],
            'filters'  => ['tab' => $tab, 'search' => $search, 'user' => $member],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }
}
