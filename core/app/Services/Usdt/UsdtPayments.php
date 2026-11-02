<?php

namespace App\Services\Usdt;

use App\Constants\Status;
use App\Http\Controllers\Gateway\PaymentController;
use App\Models\AdminNotification;
use App\Models\Deposit;
use App\Models\Gateway;
use App\Models\UsdtInvoice;
use App\Models\UsdtTransfer;
use Carbon\Carbon;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * USDT (TRC20) deposits to one Wiifarm address. Notes Step 122 has the
 * whole design; the rules that keep the money safe are:
 *
 *  1. Every deposit is asked for an exact amount that no other deposit can
 *     be asked for during an overlapping time window (base amount + 0.01 to
 *     0.99 USDT). So one payment fits at most one deposit.
 *  2. Only the official USDT contract, sent to the address the deposit was
 *     shown, in a transaction that succeeded and is final, counts.
 *  3. A transaction ID is stored once, ever (unique column), whether a
 *     member pasted it or the scan found it.
 *  4. A payment is credited automatically only when its amount is exact and
 *     its time is inside that deposit's window. Anything else a member
 *     claims goes to an admin; nothing is ever credited by guess.
 *  5. The credit itself is PaymentController::userDataUpdate(), which pays a
 *     deposit once even when called twice.
 */
class UsdtPayments
{
    public const ALIAS = 'UsdtTrc20';

    /** 1 USDT = 1,000,000 units (the token has 6 decimals) */
    public const UNITS = 1_000_000;

    private ?TronGrid $tron;

    public function __construct(?TronGrid $tron = null)
    {
        $this->tron = $tron;
    }

    // ------------------------------------------------------------------ settings

    public static function gateway(): ?Gateway
    {
        return Gateway::where('alias', self::ALIAS)->first();
    }

    /** @return array{address: string, api_key: string} */
    public static function settings(): array
    {
        $params = json_decode((string) self::gateway()?->gateway_parameters, true) ?: [];

        return [
            'address' => trim((string) ($params['wallet_address']['value'] ?? '')),
            'api_key' => trim((string) ($params['trongrid_api_key']['value'] ?? '')),
        ];
    }

    public function tron(): TronGrid
    {
        return $this->tron ??= new TronGrid(self::settings()['api_key'] ?: null);
    }

    /** 64370000 → "64.37"; 1234567 → "1.234567" (at least two decimals) */
    public static function format(int $units): string
    {
        $whole = intdiv($units, self::UNITS);
        $frac  = rtrim(str_pad((string) ($units % self::UNITS), 6, '0', STR_PAD_LEFT), '0');

        return $whole . '.' . str_pad($frac, 2, '0');
    }

    public static function explorerUrl(string $txid): string
    {
        return config('usdt.networks.' . config('usdt.network') . '.explorer') . $txid;
    }

    private static function skew(): int
    {
        return (int) config('usdt.skew_seconds', 300);
    }

    /** A blockchain time (ms) as a date in the app's time zone, for comparing with saved dates. */
    private static function at(int $ms): Carbon
    {
        return Carbon::createFromTimestampMs($ms)->setTimezone(config('app.timezone'));
    }

    // ------------------------------------------------------------------ 1. the amount to pay

