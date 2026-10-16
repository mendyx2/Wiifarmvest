<?php

namespace App\Http\Controllers\User;

use App\Constants\Status;
use App\Exceptions\InsufficientBalanceException;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Transfer;
use App\Models\User;
use App\Models\WelcomePackage;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

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
        $pageTitle             = 'Bonus Transfer';
        $user                  = $this->authUser();
        $hasPurchasedThisMonth = $this->hasMonthlyPurchase($user->id);
        $transfers             = Transfer::where('user_id', $user->id)->latest()->paginate(15);
        $isAcb                 = $user->isAcb();
        $bonusFields           = $this->visibleFields($isAcb);
        $totalTransferable     = $this->computeTotalTransferable($user, $hasPurchasedThisMonth);
        $welcomePackages       = WelcomePackage::where('user_id', $user->id)->latest()->get();

        return view('Template::user.bonus_transfer', compact(
            'pageTitle', 'user', 'bonusFields', 'hasPurchasedThisMonth', 'transfers',
            'totalTransferable', 'isAcb', 'welcomePackages'
        ));
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
            return back()
                ->withInput($request->only('bonus_field', 'amount'))
                ->withErrors(['password' => 'Incorrect password. Please try again.']);
        }

        if (!empty($config['requires_purchase']) && !$this->hasMonthlyPurchase($user->id)) {
            $notify[] = ['error', 'You need at least one purchase this month to transfer your Matching Bonus.'];
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
            return back()->withErrors(['password' => 'Incorrect password. Please try again.'])
                         ->with('reopen_all_modal', true);
        }

        $hasPurchase = $this->hasMonthlyPurchase($user->id);
        $isAcb       = $user->isAcb();

        $eligibleFields = array_filter($this->bonusFields, fn ($config) =>
            !(!empty($config['requires_purchase']) && !$hasPurchase)
            && !(!empty($config['requires_acb']) && !$isAcb)
        );

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

    public function checkPurchase()
    {
        $user  = $this->authUser();
        $has   = $this->hasMonthlyPurchase($user->id);
        $month = now()->format('F Y');

        $order = null;
        if ($has) {
            $order = Order::where('user_id', $user->id)
                ->where('status', '!=', Status::ORDER_CANCELED)
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->latest()
                ->select(['invoice_code', 'total_amount', 'created_at'])
                ->first();
        }

        return response()->json([
            'eligible' => $has,
            'month'    => $month,
            'message'  => $has
                ? "You have a qualifying purchase in {$month}. You can transfer your Matching Bonus."
                : "No qualifying purchase found for {$month}. Make at least one purchase this month to unlock Matching Bonus transfer.",
            'order'    => $order ? [
                'invoice' => $order->invoice_code,
                'amount'  => getAmount($order->total_amount),
                'date'    => $order->created_at->format('M d, Y'),
            ] : null,
        ]);
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

    private function hasMonthlyPurchase(int $userId): bool
    {
        return Order::where('user_id', $userId)
            ->where('status', '!=', Status::ORDER_CANCELED)
            ->whereMonth('created_at', now()->month)
            ->whereYear('created_at', now()->year)
            ->exists();
    }

    private function computeTotalTransferable(User $user, bool $hasPurchase): float
    {
        $isAcb = $user->isAcb();
        $total = 0.0;
        foreach ($this->bonusFields as $field => $config) {
            if (!empty($config['requires_purchase']) && !$hasPurchase) {
                continue;
            }
            if (!empty($config['requires_acb']) && !$isAcb) {
                continue;
            }
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
