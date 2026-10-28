<?php

namespace App\Services;

use App\Constants\Status;
use App\Models\AffiliateOrder;
use App\Models\WelcomePackage;
use Carbon\Carbon;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class FinancialReportService
{
    /**
     * @param  Carbon|null $from start of the period (null = from the beginning)
     * @param  Carbon|null $to   end of the period (null = up to now)
     */
    public function build(?Carbon $from, ?Carbon $to): array
    {
        $range = [$from?->copy()->startOfDay(), $to?->copy()->endOfDay()];

        $members   = $this->ledger('transactions', $range);
        $stockists = $this->ledger('stransactions', $range);

        $income  = $this->income($range, $members);
        $outflow = $this->outflow($range, $members, $stockists);

        return [
            'period'       => $this->periodLabel($range),
            'is_all_time'  => !$from && !$to,
            'income'       => $income,
            'cash_in'      => $this->cashIn($range, $members),
            'outflow'      => $outflow,
            'cash_out'     => $this->cashOut($range),
            'owed'         => $this->owedNow(),
            'unclassified' => $this->unclassified($members, $stockists),
            'standing'     => $this->standing($range, $income['total'], $outflow['total']),
        ];
    }

    /**
     * Parse the admin date-range picker value ("September 01, 2026 - September 25, 2026").
     *
     * @return array{0: ?Carbon, 1: ?Carbon}
     */
    public static function rangeFromRequest(?string $date): array
    {
        if (!$date) {
            return [null, null];
        }

        try {
            $parts = array_map('trim', explode(' - ', $date));
            $from  = Carbon::parse($parts[0]);
            $to    = isset($parts[1]) ? Carbon::parse($parts[1]) : $from->copy();
        } catch (\Throwable) {
            throw ValidationException::withMessages(['date' => 'Invalid date range.']);
        }

        return $from->lte($to) ? [$from, $to] : [$to, $from];
    }

    // ── Income ────────────────────────────────────────────────────────────

    private function income(array $range, Collection $ledger): array
    {
        // A member's current project price = what they paid at registration
        // plus every upgrade difference since, so the registration amount is
        // the current price minus their upgrade payments.
        $upgradesByUser = DB::table('transactions')
            ->where('trx_type', '-')->where('remark', 'project_upgrade')
            ->groupBy('user_id')
            ->select('user_id', DB::raw('SUM(amount) AS paid'));

        $registrations = $this->within(DB::table('users'), 'users.created_at', $range)
            ->leftJoin('projects', 'projects.id', '=', 'users.project_id')
            ->leftJoinSub($upgradesByUser, 'up', 'up.user_id', '=', 'users.id')
            ->where('users.profile_complete', Status::YES)
            ->groupBy('projects.id', 'projects.title', 'projects.sort_order')
            ->orderBy('projects.sort_order')
            ->selectRaw('projects.title, COUNT(*) AS n, SUM(COALESCE(projects.amount, ?) - COALESCE(up.paid, 0)) AS total', [(float) gs('registration_fee')])
            ->get();

        $newMembers = (int) $registrations->sum('n');
        $upgrades   = $this->count($ledger, '-', 'project_upgrade');

        $subscribe = $this->line(
            'Project Subscribe',
            (float) $registrations->sum('total') + $this->sum($ledger, '-', 'project_upgrade'),
            $newMembers + $upgrades,
            number_format($newMembers) . ' new ' . str('member')->plural($newMembers) . ' · ' . number_format($upgrades) . ' ' . str('upgrade')->plural($upgrades),
            'las la-project-diagram',
            route('admin.projects.index')
        );

        $shares = $this->within(DB::table('rinvestments'), 'created_at', $range)
            ->where('status', 1)
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(units * unit_cost), 0) AS total, COALESCE(SUM(units), 0) AS units')
            ->first();

        // Current checkout keeps the order value on its invoice (orders.total_price
        // stays 0); older orders carry it on the order row itself
        $invoiceTotals = DB::table('invoices')
            ->groupBy('order_id')
            ->select('order_id', DB::raw('SUM(total_amount) AS total'));

        $memberOrders = $this->within(DB::table('orders'), 'orders.created_at', $range)
            ->leftJoinSub($invoiceTotals, 'inv', 'inv.order_id', '=', 'orders.id')
            ->where('orders.status', '!=', Status::ORDER_CANCELED)
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(COALESCE(NULLIF(orders.total_price, 0), inv.total, 0)), 0) AS total')
            ->first();

        // Paystack orders are money in once paid; cash-on-pickup orders only when collected
        $shopOrders = $this->within(DB::table('affiliate_orders'), 'created_at', $range)
            ->whereIn('status', [AffiliateOrder::STATUS_PAID, AffiliateOrder::STATUS_FULFILLED])
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(total_amount), 0) AS total')->first();

        // Paid from the stockist wallet when placed; cancelling refunds it
        $stockOrders = $this->within(DB::table('stockist_orders'), 'created_at', $range)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(grand_total), 0) AS total')->first();

        $autoship = $this->within(DB::table('admin_autoship'), 'created_at', $range)
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(amount), 0) AS total')->first();

        $depositFees = $this->within(DB::table('deposits'), 'created_at', $range)
            ->where('status', Status::PAYMENT_SUCCESS)
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(charge), 0) AS total')->first();

        $withdrawFees = $this->within(DB::table('withdrawals'), 'created_at', $range)
            ->where('status', Status::PAYMENT_SUCCESS)
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(charge), 0) AS total')->first();

        // Only the interest share of each repayment is income; the rest is principal coming back
        $loanInterest = $this->within(DB::table('loan_repayments AS r'), 'r.created_at', $range)
            ->join('loans AS l', 'l.id', '=', 'r.loan_id')
            ->where('r.status', 'completed')
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(r.amount * l.total_interest / NULLIF(l.total_repayable, 0)), 0) AS total')
            ->first();

        $activation = 'stockist_activation_fee';

        return $this->section([
            $this->group('Subscriptions & Investments', 'las la-layer-group', [
                $subscribe,
                $this->line('Farm Shares (FFO)', $shares->total, $shares->n, $this->plural($shares->n, 'purchase') . ' · ' . number_format((float) $shares->units) . ' units', 'las la-seedling', route('admin.report.invest')),
                $this->line('Unclaimed Autoship', $autoship->total, $autoship->n, $this->plural($autoship->n, 'balance') . ' swept at month end', 'las la-sync-alt', route('admin.autoship.index')),
            ]),
            $this->group('Sales from Products', 'las la-shopping-basket', [
                $this->line('Member Product Orders', $memberOrders->total, $memberOrders->n, $this->plural($memberOrders->n, 'order'), 'las la-shopping-cart', route('admin.order.index')),
                $this->line('Affiliate Shop Orders', $shopOrders->total, $shopOrders->n, $this->plural($shopOrders->n, 'paid order'), 'las la-link', route('admin.affiliate.orders')),
                $this->line('Stockist Stock Orders', $stockOrders->total, $stockOrders->n, $this->plural($stockOrders->n, 'order'), 'las la-truck', route('admin.stockist-orders.index')),
            ]),
            $this->group('Fees & Interest', 'las la-percentage', [
                $this->line('Deposit Charges', $depositFees->total, $depositFees->n, $this->plural($depositFees->n, 'deposit'), 'las la-receipt', route('admin.deposit.successful')),
                $this->line('Withdrawal Charges', $withdrawFees->total, $withdrawFees->n, $this->plural($withdrawFees->n, 'withdrawal'), 'las la-receipt', route('admin.withdraw.data.approved')),
                $this->line('Stockist Activation Fees', $this->sum($ledger, '-', $activation), $this->count($ledger, '-', $activation), $this->plural($this->count($ledger, '-', $activation), 'stockist'), 'las la-store-alt', route('admin.report.transaction', ['remark' => $activation])),
                $this->line('Loan Interest', $loanInterest->total, $loanInterest->n, $this->plural($loanInterest->n, 'repayment'), 'las la-hand-holding-usd', route('admin.loans.index')),
            ]),
        ]);
    }

    /** Cash that came in but is not income on its own — members spend it on the lines above. */
    private function cashIn(array $range, Collection $ledger): array
    {
        $deposits = $this->within(DB::table('deposits'), 'created_at', $range)
            ->where('status', Status::PAYMENT_SUCCESS)
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(amount), 0) AS total')->first();

        $lines = [$this->line('Deposits', $deposits->total, $deposits->n, $this->plural($deposits->n, 'deposit') . ' into Money Box', 'las la-wallet', route('admin.deposit.successful'))];

        foreach (config('financial_report.admin_topups') as $credit => $cfg) {
            $n = $this->count($ledger, '+', $credit);
            $lines[] = $this->line(
                'Admin Top-ups: ' . $cfg['label'],
                $this->sum($ledger, '+', $credit) - $this->sum($ledger, '-', $cfg['debit']),
                $n,
                $this->plural($n, 'top-up') . ' (net of admin debits)',
                'las la-user-shield',
                route('admin.report.transaction', ['remark' => $credit])
            );
        }

        $stockistFunding = $this->within(DB::table('sktransactions'), 'created_at', $range)
            ->where('type', 'credit')
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(amount), 0) AS total')->first();

        $lines[] = $this->line('Stockist Wallet Funding', $stockistFunding->total, $stockistFunding->n, $this->plural($stockistFunding->n, 'top-up'), 'las la-store', route('admin.stockist.dashboard'));

        $repaid = $this->within(DB::table('loan_repayments'), 'created_at', $range)
            ->where('status', 'completed')
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(amount), 0) AS total')->first();

        $lines[] = $this->line('Loan Repayments', $repaid->total, $repaid->n, $this->plural($repaid->n, 'repayment'), 'las la-undo-alt', route('admin.loans.index'));

        return ['lines' => $lines, 'total' => $this->total($lines)];
    }

    // ── Outflow ───────────────────────────────────────────────────────────

    private function outflow(array $range, Collection $members, Collection $stockists): array
    {
        $bonuses = [];
        foreach (config('financial_report.member_payouts') as $remark => $label) {
            $n = $this->count($members, '+', $remark);
            $bonuses[] = $this->line($label, $this->sum($members, '+', $remark), $n, $this->plural($n, 'payment'), 'las la-hand-holding-usd', route('admin.report.transaction', ['remark' => $remark]));
        }

        $awards = $this->within(DB::table('user_awards'), 'paid_at', $range)
            ->where('status', 1)
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(paid_amount), 0) AS total')->first();
        $bonuses[] = $this->line('Rank Awards', $awards->total, $awards->n, $this->plural($awards->n, 'award') . ' paid', 'las la-trophy', route('admin.awards.payments'));

        $stockistLines = [];
        foreach (config('financial_report.stockist_payouts') as $remark => $label) {
            $n = $this->count($members, '+', $remark) + $this->count($stockists, '+', $remark);
            $stockistLines[] = $this->line(
                $label,
                $this->sum($members, '+', $remark) + $this->sum($stockists, '+', $remark),
                $n,
                $this->plural($n, 'payment'),
                'las la-store',
                route('admin.stockist.dashboard')
            );
        }

        $div = config('financial_report.dividends');

        // Interest is the only cost of savings and farm cycles; the principal was the member's own money
        $interest = DB::table('savings_products')
            ->where('status', 'matured')
            ->when($range[0], fn ($q, $from) => $q->whereRaw('COALESCE(matured_at, updated_at) >= ?', [$from]))
            ->when($range[1], fn ($q, $to) => $q->whereRaw('COALESCE(matured_at, updated_at) <= ?', [$to]))
            ->selectRaw("COALESCE(SUM(type = 'farm'), 0) AS farm_n, COALESCE(SUM(type <> 'farm'), 0) AS savings_n, COALESCE(SUM(CASE WHEN type = 'farm' THEN interest_earned ELSE 0 END), 0) AS farm, COALESCE(SUM(CASE WHEN type <> 'farm' THEN interest_earned ELSE 0 END), 0) AS savings")
            ->first();

        $dividendsPaid = $this->count($members, '+', $div['credit']);

        return $this->section([
            $this->group('Bonuses & Commissions to Members', 'las la-hand-holding-usd', $bonuses),
            $this->group('Stockist Bonuses & Payouts', 'las la-store', $stockistLines),
            $this->group('Dividends & Interest', 'las la-coins', [
                $this->line(
                    'FFO Dividends',
                    $this->sum($members, '+', $div['credit']) - $this->sum($members, '-', $div['reversal']),
                    $dividendsPaid,
                    $this->plural($dividendsPaid, 'payment') . ' (net of reversals)',
                    'las la-coins',
                    route('admin.dividend.history')
                ),
                $this->line('Farm-cycle Returns', $interest->farm, (int) $interest->farm_n, $this->plural($interest->farm_n, 'matured cycle') . ' (interest only)', 'las la-tractor', route('admin.savings.cycles')),
                $this->line('Savings Interest', $interest->savings, (int) $interest->savings_n, $this->plural($interest->savings_n, 'matured plan'), 'las la-piggy-bank', route('admin.savings.index')),
            ]),
        ]);
    }

    /** Cash that left, settling balances already counted in Outflow (or lent, not spent). */
    private function cashOut(array $range): array
    {
        $withdrawals = $this->within(DB::table('withdrawals'), 'created_at', $range)
            ->where('status', Status::PAYMENT_SUCCESS)
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(COALESCE(after_charge, amount - charge)), 0) AS total')->first();

        $loans = $this->within(DB::table('loans'), 'disbursed_at', $range)
            ->whereNotNull('disbursed_at')
            ->selectRaw('COUNT(*) AS n, COALESCE(SUM(original_amount), 0) AS total')->first();

        $lines = [
            $this->line('Withdrawals Paid', $withdrawals->total, $withdrawals->n, $this->plural($withdrawals->n, 'withdrawal') . ' approved', 'las la-money-bill-wave', route('admin.withdraw.data.approved')),
            $this->line('Loans Disbursed', $loans->total, $loans->n, $this->plural($loans->n, 'loan'), 'las la-hand-holding', route('admin.loans.index')),
        ];

        return ['lines' => $lines, 'total' => $this->total($lines)];
    }

    // ── Position right now ────────────────────────────────────────────────

    
    private function owedNow(): array
    {
        $bonusColumns = implode(' + ', array_map(fn ($c) => "COALESCE($c, 0)", WalletService::BONUS_WALLETS));

        $users = DB::table('users')->selectRaw(
            "COALESCE(SUM(CASE WHEN balance > 0 THEN balance ELSE 0 END), 0) AS money_box,
             COALESCE(SUM(CASE WHEN balance < 0 THEN -balance ELSE 0 END), 0) AS owing,
             COALESCE(SUM(visa), 0) AS visa,
             COALESCE(SUM(product_wallet), 0) AS product_wallet,
             COALESCE(SUM(savings_wallet), 0) AS savings,
             COALESCE(SUM($bonusColumns), 0) AS bonuses"
        )->first();

        $pendingWithdrawals = DB::table('withdrawals')->where('status', Status::PAYMENT_PENDING);
        $pendingPackages    = DB::table('welcome_packages')->where('status', WelcomePackage::STATUS_PENDING);
        $pendingWd          = $pendingWithdrawals->count();
        $pendingWp          = $pendingPackages->count();

        $toMembers = [
            $this->line('Money Box Balances', $users->money_box, null, 'members\' spendable balance', 'las la-wallet', route('admin.users.with.balance')),
            $this->line('Unclaimed Bonuses', $users->bonuses, null, 'earned but not yet transferred', 'las la-gift', route('admin.users.all')),
            $this->line('Visa Wallets', $users->visa, null, 'held for registrations', 'las la-credit-card', route('admin.users.all')),
            $this->line('Product Wallets', $users->product_wallet, null, 'held for product purchases', 'las la-shopping-bag', route('admin.users.all')),
            $this->line('Savings Held', $users->savings, null, 'locked in savings plans', 'las la-piggy-bank', route('admin.savings.index')),
            $this->line('Stockist Wallets', DB::table('stockists')->sum('wallet'), null, 'stockists\' balance', 'las la-store', route('admin.stockist.dashboard')),
            $this->line('Pending Withdrawals', $pendingWithdrawals->sum('amount'), $pendingWd, $this->plural($pendingWd, 'request') . ' awaiting approval', 'las la-hourglass-half', route('admin.withdraw.data.pending')),
            $this->line('Welcome Packages', $pendingPackages->sum('amount'), $pendingWp, $this->plural($pendingWp, 'package') . ' not yet redeemed', 'las la-box-open', route('admin.welcome-pack.index')),
        ];

        $toCompany = [
            $this->line('Loans Outstanding', DB::table('loans')->whereNotNull('disbursed_at')->where('outstanding', '>', 0)->sum('outstanding'), null, 'still to be repaid', 'las la-hand-holding-usd', route('admin.loans.index')),
            $this->line('Negative Money Box', $users->owing, null, 'members who owe the company', 'las la-user-minus', route('admin.users.all')),
        ];

        return [
            'members' => ['lines' => $toMembers, 'total' => $this->total($toMembers)],
            'company' => ['lines' => $toCompany, 'total' => $this->total($toCompany)],
        ];
    }

    // ── Standing ──────────────────────────────────────────────────────────

    private function standing(array $range, float $income, float $outflow): array
    {
        $net    = round($income - $outflow, 2);
        $margin = $income > 0 ? round($net / $income * 100, 1) : null;

        // Growth: compare with the equally long period just before. For an
        // open-ended range, compare the last 30 days with the 30 before them.
        if ($range[0] && $range[1]) {
            $days     = $range[0]->diffInDays($range[1]) + 1;
            $current  = null;
            $previous = [$range[0]->copy()->subDays($days), $range[0]->copy()->subSecond()];
            $window   = $days . '-day period before';
        } else {
            $current  = [now()->subDays(29)->startOfDay(), now()->endOfDay()];
            $previous = [now()->subDays(59)->startOfDay(), now()->subDays(30)->endOfDay()];
            $window   = 'last 30 days vs the 30 days before';
        }

        $now  = $current ? $this->totals($current) : ['income' => $income, 'net' => $net];
        $prev = $this->totals($previous);

        return [
            'income'        => $income,
            'outflow'       => $outflow,
            'net'           => $net,
            'margin'        => $margin,
            'verdict'       => $net > 0 ? 'profit' : ($net < 0 ? 'loss' : 'even'),
            'compare_label' => $window,
            'income_change' => $this->change($now['income'], $prev['income']),
            'net_change'    => $this->change($now['net'], $prev['net']),
            'trend'         => $now['net'] > $prev['net'] ? 'up' : ($now['net'] < $prev['net'] ? 'down' : 'flat'),
            'previous_net'  => $prev['net'],
            'recent_net'    => $now['net'],
        ];
    }

    /** @return array{income: float, net: float} */
    private function totals(array $range): array
    {
        $members = $this->ledger('transactions', $range);
        $income  = $this->income($range, $members)['total'];
        $outflow = $this->outflow($range, $members, $this->ledger('stransactions', $range))['total'];

        return ['income' => $income, 'net' => round($income - $outflow, 2)];
    }

    private function change(float $now, float $before): ?float
    {
        return $before != 0.0 ? round(($now - $before) / abs($before) * 100, 1) : null;
    }

    // ── Unclassified ──────────────────────────────────────────────────────

    private function unclassified(Collection $members, Collection $stockists): array
    {
        $cfg   = config('financial_report');
        $known = array_merge(
            array_keys($cfg['member_payouts']),
            array_keys($cfg['stockist_payouts']),
            $cfg['ignore'],
        );

        $rows = [];
        foreach (['Members' => $members, 'Stockists' => $stockists] as $ledgerName => $ledger) {
            foreach ($ledger as $row) {
                if ($row->trx_type === '+' && !in_array((string) $row->remark, $known, true)) {
                    $rows[] = ['ledger' => $ledgerName, 'remark' => $row->remark ?: '(no remark)', 'amount' => (float) $row->total, 'count' => (int) $row->n];
                }
            }
        }

        return $rows;
    }

    // ── Helpers ───────────────────────────────────────────────────────────

    /** Sums per (trx_type, lowercased remark) for one ledger table within the range. */
    private function ledger(string $table, array $range): Collection
    {
        return $this->within(DB::table($table), 'created_at', $range)
            ->selectRaw('trx_type, LOWER(remark) AS remark, COUNT(*) AS n, COALESCE(SUM(amount), 0) AS total')
            ->groupBy('trx_type', DB::raw('LOWER(remark)'))
            ->get()
            ->keyBy(fn ($row) => $row->trx_type . '|' . $row->remark);
    }

    private function sum(Collection $ledger, string $type, string $remark): float
    {
        return (float) ($ledger->get($type . '|' . strtolower($remark))->total ?? 0);
    }

    private function count(Collection $ledger, string $type, string $remark): int
    {
        return (int) ($ledger->get($type . '|' . strtolower($remark))->n ?? 0);
    }

    private function within(Builder $query, string $column, array $range): Builder
    {
        return $query
            ->when($range[0], fn ($q, $from) => $q->where($column, '>=', $from))
            ->when($range[1], fn ($q, $to) => $q->where($column, '<=', $to));
    }

    /**
     * One report figure. $note is the card's subheading (e.g. "3 orders"),
     * $link the admin page that lists the records behind the figure.
     */
    private function line(string $label, $amount, ?int $count, ?string $note = null, string $icon = 'las la-coins', ?string $link = null): array
    {
        return ['label' => $label, 'amount' => round((float) $amount, 2), 'count' => $count, 'note' => $note, 'icon' => $icon, 'link' => $link];
    }

    private function plural($count, string $word): string
    {
        return number_format((int) $count) . ' ' . str($word)->plural((int) $count);
    }

    private function group(string $label, string $icon, array $lines): array
    {
        return ['label' => $label, 'icon' => $icon, 'lines' => $lines, 'total' => $this->total($lines)];
    }

    private function section(array $groups): array
    {
        return ['groups' => $groups, 'total' => round(array_sum(array_column($groups, 'total')), 2)];
    }

    private function total(array $lines): float
    {
        return round(array_sum(array_column($lines, 'amount')), 2);
    }

    private function periodLabel(array $range): string
    {
        if (!$range[0] && !$range[1]) {
            return 'All time';
        }

        return ($range[0]?->format('M d, Y') ?? 'Start') . ' – ' . ($range[1]?->format('M d, Y') ?? 'Today');
    }
}