    /**
     * The invoice of a deposit, made the first time: an exact amount nobody
     * else is asked for in an overlapping window, the current Wiifarm address
     * (kept, so changing the address later does not break open payments) and
     * the deadline. The deposit's USDT amount is set to it.
     *
     * @throws UsdtNotReady when the address is not set or no amount is free
     */
    public function invoiceFor(Deposit $deposit): UsdtInvoice
    {
        if ($invoice = UsdtInvoice::where('deposit_id', $deposit->id)->first()) {
            return $invoice;
        }

        $address = self::settings()['address'];
        if (!TronAddress::isValid($address)) {
            throw new UsdtNotReady('USDT payments are not set up yet. Please choose another method or try later.');
        }

        return DB::transaction(function () use ($deposit, $address) {
            // One amount is picked at a time: this row lock makes two deposits
            // starting together wait for each other
            Gateway::where('alias', self::ALIAS)->lockForUpdate()->first();

            if ($invoice = UsdtInvoice::where('deposit_id', $deposit->id)->first()) {
                return $invoice;
            }

            // The amount in whole cents, rounded up (round() first drops float noise like 64.3600000001)
            $base = max(1, (int) ceil(round((float) $deposit->final_amount * 100, 4)));
            $busy = UsdtInvoice::where('expires_at', '>=', now()->subSeconds(self::skew()))
                ->whereBetween('units', [($base + 1) * 10_000, ($base + 99) * 10_000])
                ->pluck('units')->all();

            $free = array_values(array_filter(range(1, 99), fn ($cents) => !in_array(($base + $cents) * 10_000, $busy, true)));
            if (!$free) {
                throw new UsdtNotReady('Too many USDT payments of this amount are open right now. Please try again in a few minutes, or change the amount slightly.');
            }

            $units   = ($base + $free[random_int(0, count($free) - 1)]) * 10_000;
            $invoice = UsdtInvoice::create([
                'deposit_id' => $deposit->id,
                'address'    => $address,
                'units'      => $units,
                'expires_at' => now()->addMinutes((int) config('usdt.window_minutes', 60)),
            ]);

            $deposit->final_amount = $units / self::UNITS;
            $deposit->btc_amount   = $units / self::UNITS;
            $deposit->btc_wallet   = $address;
            $deposit->save();

            return $invoice;
        });
    }

    // ------------------------------------------------------------------ 2. a member's transaction ID

    /**
     * A member says they paid deposit $deposit with transaction $txid.
     *
     * @return array{outcome: string, message: string}
     *   outcome: credited | review | waiting | refused | unavailable
     */
    public function claim(Deposit $deposit, string $txid): array
    {
        $txid    = strtolower(trim($txid));
        $invoice = UsdtInvoice::where('deposit_id', $deposit->id)->first();

        if (!preg_match('/^[0-9a-f]{64}$/', $txid)) {
            return $this->reply('refused', 'A transaction ID is 64 letters and numbers (0-9, a-f). Please copy it again from your wallet or exchange.');
        }
        if (!$invoice) {
            return $this->reply('refused', 'This deposit has no USDT payment details. Please start a new deposit.');
        }

        // Known already: answer without asking the blockchain
        $known = UsdtTransfer::where('txid', $txid)->first();
        if ($known && $known->status !== UsdtTransfer::UNMATCHED) {
            return $this->knownReply($known, $deposit);
        }

        if ((int) $deposit->status === Status::PAYMENT_SUCCESS) {
            return $this->reply('credited', 'This deposit has already been paid.');
        }
        if ((int) $deposit->status !== Status::PAYMENT_INITIATE) {
            return $this->reply('refused', 'This deposit is already with an admin or closed. Please contact support about this payment.');
        }
        if ($deposit->created_at->lt(now()->subDays((int) config('usdt.claim_days', 7)))) {
            return $this->reply('refused', 'This deposit is too old to claim a payment. Please contact support with your transaction ID.');
        }

        try {
            $tx = $this->tron()->transaction($txid);
        } catch (TronGridUnavailable $e) {
            Log::warning('[USDT] TronGrid unavailable during a claim: ' . $e->getMessage(), ['txid' => $txid]);
            return $this->reply('unavailable', 'We could not reach the Tron network just now. Your payment is safe; please try again in a minute.');
        }

        switch ($tx['state']) {
            case 'missing':
                return $this->reply('refused', 'We cannot find this transaction on the Tron network. Check the ID, and that you sent USDT on TRC20 (not ERC20 or BEP20).');
            case 'pending':
                return $this->reply('waiting', 'We found your payment. It is waiting for the network to confirm it (about a minute). Please try again shortly.');
            case 'failed':
                return $this->reply('refused', 'This transaction failed on the Tron network, so no USDT was sent. Please check your wallet.');
        }

        $units = array_sum(array_map(fn ($t) => $t['units'], array_filter($tx['transfers'], fn ($t) => $t['to'] === $invoice->address)));
        if ($units <= 0 || !$tx['time_ms']) {
            return $this->reply('refused', 'This transaction did not send USDT (TRC20) to the Wiifarm address shown on this page.');
        }

        $from = collect($tx['transfers'])->firstWhere('to', $invoice->address)['from'] ?? null;
        $result = $this->settle(['txid' => $txid, 'to' => $invoice->address, 'from' => $from, 'units' => $units, 'time_ms' => $tx['time_ms']], 'txid', $deposit);

        return match ($result['outcome']) {
            'credited'       => $result['deposit']->id === $deposit->id
                ? $this->reply('credited', 'Payment confirmed. Your deposit has been added to your balance.')
                : $this->reply('refused', 'This payment belongs to a different deposit and has been credited there.'),
            'existing'       => $this->knownReply($result['transfer'], $deposit),
            'review'         => $this->reply('review', 'We received ' . self::format($units) . ' USDT, but it does not match this deposit exactly (amount or time). An admin will check it; you do not need to do anything else.'),
            'too_old'        => $this->reply('refused', 'This payment was made before this deposit was started, so it cannot pay it. Please contact support with the transaction ID.'),
            'claimant_paid'  => $this->reply('refused', 'This deposit has already received a payment. Please contact support about this one.'),
            default          => $this->reply('refused', 'This deposit is no longer open. Please contact support with your transaction ID.'),
        };
    }

