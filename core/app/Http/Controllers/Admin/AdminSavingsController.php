<?php

namespace App\Http\Controllers\Admin;

use App\Models\SavingsSetting;
use App\Models\SavingsProduct;
use App\Models\FarmCycle;
use App\Jobs\ProcessFarmCycleMaturity;
use App\Jobs\ProcessSavingsTransfer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class AdminSavingsController extends Controller
{
    /* ── Settings ─────────────────────────────────────── */

    public function settings()
    {
        $pageTitle = 'Savings Settings';
        $settings  = SavingsSetting::orderByRaw("FIELD(type,'target','fixed','farm')")
                        ->orderBy('duration_months')
                        ->get();

        return view('admin.savings.settings', compact('pageTitle', 'settings'));
    }

    public function storeSetting(Request $request)
    {
        $request->validate([
            'type'            => 'required|in:target,fixed,farm',
            'duration_months' => 'nullable|integer|min:1',
            'label'           => 'required|string|max:100',
            'interest_rate'   => 'required|numeric|min:0|max:100',
            'min_amount'      => 'required|numeric|min:0',
            'max_amount'      => 'nullable|numeric|min:0',
            'description'     => 'nullable|string|max:255',
            'is_active'       => 'nullable|boolean',
        ]);

        $duration = $request->type == 'fixed' ? $request->duration_months : null;

        $exists = SavingsSetting::where('type', $request->type)
                    ->where('duration_months', $duration)
                    ->exists();

        if ($exists) {
            $notify[] = ['error', 'A setting for this type and duration already exists.'];
            return back()->withNotify($notify);
        }

        SavingsSetting::create([
            'type'            => $request->type,
            'duration_months' => $duration,
            'label'           => $request->label,
            'interest_rate'   => $request->interest_rate,
            'min_amount'      => $request->min_amount,
            'max_amount'      => $request->max_amount ?: null,
            'description'     => $request->description,
            'is_active'       => $request->boolean('is_active', true),
        ]);

        $notify[] = ['success', 'New savings setting created successfully.'];
        return back()->withNotify($notify);
    }

    public function updateSetting(Request $request, $id)
    {
        $setting = SavingsSetting::findOrFail($id);
 
        $request->validate([
            'interest_rate' => 'required|numeric|min:0|max:100',
            'min_amount'    => 'required|numeric|min:0',
            'max_amount'    => 'nullable|numeric|min:0',
            'description'   => 'nullable|string|max:255',
            'is_active'     => 'nullable|boolean',
        ]);

        $setting->interest_rate = $request->interest_rate;
        $setting->min_amount    = $request->min_amount;
        $setting->max_amount    = $request->max_amount ?: null;
        $setting->description   = $request->description;
        $setting->is_active     = $request->boolean('is_active');
        $setting->save();

        $notify[] = ['success', $setting->label . ' settings updated successfully.'];
        return back()->withNotify($notify);
    }

    /* ── All user savings ─────────────────────────────── */

    public function index(Request $request)
    {
        $pageTitle = 'All Savings Products';
        $savings   = SavingsProduct::with('user')
                        ->when($request->type,   fn($q) => $q->where('type', $request->type))
                        ->when($request->status, fn($q) => $q->where('status', $request->status))
                        ->when($request->search, fn($q) => $q->whereHas('user', fn($u) =>
                            $u->where('username', 'like', '%'.$request->search.'%')
                              ->orWhere('email', 'like', '%'.$request->search.'%')
                        ))
                        ->latest()
                        ->paginate(getPaginate());

        $summary = SavingsProduct::selectRaw('
            COUNT(*) as total,
            SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) as active_count,
            SUM(CASE WHEN status = "matured" THEN 1 ELSE 0 END) as matured_count,
            SUM(principal) as total_principal,
            SUM(interest_earned) as total_interest
        ')->first();

        return view('admin.savings.index', compact('pageTitle', 'savings', 'summary'));
    }

    public function show($id)
    {
        $pageTitle = 'Savings Detail';
        $saving    = SavingsProduct::with('user')->findOrFail($id);
        return view('admin.savings.show', compact('pageTitle', 'saving'));
    }
  
    public function transferToBalance(Request $request, $id)
    {
        $saving = SavingsProduct::with('user')->findOrFail($id);

        if ($saving->status == 'closed') {
            return response()->json(['status' => 'error', 'message' => 'This savings product is already closed.'], 422);
        }

        if (!$saving->maturity_date || !$saving->maturity_date->isPast()) {
            $due = $saving->maturity_date ? $saving->maturity_date->format('M d, Y') : 'unknown';
            return response()->json(['status' => 'error', 'message' => "Maturity date ({$due}) has not been reached yet."], 422);
        }

        if (!$saving->user) {
            return response()->json(['status' => 'error', 'message' => 'User not found for this savings product.'], 422);
        }

        ProcessSavingsTransfer::dispatch($saving, auth()->id());
 
        $total = showAmount((float) $saving->balance + (float) $saving->interest_earned);

        return response()->json([
            'status'  => 'success',
            'message' => "Transfer queued. {$saving->user->username}'s Money Box will be credited with {$total} shortly.",
        ]);
    }

    /* ── Farm Cycles ──────────────────────────────────── */

    public function cycles()
    {
        $pageTitle = 'Farm Cycles';
        $cycles    = FarmCycle::latest()->paginate(getPaginate());
        return view('admin.savings.cycles', compact('pageTitle', 'cycles'));
    }

    public function storeCycle(Request $request, $id = 0)
    {
        $request->validate([
            'name'                   => 'required|string|max:100',
            'min_contribution'       => 'required|numeric|min:0',
            'max_contribution'       => 'required|numeric|min:0|gte:min_contribution',
            'min_yield'              => 'required|numeric|min:0|max:100',
            'max_yield'              => 'required|numeric|min:0|max:100|gte:min_yield',
            'subscription_opens_at'  => 'required|date',
            'maturity_date'          => 'required|date|after:subscription_opens_at',
            'actual_yield'           => 'nullable|numeric|min:0|max:100',
            'status'                 => 'required|in:open,closed',
        ]);

        $cycle = $id ? FarmCycle::findOrFail($id) : new FarmCycle();
        // Prevent editing a matured cycle's status
        if ($id && $cycle->status == 'matured') {
            $notify[] = ['error', 'Matured cycles cannot be edited.'];
            return back()->withNotify($notify);
        }

        $cycle->fill($request->only([
            'name', 'min_contribution', 'max_contribution',
            'min_yield', 'max_yield', 'actual_yield',
            'subscription_opens_at', 'maturity_date', 'status',
        ]));
        $cycle->save();

        $notify[] = ['success', 'Farm cycle ' . ($id ? 'updated' : 'created') . ' successfully.'];
        return back()->withNotify($notify);
    }

    public function closeCycle($id)
    {
        $cycle = FarmCycle::findOrFail($id);

        if ($cycle->status != 'open') {
            $notify[] = ['error', 'Only open cycles can be closed.'];
            return back()->withNotify($notify);
        }

        $cycle->status = 'closed';
        $cycle->save();

        $notify[] = ['success', 'Farm cycle closed. No new contributions will be accepted.'];
        return back()->withNotify($notify);
    }

    public function matureCycle(Request $request, $id)
    {
        $request->validate([
            'actual_yield' => 'required|numeric|min:0|max:100',
        ]);

        $error = null;
        $cycle = null;

        DB::transaction(function () use ($request, $id, &$cycle, &$error) {
            // Lock the row so concurrent admin clicks cannot both dispatch the job
            $cycle = FarmCycle::lockForUpdate()->findOrFail($id);

            if ($cycle->status != 'closed') {
                $error = 'Only closed cycles can be matured. Close the cycle first.';
                return;
            }

            if ($cycle->payout_processed) {
                $error = 'Payout has already been processed for this cycle.';
                return;
            }

            if (!$cycle->isMaturityDateReached()) {
                $error = 'Maturity date (' . $cycle->maturity_date->format('M d, Y') . ') has not been reached yet.';
                return;
            }

            // Mark as processed here (inside the lock) so concurrent clicks are
            // blocked before the job runs — the job also re-checks this flag.
            $cycle->actual_yield     = $request->actual_yield;
            $cycle->payout_processed = true;
            $cycle->save();
        });

        if ($error) {
            $notify[] = ['error', $error];
            return back()->withNotify($notify);
        }

        ProcessFarmCycleMaturity::dispatch($cycle);

        $count = $cycle->activeSavingsCount();
        $total = $cycle->activeSavingsTotal();

        $notify[] = ['success', "Maturity payout queued for {$count} savings accounts (total " . showAmount($total) . " principal). Users will be credited with principal + interest shortly."];
        return back()->withNotify($notify);
    }
}
