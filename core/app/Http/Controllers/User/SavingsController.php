<?php

namespace App\Http\Controllers\User;

use App\Models\Transaction;
use App\Models\SavingsProduct;
use App\Models\SavingsTransaction;
use App\Models\SavingsSetting;
use App\Models\FarmCycle;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class SavingsController extends Controller
{
    public function index() 
    {
        $pageTitle     = 'My Savings';
        $user          = auth()->user();
        $activeSavings = SavingsProduct::where('user_id', $user->id)
                            ->whereIn('status', ['active', 'matured'])
                            ->latest()
                            ->get();
        $matureCount   = $activeSavings->where('status', 'matured')->count();
        $totalSaved    = $activeSavings->sum('principal');
        $totalInterest = $activeSavings->sum('interest_earned');

        return view(activeTemplate() . 'user.savings.index', compact(
            'pageTitle', 'user', 'activeSavings', 'matureCount', 'totalSaved', 'totalInterest'
        ));
    }

    public function create(Request $request)
    {
        $pageTitle      = 'Create Savings';
        $farmCycles     = FarmCycle::where('status', 'open')->latest()->get();
        $savingsSettings = SavingsSetting::active()
                            ->orderByRaw("FIELD(type,'target','fixed','farm')")
                            ->orderBy('duration_months')
                            ->get()
                            ->keyBy(fn($s) => $s->type . ($s->duration_months ? '_' . $s->duration_months : ''));

        return view(activeTemplate() . 'user.savings.create', compact('pageTitle', 'farmCycles', 'savingsSettings'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'type' => 'required|in:target,fixed,farm',
        ]);

        $type   = $request->type;
        $amount = 0;

        // Enforce admin-configured availability
        $typeActive = SavingsSetting::where('type', $type)->where('is_active', true)->exists();
        if (!$typeActive) {
            $notify[] = ['error', ucfirst($type) . 'The saving product is currently unavailable for new subscriptions.'];
            return back()->withNotify($notify)->withInput();
        }

        if ($type == 'target') {
            $targetMin = SavingsSetting::minAmountFor('target');
            $request->validate([
                'name'            => 'required|string|max:60',
                'target_amount'   => 'required|numeric|min:1',
                'frequency'       => 'required|in:weekly,monthly',
                'duration'        => 'required|integer|min:1',
                'start_date'      => 'required|date|after_or_equal:today',
                'end_date'        => 'required|date|after:start_date',
                'initial_funding' => 'required|numeric|min:' . $targetMin . '|max:' . $user->balance,
            ]);
            $amount = (float) $request->initial_funding;
        } elseif ($type == 'fixed') {
            $dur    = (int) $request->duration;

            $durActive = SavingsSetting::where('type', 'fixed')
                ->where('duration_months', $dur)
                ->where('is_active', true)
                ->exists();
                //dd($durActive);
            if (!$durActive) {
                $notify[] = ['error', 'The ' . $dur . '-month Fixed Box is currently unavailable.'];
                return back()->withNotify($notify)->withInput();
            }
            $minAmt = SavingsSetting::minAmountFor('fixed', $dur);
            $request->validate([
                'amount'   => 'required|numeric|min:' . $minAmt . '|max:' . $user->balance,
                'duration' => 'required|in:1,3,6,12',
            ]);
            $amount = (float) $request->amount;
        } elseif ($type == 'farm') {
            $farmMin = SavingsSetting::minAmountFor('farm');
            $request->validate([
                'farm_cycle_id' => 'required|exists:farm_cycles,id',
                'amount'        => 'required|numeric|min:' . $farmMin . '|max:' . $user->balance,
            ]);
            $amount = (float) $request->amount;
        }

        // Ensure sufficient Money Box balance (pre-flight; re-checked under lock below)
        if ($user->balance < $amount) {
            $notify[] = ['error', 'Insufficient Money Box balance.'];
            return back()->withNotify($notify)->withInput();
        }

        try { DB::transaction(function () use ($user, $request, $type, $amount) {

            // Re-fetch user under a row lock to prevent concurrent double-spend
            $user = User::lockForUpdate()->findOrFail($user->id);

            if ($user->balance < $amount) {
                throw new \RuntimeException('Insufficient Money Box balance.');
            }

            // ── Build savings product ────────────────────────────────
            $saving             = new SavingsProduct();
            $saving->user_id    = $user->id;
            $saving->type       = $type;
            $saving->reference  = $this->generateReference();
            $saving->principal  = $amount;
            $saving->balance    = $amount;
            $saving->status     = 'active';

            if ($type == 'target') {
                $rate                          = SavingsSetting::rateFor('target');
                $saving->name                  = $request->name;
                $saving->target_amount         = $request->target_amount;
                $saving->frequency             = $request->frequency;
                $saving->duration_cycles       = $request->duration;
                $saving->contribution_per_cycle = round($request->target_amount / $request->duration, 2);
                $saving->start_date            = $request->start_date;
                $saving->maturity_date         = $request->end_date;
                $saving->interest_rate         = $rate;
                $saving->next_due_date         = $request->start_date;

            } elseif ($type == 'fixed') {
                $dur                   = (int) $request->duration;
                $rate                  = SavingsSetting::rateFor('fixed', $dur);
                $saving->name          = 'Fixed Box — ' . $dur . ' ' . ($dur == 1 ? 'month' : 'months');
                $saving->start_date    = now()->toDateString();
                $saving->maturity_date = now()->addMonths($dur)->toDateString();
                $saving->interest_rate = $rate;
                $saving->duration_cycles = $dur;
                $saving->target_amount = $amount;

            } elseif ($type == 'farm') {
                $rate                  = SavingsSetting::rateFor('farm');
                $farmCycle             = FarmCycle::find($request->farm_cycle_id);
                $saving->name          = 'Farm Yield Savings';
                $saving->farm_cycle_id = $request->farm_cycle_id;
                $saving->start_date    = now()->toDateString();
                $saving->maturity_date = $farmCycle?->maturity_date?->toDateString()
                                            ?? now()->addMonths(6)->toDateString();
                $saving->interest_rate = $rate;
                $saving->target_amount = $amount;
            }

            // Project interest
            $years = $saving->start_date && $saving->maturity_date
                ? now()->parse($saving->start_date)->diffInDays($saving->maturity_date) / 365
                : 0;
            $saving->interest_earned = round($amount * ($saving->interest_rate / 100) * $years, 2);

            $saving->save();

            // ── Deduct from Money Box (balance) ──────────────────────
            $balanceBefore  = $user->balance;
            $user->balance -= $amount;

            // Track total locked across all savings in savings_wallet
            $user->savings_wallet += $amount;
            $user->save();

            // ── Record in transactions table ─────────────────────────
            $transaction                = new Transaction();
            $transaction->user_id       = $user->id;
            $transaction->amount        = $amount;
            $transaction->post_balance  = $user->balance;
            $transaction->charge        = 0;
            $transaction->trx_type      = '-';
            $transaction->details       = ucfirst($type) . ' savings created: ' . $saving->name;
            $transaction->trx           = $saving->reference;
            $transaction->remark        = 'savings_lock';
            $transaction->save();

            // ── Savings audit trail (double-entry) ───────────────────
            $stxRef      = getTrx();
            $description = ucfirst($type) . ' savings created: ' . $saving->name;
            $meta        = ['savings_reference' => $saving->reference, 'type' => $type];

            // Debit side — money leaving Money Box
            SavingsTransaction::create([
                'user_id'            => $user->id,
                'wallet_type'        => 'balance',
                'savings_product_id' => $saving->id,
                'type'               => 'debit',
                'amount'             => $amount,
                'source'             => 'product_creation',
                'description'        => $description,
                'reference'          => $stxRef . '-BAL',
                'balance_before'     => $balanceBefore,
                'balance_after'      => $user->balance,
                'status'             => 'completed',
                'metadata'           => $meta,
            ]);

            // Credit side — money entering the savings product
            SavingsTransaction::create([
                'user_id'            => $user->id,
                'wallet_type'        => 'savings_product',
                'savings_product_id' => $saving->id,
                'type'               => 'credit',
                'amount'             => $amount,
                'source'             => 'product_creation',
                'description'        => $description,
                'reference'          => $stxRef . '-SAV',
                'balance_before'     => 0,
                'balance_after'      => $saving->balance,
                'status'             => 'completed',
                'metadata'           => $meta,
            ]);
        }); } catch (\RuntimeException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify)->withInput();
        }

        $notify[] = ['success', 'Savings product created successfully.'];
        return redirect()->route('user.savings.index')->withNotify($notify);
    }

    public function show($id)
    {
        $pageTitle = 'Savings Detail';
        $saving    = SavingsProduct::where('user_id', auth()->id())->find($id);
    
        if (!$saving) {
            $notify[] = ['error', 'Savings product not found.'];
            return redirect()->route('user.savings.index')->withNotify($notify);
        }

        $txns = Transaction::where('user_id', auth()->id())
                    ->where('trx', $saving->reference)
                    ->orWhere(function ($q) use ($saving) {
                        $q->where('user_id', auth()->id())
                          ->where('remark', 'savings_lock')
                          ->where('details', 'like', '%' . $saving->name . '%');
                    })
                    ->latest()
                    ->get();

        return view(activeTemplate() . 'user.savings.show', compact('pageTitle', 'saving', 'txns'));
    }

    public function addFunds(Request $request, $id)
    {
        $user   = auth()->user();
        $saving = SavingsProduct::where('user_id', $user->id)
                    ->where('status', 'active')
                    ->findOrFail($id);

        // Target savings: block once target is reached, and prevent partial overshoot
        if ($saving->type == 'target' && $saving->target_amount > 0) {
            if ($saving->balance >= $saving->target_amount) {
                $notify[] = ['error', 'Target amount has already been reached. No more funds can be added.'];
                return back()->withNotify($notify);
            }
            $remaining = $saving->target_amount - $saving->balance;
            if ((float) $request->amount > $remaining) {
                $notify[] = ['error', 'Amount exceeds the remaining target. You can add at most ' . showAmount($remaining) . '.'];
                return back()->withNotify($notify);
            }
        }

        // Farm savings: block contributions when cycle is no longer open
        if ($saving->type == 'farm') {
            $cycle = $saving->farmCycle;
            if (!$cycle || $cycle->status != 'open') {
                $status = $cycle ? ucfirst($cycle->status) : 'unavailable';
                $notify[] = ['error', "This farm cycle is {$status} and no longer accepting contributions."];
                return back()->withNotify($notify);
            }
        }

        $request->validate([
            'amount' => 'required|numeric|min:1|max:' . $user->balance,
        ]);

        $amount = (float) $request->amount;

        if ($user->balance < $amount) {
            $notify[] = ['error', 'Insufficient Money Box balance.'];
            return back()->withNotify($notify);
        }

        $savingId = $saving->id;

        try { DB::transaction(function () use ($user, $savingId, $amount) {
            // Re-fetch both records under locks to prevent concurrent double-spend
            $user   = User::lockForUpdate()->findOrFail($user->id);
            $saving = SavingsProduct::where('user_id', $user->id)
                        ->where('status', 'active')
                        ->lockForUpdate()
                        ->findOrFail($savingId);

            if ($user->balance < $amount) {
                throw new \RuntimeException('Insufficient Money Box balance.');
            }

            $balanceBefore        = $user->balance;
            $savingBalanceBefore  = $saving->balance;

            $saving->balance   += $amount;
            $saving->principal += $amount;
            $saving->save();

            $user->balance        -= $amount;
            $user->savings_wallet += $amount;
            $user->save();

            $txnRef      = getTrx();
            $description = 'Added funds to savings: ' . $saving->name;
            $meta        = ['savings_reference' => $saving->reference];

            $transaction               = new Transaction();
            $transaction->user_id      = $user->id;
            $transaction->amount       = $amount;
            $transaction->post_balance = $user->balance;
            $transaction->charge       = 0;
            $transaction->trx_type     = '-';
            $transaction->details      = $description;
            $transaction->trx          = $txnRef;
            $transaction->remark       = 'savings_lock';
            $transaction->save();

            // ── Savings audit trail (double-entry) ───────────────────
            $stxRef = getTrx();

            // Debit side — money leaving Money Box
            SavingsTransaction::create([
                'user_id'            => $user->id,
                'wallet_type'        => 'balance',
                'savings_product_id' => $saving->id,
                'type'               => 'debit',
                'amount'             => $amount,
                'source'             => 'add_funds',
                'description'        => $description,
                'reference'          => $stxRef . '-BAL',
                'balance_before'     => $balanceBefore,
                'balance_after'      => $user->balance,
                'status'             => 'completed',
                'metadata'           => $meta,
            ]);

            // Credit side — money entering the savings product
            SavingsTransaction::create([
                'user_id'            => $user->id,
                'wallet_type'        => 'savings_product',
                'savings_product_id' => $saving->id,
                'type'               => 'credit',
                'amount'             => $amount,
                'source'             => 'add_funds',
                'description'        => $description,
                'reference'          => $stxRef . '-SAV',
                'balance_before'     => $savingBalanceBefore,
                'balance_after'      => $saving->balance,
                'status'             => 'completed',
                'metadata'           => $meta,
            ]);
        }); } catch (\RuntimeException $e) {
            $notify[] = ['error', $e->getMessage()];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', 'Funds added to your savings product successfully.'];
        return back()->withNotify($notify);
    }

    public function withdraw(Request $request, $id)
    {
        $user   = auth()->user();
        $saving = SavingsProduct::where('user_id', $user->id)
                    ->where('status', 'matured')
                    ->findOrFail($id);

        $savingId = $saving->id;

        DB::transaction(function () use ($user, $savingId) {
            // Lock both rows — withdraw can race with auto-maturity jobs
            $user   = User::lockForUpdate()->findOrFail($user->id);
            $saving = SavingsProduct::where('user_id', $user->id)
                        ->where('status', 'matured')
                        ->lockForUpdate()
                        ->findOrFail($savingId);

            $total               = $saving->balance + $saving->interest_earned;
            $balanceBefore       = $user->balance;
            $savingBalanceBefore = $saving->balance;

            // Release to Money Box
            $user->balance        += $total;
            $user->savings_wallet  = max(0, $user->savings_wallet - $saving->principal);
            $user->save();

            // Close product
            $saving->status    = 'closed';
            $saving->closed_at = now();
            $saving->save();

            $txnRef      = getTrx();
            $description = 'Savings maturity withdrawal: ' . $saving->name;
            $meta        = ['savings_reference' => $saving->reference, 'interest_earned' => $saving->interest_earned];

            // Record in generic transactions table
            $transaction               = new Transaction();
            $transaction->user_id      = $user->id;
            $transaction->amount       = $total;
            $transaction->post_balance = $user->balance;
            $transaction->charge       = 0;
            $transaction->trx_type     = '+';
            $transaction->details      = $description;
            $transaction->trx          = $txnRef;
            $transaction->remark       = 'savings_withdrawal';
            $transaction->save();

            // ── Savings audit trail (double-entry) ───────────────────
            $stxRef = getTrx();

            // Debit side — money leaving the savings product
            SavingsTransaction::create([
                'user_id'            => $user->id,
                'wallet_type'        => 'savings_product',
                'savings_product_id' => $saving->id,
                'type'               => 'debit',
                'amount'             => $total,
                'source'             => 'maturity_withdrawal',
                'description'        => $description,
                'reference'          => $stxRef . '-SAV',
                'balance_before'     => $savingBalanceBefore,
                'balance_after'      => 0,
                'status'             => 'completed',
                'metadata'           => $meta,
            ]);

            // Credit side — money entering Money Box
            SavingsTransaction::create([
                'user_id'            => $user->id,
                'wallet_type'        => 'balance',
                'savings_product_id' => $saving->id,
                'type'               => 'credit',
                'amount'             => $total,
                'source'             => 'maturity_withdrawal',
                'description'        => $description,
                'reference'          => $stxRef . '-BAL',
                'balance_before'     => $balanceBefore,
                'balance_after'      => $user->balance,
                'status'             => 'completed',
                'metadata'           => $meta,
            ]);
        });

        $notify[] = ['success', 'Savings withdrawn successfully to your Money Box.'];
        return redirect()->route('user.savings.index')->withNotify($notify);
    }

    public function history()
    {
        $pageTitle     = 'Savings History';
        $closedSavings = SavingsProduct::where('user_id', auth()->id())
                            ->whereIn('status', ['closed', 'matured'])
                            ->latest()
                            ->get();

        return view(activeTemplate() . 'user.savings.history', compact('pageTitle', 'closedSavings'));
    }

    private function generateReference(): string
    {
        $ref = 'SV-' . date('Y') . '-' . str_pad(
            SavingsProduct::whereYear('created_at', date('Y'))->count() + 1,
            5, '0', STR_PAD_LEFT
        );
        return SavingsProduct::where('reference', $ref)->exists()
            ? 'SV-' . date('Y') . '-' . getTrx()
            : $ref;
    }
}