    private function knownReply(UsdtTransfer $known, Deposit $deposit): array
    {
        $mine = (int) $known->deposit_id === (int) $deposit->id;

        return match ($known->status) {
            UsdtTransfer::CREDITED => $mine
                ? $this->reply('credited', 'This payment has already been credited to this deposit.')
                : $this->reply('refused', 'This payment has already been used for another deposit.'),
            UsdtTransfer::REVIEW   => $mine
                ? $this->reply('review', 'This payment is already with an admin for checking.')
                : $this->reply('refused', 'This payment has already been submitted for another deposit.'),
            UsdtTransfer::REJECTED => $this->reply('refused', 'This payment was refused by an admin. Please contact support.'),
            default                => $this->reply('refused', 'This payment could not be matched. Please contact support.'),
        };
    }

    private function reply(string $outcome, string $message): array
    {
        return ['outcome' => $outcome, 'message' => $message];
    }

    // ------------------------------------------------------------------ 3. one payment, decided once

    /**
     * Decide what one verified payment pays, record it, and credit it.
     *
     * $payment: {txid, to, from, units, time_ms}, already checked against the
     * final state of the chain (TronGrid::transaction()).
     *
     * @return array{outcome: string, transfer?: UsdtTransfer, deposit?: Deposit}
     *   outcome: credited | existing | review | unmatched | too_old | claimant_paid | claimant_closed
     */
    public function settle(array $payment, string $source, ?Deposit $claimant = null): array
    {
        try {
            $result = $this->decide($payment, $source, $claimant);
        } catch (UniqueConstraintViolationException) {
            // Another request recorded the same payment a moment ago: decide again on its row
            $result = $this->decide($payment, $source, $claimant);
        }

        Log::info('[USDT] ' . $result['outcome'], ['txid' => $payment['txid'], 'units' => $payment['units'], 'deposit' => $result['deposit']->id ?? null, 'source' => $source]);

        // Money moves after the record is saved: userDataUpdate() credits a deposit
        // once, so a retry (or a crash between the two) is safe
        $transfer = $result['transfer'] ?? null;
        if ($transfer && $transfer->status === UsdtTransfer::CREDITED && $transfer->deposit_id) {
            $deposit = Deposit::find($transfer->deposit_id);
            if ($deposit && in_array((int) $deposit->status, [Status::PAYMENT_INITIATE, Status::PAYMENT_PENDING], true)) {
                PaymentController::userDataUpdate($deposit);
                $deposit->refresh();
            }
            $result['deposit'] = $deposit;
        }

        if (in_array($result['outcome'], ['review', 'unmatched'], true) && ($result['new'] ?? false)) {
            $this->tellAdmins($result['outcome'], $transfer, $result['deposit'] ?? null);
        }

        return $result;
    }

