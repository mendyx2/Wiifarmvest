<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdminAutoship;
use App\Models\Transaction;
use App\Models\User;
use App\Support\BonusWalletReport;
use Illuminate\Http\Request;

/**
 * Autoship (Pages/Admin/BonusWallet.tsx): what members hold in their
 * autoship wallet, what was credited to it, and what the monthly sweep took.
 */
class AdminAutoshipController extends Controller
{
    public function index(Request $request)
    {
        $tab   = in_array($request->tab, ['current', 'gained', 'lost'], true) ? $request->tab : 'current';
        $month = preg_match('/^\d{4}-\d{2}$/', (string) $request->month) ? $request->month : null;

        return BonusWalletReport::render('Autoship', 'autoship', 'autoship', [
            ['Currently holding (' . User::where('autoship', '>', 0)->count() . ' members)', showAmount(User::where('autoship', '>', 0)->sum('autoship')), 'las la-wallet'],
            ['Credited (all time)', showAmount(Transaction::where('remark', 'autoship')->sum('amount')), 'las la-arrow-circle-down'],
            ['Swept this month', showAmount(AdminAutoship::where('month', now()->format('Y-m'))->sum('amount')), 'las la-times-circle'],
            ['Swept (all time)', showAmount(AdminAutoship::sum('amount')), 'las la-ban'],
        ], ['current' => 'Holding', 'gained' => 'Credits', 'lost' => 'Swept'], $tab, $request, function ($search) use ($month) {
            $months = AdminAutoship::select('month')->groupBy('month')->orderByDesc('month')->pluck('month')->all();
            $rows   = AdminAutoship::with('user')
                ->when($search !== '', fn ($q) => BonusWalletReport::whereMember($q, $search))
                ->when($month, fn ($q) => $q->where('month', $month))
                ->latest('swept_at')
                ->paginate(getPaginate())->withQueryString()
                ->through(fn (AdminAutoship $s) => ['id' => 's' . $s->id] + BonusWalletReport::memberCells($s->user) + ['amount' => showAmount($s->amount), 'note' => (string) $s->month, 'date' => $s->swept_at ? showDateTime($s->swept_at) : '—', 'trx' => (string) $s->trx]);
            return [$rows, ['months' => $months, 'month' => $month]];
        });
    }
}
