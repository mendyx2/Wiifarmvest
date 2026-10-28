<?php

namespace App\Support;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * The Autoship and Key-In Bonus pages were two copies of one report (one
 * wallet column on users, the transactions that credited it): the shared
 * part is here, the page is Pages/Admin/BonusWallet.tsx.
 */
class BonusWalletReport
{
    /**
     * @param string        $column  the users column holding the wallet (autoship, key_in_bonus)
     * @param string        $route   the route name under admin. (autoship, key-in-bonus)
     * @param array         $stats   [label, value, icon]
     * @param array         $tabs    key => label; "current" and "gained" are built here
     * @param callable|null $other   builds any other tab: fn (string $search) => [paginator, extra props]
     */
    public static function render(string $title, string $column, string $route, array $stats, array $tabs, string $tab, Request $request, ?callable $other = null)
    {
        $search = trim((string) $request->search);
        $extra  = [];

        if ($tab === 'current') {
            // The search's OR conditions were not grouped, so members with an empty wallet matched too
            $rows = User::where($column, '>', 0)
                ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w->where('username', 'like', "%{$search}%")->orWhere('firstname', 'like', "%{$search}%")->orWhere('lastname', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
                ->orderByDesc($column)
                ->paginate(getPaginate())->withQueryString()
                ->through(fn (User $u) => ['id' => 'u' . $u->id] + self::memberCells($u) + ['amount' => showAmount($u->$column), 'note' => '', 'date' => '', 'trx' => '']);
        } elseif ($tab === 'gained') {
            $rows = Transaction::with('user')->where('remark', $column)
                ->when($search !== '', fn ($q) => self::whereMember($q, $search))
                ->latest('id')
                ->paginate(getPaginate())->withQueryString()
                ->through(fn (Transaction $t) => ['id' => 't' . $t->id] + self::memberCells($t->user) + ['amount' => showAmount($t->amount), 'note' => (string) $t->details, 'date' => showDateTime($t->created_at), 'trx' => (string) $t->trx]);
        } else {
            [$rows, $extra] = $other($search);
        }

        return Inertia::render('Admin/BonusWallet', [
            'title'  => $title,
            'stats'  => array_map(fn ($s) => ['label' => $s[0], 'value' => $s[1], 'icon' => $s[2]], $stats),
            'tabs'   => collect($tabs)->map(fn ($label, $key) => ['key' => $key, 'label' => $label, 'url' => route("admin.{$route}.index", ['tab' => $key])])->values(),
            'tab'    => $tab,
            'search' => $search,
            'rows'   => $rows,
            'url'    => route("admin.{$route}.index"),
        ] + $extra)->withViewData(['pageTitle' => $title]);
    }

    public static function whereMember($query, string $search)
    {
        return $query->whereHas('user', fn ($u) => $u->where(fn ($w) => $w->where('username', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")));
    }

    public static function memberCells(?User $user): array
    {
        return [
            'member' => $user ? ['name' => (string) $user->fullname, 'username' => (string) $user->username, 'email' => (string) $user->email, 'url' => route('admin.users.detail', $user->id)] : null,
        ];
    }
}