    private function decide(array $p, string $source, ?Deposit $claimant): array
    {
        return DB::transaction(function () use ($p, $source, $claimant) {
            $row = UsdtTransfer::where('txid', $p['txid'])->lockForUpdate()->first();
            if ($row && $row->status !== UsdtTransfer::UNMATCHED) {
                return ['outcome' => 'existing', 'transfer' => $row, 'new' => false];
            }
            $new = !$row;
            $row ??= new UsdtTransfer([
                'txid'          => $p['txid'],
                'from_address'  => $p['from'],
                'to_address'    => $p['to'],
                'units'         => $p['units'],
                'block_time_ms' => $p['time_ms'],
                'source'        => $source,
            ]);

            // The deposit this payment fits: exact amount, same address, sent inside
            // its window, not paid yet. Rule 1 means at most one can fit.
            $fits = UsdtInvoice::where('units', $p['units'])
                ->where('address', $p['to'])
                ->whereNull('transfer_id')
                ->where('created_at', '<=', self::at($p['time_ms'] + self::skew() * 1000))
                ->where('expires_at', '>=', self::at($p['time_ms']))
                ->lockForUpdate()
                ->get()
                ->filter(fn (UsdtInvoice $i) => (int) Deposit::whereKey($i->deposit_id)->value('status') === Status::PAYMENT_INITIATE)
                ->values();

            if ($fits->count() === 1) {
                $invoice = $fits->first();
                $row->fill(['status' => UsdtTransfer::CREDITED, 'deposit_id' => $invoice->deposit_id, 'note' => null])->save();
                $invoice->update(['transfer_id' => $row->id]);
                // What paid it, for the admin's deposit page
                Deposit::whereKey($invoice->deposit_id)->update(['detail' => json_encode([
                    'transaction_id' => $p['txid'],
                    'received'       => self::format($p['units']) . ' USDT',
                    'from'           => (string) $p['from'],
                    'sent_at'        => self::at($p['time_ms'])->toDateTimeString(),
                    'found_by'       => $source === 'txid' ? 'member sent the transaction ID' : 'automatic check',
                ])]);

                return ['outcome' => 'credited', 'transfer' => $row, 'new' => $new];
            }
            if ($fits->count() > 1) {
                // Cannot happen while rule 1 holds; never guess between two members
                Log::error('[USDT] One payment fits several deposits', ['txid' => $p['txid'], 'deposits' => $fits->pluck('deposit_id')->all()]);
            }

            if (!$claimant) {
                $row->fill(['status' => UsdtTransfer::UNMATCHED, 'deposit_id' => null, 'note' => $fits->count() > 1 ? 'Fits several deposits: check by hand' : 'No open deposit asked for this amount at this time'])->save();

                return ['outcome' => 'unmatched', 'transfer' => $row, 'new' => $new];
            }

            // A member says it is theirs, but it does not fit exactly
            $invoice = UsdtInvoice::where('deposit_id', $claimant->id)->lockForUpdate()->first();
            $status  = (int) Deposit::whereKey($claimant->id)->lockForUpdate()->value('status');

            if (!$invoice || $invoice->transfer_id) {
                $this->keepUnmatched($row);
                return ['outcome' => 'claimant_paid', 'transfer' => $row, 'new' => $new];
            }
            if ($status !== Status::PAYMENT_INITIATE) {
                $this->keepUnmatched($row);
                return ['outcome' => 'claimant_closed', 'transfer' => $row, 'new' => $new];
            }
            if ($p['time_ms'] < $claimant->created_at->getTimestampMs() - self::skew() * 1000) {
                $this->keepUnmatched($row);
                return ['outcome' => 'too_old', 'transfer' => $row, 'new' => $new];
            }

            $reasons = [];
            if ($p['units'] !== $invoice->units) {
                $reasons[] = 'expected ' . self::format($invoice->units) . ' USDT, received ' . self::format($p['units']);
            }
            if ($p['time_ms'] > $invoice->expires_at->getTimestampMs()) {
                $reasons[] = 'sent after the payment window closed';
            }
            if ($fits->count() > 1) {
                $reasons[] = 'fits several deposits';
            }

            $row->fill(['status' => UsdtTransfer::REVIEW, 'deposit_id' => $claimant->id, 'note' => ucfirst(implode('; ', $reasons))])->save();
            $invoice->update(['transfer_id' => $row->id]);

            Deposit::whereKey($claimant->id)->where('status', Status::PAYMENT_INITIATE)->update([
                'status' => Status::PAYMENT_PENDING,
                'detail' => json_encode([
                    'transaction_id'  => $p['txid'],
                    'expected'        => self::format($invoice->units) . ' USDT',
                    'received'        => self::format($p['units']) . ' USDT',
                    'from'            => (string) $p['from'],
                    'sent_at'         => self::at($p['time_ms'])->toDateTimeString(),
                    'payment_window'  => $invoice->created_at->toDateTimeString() . ' to ' . $invoice->expires_at->toDateTimeString(),
                    'why_review'      => ucfirst(implode('; ', $reasons)),
                    'warning'         => 'Anyone can see payments to a public address. Confirm with the member that this payment is theirs before approving.',
                ]),
            ]);

            return ['outcome' => 'review', 'transfer' => $row, 'deposit' => $claimant->fresh(), 'new' => true];
        });
    }

