<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\WelcomePackage;
use App\Services\WelcomePackageService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use RuntimeException;

/**
 * StockistWelcomePackageController
 *
 * Lets a stockist look up a customer's Welcome Back Package code and see
 * their redemption history. The package itself is redeemed in the POS, as
 * goods worth exactly its value taken from the stockist's stock, so stock
 * and money always move together.
 */
class StockistWelcomePackageController extends Controller
{
    public function __construct(private readonly WelcomePackageService $packages)
    {
    }

    public function index(Request $request)
    {
        $pageTitle = 'Welcome Package Redemption';
        $stockist  = auth()->user()->stockist;

        $history = WelcomePackage::with('user')
            ->redeemed()
            ->where('redeemed_by_stockist_id', $stockist->id)
            ->latest('redeemed_at')
            ->paginate(10)
            ->withQueryString();

        $totalRedeemed = WelcomePackage::redeemed()
            ->where('redeemed_by_stockist_id', $stockist->id)
            ->sum('amount');

        // "Verify Code" reloads this page with ?code=XXXXXXXXXX.
        $code    = strtoupper(preg_replace('/[^A-Za-z0-9]/', '', (string) $request->query('code', '')));
        $package = null;
        $error   = null;
        if ($code !== '') {
            try {
                $found   = $this->packages->findByCode($code)->load('user');
                $package = [
                    'code'     => $found->code,
                    'amount'   => showAmount($found->amount),
                    'source'   => (string) $found->source,
                    'username' => (string) @$found->user->username,
                    'fullname' => (string) @$found->user->fullname,
                ];
            } catch (RuntimeException $e) {
                $error = $e->getMessage();
            }
        }

        return Inertia::render('User/Stockist/WelcomePack', [
            'totalRedeemed' => showAmount($totalRedeemed),
            'history'       => $history->through(fn ($pkg) => [
                'id'       => $pkg->id,
                'fullname' => (string) (@$pkg->user->fullname ?: '—'),
                'date'     => $pkg->redeemed_at ? showDateTime($pkg->redeemed_at, 'M d, Y') : '',
                'time'     => $pkg->redeemed_at ? showDateTime($pkg->redeemed_at, 'H:i') : '',
                'amount'   => showAmount($pkg->amount),
                'source'   => (string) $pkg->source,
            ]),
            'code'     => $code,
            'package'  => $package,
            'error'    => $error,
            'urls'     => [
                'index' => route('user.stockist.welcome-pack.index'),
                // A package is always redeemed in the POS, as goods taken from stock.
                'pos'   => route('user.stockist.pos.index', $package ? ['code' => $package['code']] : []),
            ],
        ])->withViewData(compact('pageTitle'));
    }
}
