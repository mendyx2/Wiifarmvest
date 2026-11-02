<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\User;
use App\Support\BonusWalletReport;
use Illuminate\Http\Request;

/** Key-In Bonus (Pages/Admin/BonusWallet.tsx): what members hold and what was credited. */
class AdminKeyInBonusController extends Controller
{
    public function index(Request $request)
    {
        $tab = in_array($request->tab, ['current', 'gained'], true) ? $request->tab : 'current';

        return BonusWalletReport::render('Key-In Bonus', 'key_in_bonus', 'key-in-bonus', [
            ['Currently held (' . User::where('key_in_bonus', '>', 0)->count() . ' members)', showAmount(User::where('key_in_bonus', '>', 0)->sum('key_in_bonus')), 'las la-wallet'],
            ['Earned (all time)', showAmount(Transaction::where('remark', 'key_in_bonus')->sum('amount')), 'las la-arrow-circle-down'],
        ], ['current' => 'Holding', 'gained' => 'Credits'], $tab, $request);
    }
}
