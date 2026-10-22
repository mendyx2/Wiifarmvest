<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\StockistRedemption;
use App\Models\WelcomePackage;
use App\Services\WelcomePackageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use RuntimeException;

/**
 * StockistWelcomePackageController
 *
 * Lets a stockist look up and redeem a customer's Welcome Back Package
 * code in person. Kept separate from StockistController so the redemption
 * workflow (and its own service dependency) stays a single responsibility.
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
            // Set by redeem() for the success dialog.
            'redeemed' => session('wp_redeemed'),
            'urls'     => [
                'index'  => route('user.stockist.welcome-pack.index'),
                'redeem' => route('user.stockist.welcome-pack.redeem'),
            ],
        ])->withViewData(compact('pageTitle'));
    }

    public function redeem(Request $request)
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $stockist = auth()->user()->stockist;

        try {
            // The package row is locked inside redeem(), so a double click or two
            // stockists can't both be paid for the same code.
            DB::transaction(function () use ($request, $stockist, &$package) {
                $package = $this->packages->findByCode($request->code);
                $package = $this->packages->redeem($package, $stockist);

                StockistRedemption::create([
                    'stockist_id'        => $stockist->id,
                    'type'               => StockistRedemption::TYPE_WELCOME_PACK,
                    'trx'                => $package->trx,
                    'reference_code'     => $package->code,
                    'welcome_package_id' => $package->id,
                    'customer_user_id'   => $package->user_id,
                    'items'              => [],
                    'quantity'           => 0,
                    'amount'             => $package->amount,
                    'redeemed_at'        => $package->redeemed_at,
                ]);
            });
        } catch (RuntimeException $e) {
            return to_route('user.stockist.welcome-pack.index', ['code' => $request->code])
                ->withErrors(['code' => $e->getMessage()]);
        }

        return to_route('user.stockist.welcome-pack.index')
            ->with('wp_redeemed', showAmount($package->amount) . ' credited to your stockist wallet.');
    }
}
