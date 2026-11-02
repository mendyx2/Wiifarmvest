<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Deposit;
use App\Models\UsdtInvoice;
use App\Services\Usdt\TronGridUnavailable;
use App\Services\Usdt\UsdtPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Inertia\Inertia;

/**
 * A member paying a USDT (TRC20) deposit (Pages/User/UsdtPay.tsx): the exact
 * amount and the Wiifarm address, a check for the payment while the page is
 * open, and a box for the transaction ID. App\Services\Usdt\UsdtPayments
 * decides; this only shows it.
 */
class UsdtDepositController extends Controller
{
    public function show(Request $request, string $trx, UsdtPayments $usdt)
    {
        [$deposit, $invoice] = $this->find($trx);

        // While the page is open, look for the payment every few seconds
        // (also where no scheduler runs). One look per deposit per interval.
        $waiting = (int) $deposit->status === Status::PAYMENT_INITIATE && !$invoice->transfer_id
            && $invoice->expires_at->gt(now()->subDay());
        if ($waiting && Cache::add('usdt-page-check:' . $invoice->id, 1, (int) config('usdt.page_check_seconds', 15))) {
            try {
                $usdt->scan($invoice, 3);
                $deposit->refresh();
                $invoice->refresh();
            } catch (TronGridUnavailable $e) {
                Log::notice('[USDT] page check skipped: ' . $e->getMessage());
            }
        }

        $status = match (true) {
            (int) $deposit->status === Status::PAYMENT_SUCCESS => 'paid',
            (int) $deposit->status === Status::PAYMENT_PENDING => 'review',
            (int) $deposit->status === Status::PAYMENT_REJECT  => 'rejected',
            $invoice->expires_at->isPast()                     => 'expired',
            default                                            => 'waiting',
        };

        return Inertia::render('User/UsdtPay', [
            'deposit' => [
                'trx'       => $deposit->trx,
                'amount'    => showAmount($deposit->amount),
                'usdt'      => UsdtPayments::format($invoice->units),
                'address'   => $invoice->address,
                'qr'        => cryptoQR($invoice->address),
                'expiresAt' => $invoice->expires_at->toIso8601String(),
                'canClaim'  => in_array($status, ['waiting', 'expired'], true) && $deposit->created_at->gt(now()->subDays((int) config('usdt.claim_days', 7))),
                'feedback'  => $status === 'rejected' ? (string) $deposit->admin_feedback : null,
            ],
            'status'      => $status,
            'checkEvery'  => (int) config('usdt.page_check_seconds', 15),
            'testNetwork' => config('usdt.network') !== 'mainnet',
            'urls'        => [
                'claim'   => route('user.deposit.usdt.claim', $deposit->trx),
                'history' => route('user.deposit.history'),
                'deposit' => route('user.deposit.index'),
            ],
        ])->withViewData(['pageTitle' => 'Pay with USDT']);
    }

    public function claim(Request $request, string $trx, UsdtPayments $usdt)
    {
        [$deposit] = $this->find($trx);
        $request->validate(['txid' => 'required|string|max:100']);

        $result = $usdt->claim($deposit, (string) $request->txid);

        if ($result['outcome'] === 'credited') {
            $notify[] = ['success', $result['message']];
            return to_route('user.deposit.history')->withNotify($notify);
        }
        if ($result['outcome'] === 'review') {
            $notify[] = ['info', $result['message']];
            return back()->withNotify($notify);
        }

        return back()->withErrors(['txid' => $result['message']]);
    }

    /** The member's own USDT deposit and its invoice, or 404. */
    private function find(string $trx): array
    {
        $deposit = Deposit::where('trx', $trx)->where('user_id', auth()->id())->firstOrFail();
        $invoice = UsdtInvoice::where('deposit_id', $deposit->id)->firstOrFail();

        return [$deposit, $invoice];
    }
}
