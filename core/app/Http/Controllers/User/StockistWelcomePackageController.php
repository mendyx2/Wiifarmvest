<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\StockistRedemption;
use App\Models\WelcomePackage;
use App\Services\WelcomePackageService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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

    public function index()
    {
        $pageTitle = 'Welcome Package Redemption';
        $stockist  = auth()->user()->stockist;

        $history = WelcomePackage::with('user')
            ->redeemed()
            ->where('redeemed_by_stockist_id', $stockist->id)
            ->latest('redeemed_at')
            ->paginate(10);

        $totalRedeemed = WelcomePackage::redeemed()
            ->where('redeemed_by_stockist_id', $stockist->id)
            ->sum('amount');

        return view('Template::user.stockist.welcome-pack.index', compact(
            'pageTitle', 'history', 'totalRedeemed'
        ));
    }

    public function verify(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        try {
            $package = $this->packages->findByCode($request->code)->load('user');

            return response()->json([
                'success' => true,
                'package' => [
                    'code'     => $package->code,
                    'amount'   => showAmount($package->amount, currencyFormat: false),
                    'source'   => $package->source,
                    'username' => $package->user->username,
                    'fullname' => $package->user->fullname,
                ],
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }

    public function redeem(Request $request): JsonResponse
    {
        $request->validate([
            'code' => 'required|string',
        ]);

        $stockist = auth()->user()->stockist;

        try {
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

            return response()->json([
                'success' => true,
                'message' => showAmount($package->amount) . ' credited to your stockist wallet.',
            ]);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()]);
        }
    }
}