    private function keepUnmatched(UsdtTransfer $row): void
    {
        $row->fill(['status' => UsdtTransfer::UNMATCHED, 'deposit_id' => null, 'note' => $row->note ?: 'No open deposit asked for this amount at this time'])->save();
    }

    private function tellAdmins(string $outcome, ?UsdtTransfer $transfer, ?Deposit $deposit): void
    {
        if (!$transfer) {
            return;
        }
        $notice           = new AdminNotification();
        $notice->user_id  = $deposit->user_id ?? 0;
        $notice->title    = $outcome === 'review'
            ? 'USDT payment of ' . self::format($transfer->units) . ' needs checking (deposit ' . $deposit?->trx . ')'
            : 'USDT payment of ' . self::format($transfer->units) . ' matches no deposit';
        $notice->click_url = $outcome === 'review' && $deposit ? urlPath('admin.deposit.details', $deposit->id) : urlPath('admin.deposit.usdt');
        $notice->save();
    }

    // ------------------------------------------------------------------ 4. admin decisions

    /**
     * An admin approved or rejected a deposit that was waiting with a USDT
     * payment. A rejected payment is released (unmatched), so the member it
     * really belongs to can still claim it.
     */
    public static function adminDecided(Deposit $deposit, bool $approved): void
    {
        DB::transaction(function () use ($deposit, $approved) {
            $row = UsdtTransfer::where('deposit_id', $deposit->id)->where('status', UsdtTransfer::REVIEW)->lockForUpdate()->first();
            if (!$row) {
                return;
            }
            if ($approved) {
                $row->update(['status' => UsdtTransfer::CREDITED]);
                return;
            }
            UsdtInvoice::where('transfer_id', $row->id)->update(['transfer_id' => null]);
            $row->update(['status' => UsdtTransfer::UNMATCHED, 'deposit_id' => null, 'note' => 'Refused by an admin for deposit ' . $deposit->trx]);
        });
    }

    // ------------------------------------------------------------------ 5. finding payments nobody reported

    /**
     * Look for payments into the Wiifarm address(es) of open deposits and
     * settle each new one. With $only, just that deposit's payment (the
     * member's open payment page asks this every few seconds).
     *
     * @return int how many deposits were credited
     * @throws TronGridUnavailable
     */
    public function scan(?UsdtInvoice $only = null, int $maxChecks = 30): int
    {
        $open = $only ? collect([$only]) : UsdtInvoice::whereNull('transfer_id')
            ->where('expires_at', '>=', now()->subDay())
            ->whereIn('deposit_id', Deposit::where('status', Status::PAYMENT_INITIATE)->select('id'))
            ->get();

        $credited = 0;
        foreach ($open->groupBy('address') as $address => $invoices) {
            $since = $invoices->min(fn ($i) => $i->created_at->getTimestampMs()) - self::skew() * 1000;
            $wanted = $only ? [$only->units] : null;

            foreach ($this->tron()->incoming($address, $since) as $seen) {
                if ($maxChecks <= 0) {
                    break 2;
                }
                if ($wanted && !in_array($seen['units'], $wanted, true)) {
                    continue;
                }
                if (UsdtTransfer::where('txid', $seen['txid'])->exists()) {
                    continue;
                }

                // The list is only a lead: read the transaction itself before deciding
                $maxChecks--;
                $tx = $this->tron()->transaction($seen['txid']);
                if ($tx['state'] !== 'confirmed') {
                    continue;
                }
                $mine = array_filter($tx['transfers'], fn ($t) => $t['to'] === $address);
                $units = array_sum(array_map(fn ($t) => $t['units'], $mine));
                if ($units <= 0 || !$tx['time_ms']) {
                    continue;
                }

                $result = $this->settle(['txid' => $seen['txid'], 'to' => $address, 'from' => reset($mine)['from'] ?? null, 'units' => $units, 'time_ms' => $tx['time_ms']], 'scan');
                if ($result['outcome'] === 'credited') {
                    $credited++;
                }
            }
        }

        return $credited;
    }
}
