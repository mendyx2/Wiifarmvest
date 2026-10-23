<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Exceptions\InsufficientBalanceException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transfer;
use App\Models\User;
use App\Models\WelcomePackage;
use App\Services\AutoshipService;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class BonusTransferController extends Controller
{
    private array $bonusFields = [
        'direct_bonus'   => ['label' => 'Direct Bonus',   'icon' => 'las la-user-check',     'color' => '#3B82F6', 'bg' => 'linear-gradient(135deg,#3B82F6,#1D4ED8)'],
        'indirect_bonus' => ['label' => 'Indirect Bonus', 'icon' => 'las la-users',           'color' => '#8B5CF6', 'bg' => 'linear-gradient(135deg,#8B5CF6,#6D28D9)'],
        'upgrade_bonus'  => ['label' => 'Upgrade Bonus',  'icon' => 'las la-arrow-circle-up', 'color' => '#F59E0B', 'bg' => 'linear-gradient(135deg,#F59E0B,#D97706)'],
        'unilevel_bonus' => ['label' => 'Unilevel Bonus', 'icon' => 'las la-layer-group',     'color' => '#10B981', 'bg' => 'linear-gradient(135deg,#10B981,#059669)'],
        'awards'         => ['label' => 'Awards',          'icon' => 'las la-trophy',          'color' => '#F97316', 'bg' => 'linear-gradient(135deg,#F97316,#EA580C)'],
        'matching_bonus'     => ['label' => 'Matching Bonus',     'icon' => 'las la-code-branch', 'color' => '#EC4899', 'bg' => 'linear-gradient(135deg,#EC4899,#BE185D)', 'requires_purchase' => false],
        'autoship'           => ['label' => 'Autoship',           'icon' => 'las la-sync-alt',     'color' => '#0891B2', 'bg' => 'linear-gradient(135deg,#0891B2,#0E7490)',  'requires_purchase' => true, 'is_autoship' => true],
        'repurchase_award'   => ['label' => 'Repurchase Award',   'icon' => 'las la-medal',       'color' => '#0D9488', 'bg' => 'linear-gradient(135deg,#0D9488,#0F766E)'],
        'key_in_bonus'       => ['label' => 'Key-In Bonus',       'icon' => 'las la-keyboard',    'color' => '#7C3AED', 'bg' => 'linear-gradient(135deg,#7C3AED,#5B21B6)'],
        'acb'                => ['label' => 'ACB Bonus',           'icon' => 'las la-star',        'color' => '#DC2626', 'bg' => 'linear-gradient(135deg,#DC2626,#991B1B)', 'requires_acb' => true],
        'affiliate_bonus'    => ['label' => 'Affiliate Bonus',    'icon' => 'las la-link',        'color' => '#059669', 'bg' => 'linear-gradient(135deg,#059669,#0D5C2E)'],
        'stockist_rebate'    => ['label' => 'Stockist Rebate',    'icon' => 'las la-store',       'color' => '#65A30D', 'bg' => 'linear-gradient(135deg,#65A30D,#4D7C0F)'],
    ];

    public function index()
    {
        $user      = $this->authUser();
        $autoship  = $this->autoshipStatus($user->id);
        $isAcb     = $user->isAcb();
        $eligible  = $this->eligibleFields($autoship['eligible'], $isAcb);

        // A row per bonus with money in it; ACB members always see their ACB row.
        $wallets = [];
        foreach ($this->visibleFields($isAcb) as $field => $config) {
            $balance = (float) ($user->$field ?? 0);
            if ($balance <= 0 && empty($config['requires_acb'])) {
                continue;
            }
            $wallets[] = [
                'field'   => $field,
                'label'   => $config['label'],
                'icon'    => $config['icon'],
                'kind'    => !empty($config['is_autoship']) ? 'autoship' : (!empty($config['requires_acb']) ? 'acb' : 'bonus'),
                'balance' => showAmount($balance),
                'amount'  => round($balance, 2),
                'locked'  => !isset($eligible[$field]),
            ];
        }

        $summary = collect($wallets)->filter(fn ($w) => $w['amount'] > 0)->map(fn ($w) => [
            'label'   => $w['label'],
            'balance' => $w['balance'],
            'skipped' => $w['locked'],
        ])->values();

        return Inertia::render('User/BonusTransfer', [
            'moneyBox'          => showAmount($user->balance),
            'totalTransferable' => showAmount($this->computeTotalTransferable($user, $autoship['eligible'])),
            'canTransferAll'    => $this->computeTotalTransferable($user, $autoship['eligible']) > 0,
            'wallets'           => $wallets,
            'summary'           => $summary,
            'autoship'          => [
                'eligible' => $autoship['eligible'],
                'pv'       => $autoship['pv'],
                'minPv'    => AutoshipService::MIN_PV,
                'month'    => now()->format('F Y'),
                'balance'  => showAmount($user->autoship ?? 0),
                'order'    => $autoship['order'],
            ],
            'welcomePackages'   => WelcomePackage::where('user_id', $user->id)->latest()->get()->map(fn ($wp) => [
                'code'       => $wp->code,
                'title'      => ucfirst($wp->source) . ' Welcome Package',
                'amount'     => showAmount($wp->amount),
                'redeemed'   => $wp->isRedeemed(),
                'redeemedAt' => $wp->redeemed_at?->format('M d, Y'),
            ])->values(),
            'transfers'         => Transfer::where('user_id', $user->id)->latest()->paginate(15)->through(fn (Transfer $t) => [
                'id'     => $t->id,
                'source' => $t->source_label,
                'amount' => showAmount($t->amount),
                'status' => $t->status_text,
                'date'   => $t->created_at?->format('M d, Y'),
                'time'   => $t->created_at?->format('h:i A'),
            ]),
            'urls'              => [
                'transfer'    => route('user.bonus.transfer.submit'),
                'transferAll' => route('user.bonus.transfer.all'),
                'shop'        => route('user.products'),
            ],
        ])->withViewData(['pageTitle' => 'Bonus Transfer']);
    }

    public function transfer(Request $request)
    {
        $request->validate([
            'bonus_field' => 'required|in:' . implode(',', array_keys($this->bonusFields)),
            'amount'      => 'required|numeric|min:0.01',
            'password'    => 'required|string',
        ]);

        $user   = $this->authUser();
        $field  = $request->bonus_field;
        $amount = (float) $request->amount;
        $config = $this->bonusFields[$field];

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        // Autoship unlocks with MIN_PV of products bought this month, the rule the
        // matching bonus applies when it holds the 20% back (MatchingBonusService).
        // This used to accept any order at all, and its message named the Matching Bonus.
        if (!empty($config['requires_purchase']) && !$this->autoshipStatus($user->id)['eligible']) {
            $notify[] = ['error', 'Buy ' . AutoshipService::MIN_PV . ' PV of products this month to transfer your ' . $config['label'] . '.'];
            return back()->withNotify($notify);
        }

        // Same eligibility as transferAll(): the ACB bonus is for ACB members only
        if (!empty($config['requires_acb']) && !$user->isAcb()) {
            $notify[] = ['error', $config['label'] . ' can only be transferred by ACB members.'];
            return back()->withNotify($notify);
        }

        try {
            DB::transaction(function () use ($user, $field, $amount, $config) {
                $this->moveToMoneyBox($user, $field, $amount, $config['label']);
            });
        } catch (InsufficientBalanceException) {
            $notify[] = ['error', 'Insufficient ' . $config['label'] . ' balance.'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', getAmount($amount) . ' transferred from ' . $config['label'] . ' to your Money Box.'];
        return back()->withNotify($notify);
    }

    public function transferAll(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $user = $this->authUser();

        if (!Hash::check($request->password, $user->password)) {
            return back()->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        $eligibleFields = $this->eligibleFields($this->autoshipStatus($user->id)['eligible'], $user->isAcb());

        $total = DB::transaction(function () use ($user, $eligibleFields) {
            // Amounts come from the locked row, not the copy loaded at the start
            // of the request — two parallel "Transfer All" clicks used to credit
            // the full bonus twice and leave the bonus columns negative
            $locked = User::lockForUpdate()->findOrFail($user->id);
            $total  = 0.0;

            foreach ($eligibleFields as $field => $config) {
                $amount = (float) ($locked->$field ?? 0);
                if ($amount <= 0) {
                    continue;
                }

                // One ledger entry per source so the audit trail is unambiguous
                $this->moveToMoneyBox($user, $field, $amount, $config['label']);
                $total += $amount;
            }

            return $total;
        });

        if ($total <= 0) {
            $notify[] = ['warning', 'No eligible bonus balance to transfer.'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', getAmount($total) . ' total transferred to your Money Box.'];
        return back()->withNotify($notify);
    }

    /** @return User */
    private function authUser(): User
    {
        return auth()->user();
    }

    /**
     * Move one bonus into the Money Box and record it in the transfer history.
     * Call inside a DB transaction so the history row commits with the money.
     *
     * @throws InsufficientBalanceException
     */
    private function moveToMoneyBox(User $user, string $field, float $amount, string $label): void
    {
        app(WalletService::class)->transferBonus(
            $user,
            $field,
            $amount,
            'Bonus Transfer from ' . $label,
            ledger: ['bonus_type' => 6],
        );

        Transfer::create([
            'user_id'      => $user->id,
            'source_field' => $field,
            'source_label' => $label,
            'amount'       => $amount,
            'status'       => Transfer::STATUS_COMPLETED,
        ]);
    }

    /**
     * Autoship status for this month: PV bought, whether it unlocks the
     * transfer, and the latest qualifying order.
     *
     * @return array{eligible: bool, pv: float, order: ?array}
     */
    private function autoshipStatus(int $userId): array
    {
        $pv = app(AutoshipService::class)->monthlyPv($userId);

        $order = Order::where('user_id', $userId)
            ->where('status', '!=', Status::ORDER_CANCELED)
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->latest()
            ->first(['invoice_code', 'created_at']);

        return [
            'eligible' => $pv >= AutoshipService::MIN_PV,
            'pv'       => $pv + 0,
            'order'    => $order ? ['invoice' => $order->invoice_code, 'date' => $order->created_at?->format('M d, Y')] : null,
        ];
    }

    /** The bonus fields this member may move right now. */
    private function eligibleFields(bool $autoshipUnlocked, bool $isAcb): array
    {
        return array_filter($this->bonusFields, fn ($config) =>
            !(!empty($config['requires_purchase']) && !$autoshipUnlocked)
            && !(!empty($config['requires_acb']) && !$isAcb)
        );
    }

    private function computeTotalTransferable(User $user, bool $autoshipUnlocked): float
    {
        $total = 0.0;
        foreach ($this->eligibleFields($autoshipUnlocked, $user->isAcb()) as $field => $config) {
            $total += (float) ($user->$field ?? 0);
        }
        return $total;
    }

    private function visibleFields(bool $isAcb): array
    {
        return array_filter(
            $this->bonusFields,
            fn($cfg) => !(!$isAcb && !empty($cfg['requires_acb']))
        );
    }
}
