<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AcbController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        abort_unless($user->isAcb(), 403);

        $pageTitle = 'My ACB';

        $history = Transaction::where('user_id', $user->id)
            ->where('remark', 'acb_bonus')
            ->latest()
            ->paginate(20);

        // Migrated to React + Inertia (resources/js/Pages/User/Acb.tsx).
        // Was the Blade view Template::user.acb (deleted)
        return Inertia::render('User/Acb', [
            'balance' => showAmount($user->acb ?? 0),
            'history' => $history->through(function ($txn) {
                // Details look like "ACB Gen-1 — username ..."
                preg_match('/Gen-(\d)/', $txn->details ?? '', $gen);
                preg_match('/Gen-\d+ — (\S+) /', $txn->details ?? '', $member);
                return [
                    'id'      => $txn->id,
                    'trx'     => $txn->trx,
                    'gen'     => isset($gen[1]) ? (int) $gen[1] : null,
                    // The Blade view printed "{{ $memberUser }}" literally here (an @{{ escape).
                    'member'  => $member[1] ?? null,
                    'details' => $txn->details,
                    'amount'  => showAmount($txn->amount),
                    'balance' => showAmount($txn->post_balance),
                    'date'    => showDateTime($txn->created_at, 'd M Y'),
                    'ago'     => diffForHumans($txn->created_at),
                ];
            }),
        ])->withViewData(compact('pageTitle'));
    }
}
