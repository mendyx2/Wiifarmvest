<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\WelcomePackage;
use Illuminate\Http\Request;

class AdminWelcomePackageController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Welcome Packages';
        $tab       = $request->get('tab', 'pending');
        $search    = $request->search;
        $userFilter = $request->user;

        $totalPending  = WelcomePackage::pending()->sum('amount');
        $totalRedeemed = WelcomePackage::redeemed()->sum('amount');
        $pendingCount  = WelcomePackage::pending()->count();
        $redeemedCount = WelcomePackage::redeemed()->count();

        $query = WelcomePackage::with(['user', 'redeemedBy.user'])
            ->when($tab === 'pending', fn ($q) => $q->pending())
            ->when($tab === 'redeemed', fn ($q) => $q->redeemed())
            ->when($search, fn ($q) => $q->whereHas('user', fn ($u) => $u
                ->where('username', 'like', "%{$search}%")
                ->orWhere('firstname', 'like', "%{$search}%")
                ->orWhere('lastname', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            ))
            ->when($userFilter, fn ($q) => $q->whereHas('user', fn ($u) => $u
                ->where('username', $userFilter)
            ));

        $packages = $query->latest()->paginate(25)->withQueryString();

        return view('admin.welcome-pack.index', compact(
            'pageTitle', 'tab', 'search', 'userFilter', 'packages',
            'totalPending', 'totalRedeemed', 'pendingCount', 'redeemedCount'
        ));
    }
}
