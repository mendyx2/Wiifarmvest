<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;

class AdminKeyInBonusController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Key-In Bonus';
        $tab       = $request->get('tab', 'current');
        $search    = $request->search;

        // Summary stats — always computed
        $totalCurrent = User::where('key_in_bonus', '>', 0)->sum('key_in_bonus');
        $totalGained  = Transaction::where('remark', 'key_in_bonus')->sum('amount');
        $holderCount  = User::where('key_in_bonus', '>', 0)->count();

        $current = $gained = collect();

        if ($tab === 'current') {
            $current = User::where('key_in_bonus', '>', 0)
                ->when($search, fn($q) => $q
                    ->where('username',  'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname',  'like', "%{$search}%")
                    ->orWhere('email',     'like', "%{$search}%")
                )
                ->orderByDesc('key_in_bonus')
                ->paginate(25)
                ->withQueryString();
        }

        if ($tab === 'gained') {
            $gained = Transaction::with('user')
                ->where('remark', 'key_in_bonus')
                ->when($search, fn($q) => $q->whereHas('user', fn($u) => $u
                    ->where('username', 'like', "%{$search}%")
                    ->orWhere('email',    'like', "%{$search}%")
                ))
                ->latest()
                ->paginate(25)
                ->withQueryString();
        }

        return view('admin.key-in-bonus.index', compact(
            'pageTitle', 'tab', 'search',
            'current', 'gained',
            'totalCurrent', 'totalGained', 'holderCount'
        ));
    }
}
