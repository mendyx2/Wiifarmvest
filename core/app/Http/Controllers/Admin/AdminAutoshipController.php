<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAutoship;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class AdminAutoshipController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Autoship';
        $tab       = $request->get('tab', 'current');
        $search    = $request->search;
        $month     = $request->month;

        // Summary stats — always computed
        $totalCurrent   = User::where('autoship', '>', 0)->sum('autoship');
        $totalGained    = Transaction::where('remark', 'autoship')->sum('amount');
        $totalLost      = AdminAutoship::sum('amount');
        $totalThisMonth = AdminAutoship::where('month', now()->format('Y-m'))->sum('amount');
        $holdingCount   = User::where('autoship', '>', 0)->count();

        // Tab data — only query what's needed
        $current = $gained = $lost = collect();
        $months  = collect();

        if ($tab === 'current') {
            $current = User::where('autoship', '>', 0)
                ->when($search, fn($q) => $q
                    ->where('username',  'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname',  'like', "%{$search}%")
                    ->orWhere('email',     'like', "%{$search}%")
                )
                ->orderByDesc('autoship')
                ->paginate(25)
                ->withQueryString();
        }

        if ($tab === 'gained') {
            $gained = Transaction::with('user')
                ->where('remark', 'autoship')
                ->when($search, fn($q) => $q->whereHas('user', fn($u) => $u
                    ->where('username', 'like', "%{$search}%")
                    ->orWhere('email',    'like', "%{$search}%")
                ))
                ->latest()
                ->paginate(25)
                ->withQueryString();
        }

        if ($tab === 'lost') {
            $months = AdminAutoship::selectRaw('month')
                ->groupBy('month')
                ->orderByDesc('month')
                ->pluck('month');

            $lost = AdminAutoship::with('user')
                ->when($search, fn($q) => $q->whereHas('user', fn($u) => $u
                    ->where('username', 'like', "%{$search}%")
                    ->orWhere('email',    'like', "%{$search}%")
                ))
                ->when($month, fn($q) => $q->where('month', $month))
                ->latest('swept_at')
                ->paginate(25)
                ->withQueryString();
        }

        return view('admin.autoship.index', compact(
            'pageTitle', 'tab', 'search', 'month', 'months',
            'current', 'gained', 'lost',
            'totalCurrent', 'totalGained', 'totalLost', 'totalThisMonth', 'holdingCount'
        ));
    }
}
