<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\AdminNotification;
use App\Models\Pin;
use App\Models\Transaction;
use App\Models\User;
use DomainException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

/**
 * E-pins: a member pays from their VISA wallet for a pin, then gives the pin
 * to a new member, who activates their account with it
 * (Gateway\Paystack\ProcessController::pinPayment).
 */
class EpinController extends Controller
{
    /** Every pin is worth this much. The page has only ever offered this one amount. */
    public const PIN_AMOUNT = 9000;

    public function epin(Request $request)
    {
        // Kept from the old controller: this one account may not create pins.
        if (auth()->id() == 2) {
            abort(404);
        }

        $user = auth()->user();
        $mine = Pin::where('generate_user_id', $user->id);

        $pins = (clone $mine)->with('user:id,username')->orderBy('status')->searchable(['pin'])->filter(['status'])->latest()
            ->paginate(getPaginate(30))->withQueryString()
            ->through(fn (Pin $pin) => [
                'id'      => $pin->id,
                'pin'     => $pin->pin,
                'amount'  => getAmount($pin->amount) . ' ' . gs('cur_text'),
                'used'    => (int) $pin->status === Status::YES,
                'created' => showDateTime($pin->created_at, 'd M Y'),
                'usedAgo' => (int) $pin->status === Status::YES ? diffForHumans($pin->updated_at) : null,
                'usedBy'  => $pin->user?->username,
            ]);

        $charge = $this->charge(self::PIN_AMOUNT);

        return Inertia::render('User/Epin', [
            'visa'    => showAmount($user->visa ?? 0),
            'price'   => [
                'amount' => number_format(self::PIN_AMOUNT),
                'cur'    => gs('cur_text'),
                // The VISA wallet pays the pin plus the admin's e-pin charge.
                'charge' => $charge > 0 ? showAmount($charge) : null,
                'total'  => showAmount(self::PIN_AMOUNT + $charge),
                'enough' => (float) ($user->visa ?? 0) >= self::PIN_AMOUNT + $charge,
            ],
            // Counted over all of the member's pins; the old page counted only the current page.
            'stats'   => [
                'total'  => (clone $mine)->count(),
                'unused' => (clone $mine)->where('status', Status::NO)->count(),
                'used'   => (clone $mine)->where('status', Status::YES)->count(),
            ],
            'pins'    => $pins,
            'filters' => [
                'search' => (string) $request->query('search', ''),
                'status' => (string) $request->query('status', ''),
            ],
            'urls'    => [
                'index'    => route('user.epin.recharge'),
                'generate' => route('user.pin.generate'),
            ],
        ])->withViewData(['pageTitle' => 'Recharge Your Wallet']);
    }

    /** The old e-pin log was a copy of the Transactions page; the Transactions page filters by remark. */
    public function epinRechargeLog()
    {
        return to_route('user.transactions', ['remark' => 'epin']);
    }

    public function pinGenerate()
    {
        if (auth()->id() == 2) {
            abort(404);
        }

        $amount = self::PIN_AMOUNT;
        $charge = $this->charge($amount);

        try {
            DB::transaction(function () use ($amount, $charge) {
                // Lock the row: two quick clicks used to both pass the balance
                // check and spend the same VISA money twice.
                $user = User::lockForUpdate()->findOrFail(auth()->id());

                if ($amount + $charge > (float) $user->visa) {
                    throw new DomainException('You have insufficient money in your VISA wallet.');
                }

                $user->visa -= $amount + $charge;
                $user->save();

                $pin                   = new Pin();
                $pin->generate_user_id = $user->id;
                if ($user->stats == 1) {
                    $pin->stats = 1;
                }
                $pin->user_id = 0;
                $pin->status  = Status::NO;
                $pin->amount  = $amount;
                $pin->pin     = $this->newPinCode();
                $pin->details = 'Created via ' . $user->username;
                $pin->save();

                $transaction               = new Transaction();
                $transaction->user_id      = $user->id;
                $transaction->amount       = $amount;
                $transaction->post_balance = $user->visa;
                $transaction->charge       = $charge;
                $transaction->trx_type     = '-';
                $transaction->details      = 'Created E-pin';
                $transaction->remark       = 'epin';
                $transaction->trx          = getTrx();
                $transaction->save();

                $adminNotification            = new AdminNotification();
                $adminNotification->user_id   = $user->id;
                $adminNotification->title     = 'E-pin Generated successful';
                $adminNotification->click_url = urlPath('admin.withdraw.data.approved');
                $adminNotification->save();
            });
        } catch (DomainException $e) {
            return back()->withNotify([['error', $e->getMessage()]]);
        } catch (\Throwable $e) {
            report($e);
            return back()->withNotify([['error', 'Error creating pin']]);
        }

        return back()->withNotify([['success', 'The pin has been created']]);
    }

    private function charge(float $amount): float
    {
        return (int) gs('epin_status') === 1 ? $amount / 100 * (float) gs('epin_charge') : 0.0;
    }

    /**
     * A pin is money: anyone holding the code can activate an account with it.
     * random_int() is unpredictable; the old rand() was not.
     */
    private function newPinCode(): string
    {
        do {
            $code = implode('-', array_map(fn () => (string) random_int(10000000, 99999999), range(1, 4)));
        } while (Pin::where('pin', $code)->exists());

        return $code;
    }
}
