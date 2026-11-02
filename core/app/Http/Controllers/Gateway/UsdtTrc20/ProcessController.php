<?php

namespace App\Http\Controllers\Gateway\UsdtTrc20;

use App\Http\Controllers\Controller;
use App\Services\Usdt\UsdtNotReady;
use App\Services\Usdt\UsdtPayments;

/**
 * USDT (TRC20) to the one Wiifarm address (App\Services\Usdt\UsdtPayments).
 * PaymentController::depositConfirm() calls process(); the payment itself
 * happens on the member's own page (User\UsdtDepositController).
 */
class ProcessController extends Controller
{
    public static function process($deposit)
    {
        try {
            app(UsdtPayments::class)->invoiceFor($deposit);
        } catch (UsdtNotReady $e) {
            return json_encode(['error' => true, 'message' => $e->getMessage()]);
        }

        return json_encode(['redirect' => true, 'redirect_url' => route('user.deposit.usdt', $deposit->trx)]);
    }
}
