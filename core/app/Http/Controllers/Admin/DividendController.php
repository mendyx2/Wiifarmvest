<?php

namespace App\Http\Controllers\Admin;

use App\Models\DividendBatch;
use App\Models\Rinvestment;
use App\Models\Shtransaction;
use App\Models\User;
use App\Models\Plan;
use App\Jobs\ProcessDividendPayment;
use App\Jobs\ProcessSharePurchase;
use App\Jobs\ReverseDividendPayment;
use Carbon\Carbon;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class DividendController extends Controller
{
    /**
     * List all pending dividend batches
     */
    public function index()
    {
        $pageTitle = 'Dividend Payments - Pending';
        $batches = DividendBatch::where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->paginate(getPaginate());

        $totalShareholders = User::whereHas('rinvestment', function ($query) {
            $query->where('status', 1);
        })->count();

        $totalUnits = Rinvestment::where('status', 1)->sum('units');
        $totalDistributed = Shtransaction::where('status', 'completed')->sum('amount');

        $users = User::where('balance', '>', 0)
            ->select('id', 'firstname', 'lastname', 'username', 'balance')
            ->orderBy('username')
            ->get();

        $plans = Plan::where('status', 1)
            ->select('id', 'name', 'price')
            ->orderBy('name')
            ->get();

        $shareUsers = $users->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'name' => trim($user->firstname . ' ' . $user->lastname),
                'balance' => $user->balance,
            ];
        });

        $sharePlans = $plans->map(function ($plan) {
            return [
                'id' => $plan->id,
                'name' => $plan->name,
                'price' => $plan->price,
            ];
        });

        return view('admin.dividend.index', compact(
            'pageTitle',
            'batches',
            'totalShareholders',
            'totalUnits',
            'totalDistributed',
            'users',
            'plans',
            'shareUsers',
            'sharePlans'
        ));
    }

    /**
     * Queue a share purchase job for a user
     */
    public function buyShares(Request $request)
    {
        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'plan_id' => 'required|exists:plans,id',
            'units' => 'required|integer|min:1',
        ]);

        $user = User::findOrFail($validated['user_id']);
        $plan = Plan::findOrFail($validated['plan_id']);
        $units = (int) $validated['units'];
        $totalCost = $plan->price * $units;

        if ($user->balance < $totalCost) {
            return response()->json([
                'message' => 'Insufficient balance for this purchase.',
            ], 422);
        }

        ProcessSharePurchase::dispatch(
            $user->id,
            $plan->id,
            $units,
            $plan->price
        )->onQueue('share-purchases');

        return response()->json([
            'success' => 'Share purchase has been queued successfully.',
        ]);
    }

    /**
     * Show form to create new dividend payment
     */
    public function create()
    {
        $pageTitle = 'Create Dividend Payment';

        // Get all users with investments
        $investors = User::whereHas('rinvestment', function($query) {
            $query->where('status', 1); // Active investments
        })
        ->with(['rinvestment' => function($q) {
            $q->where('status', 1);
        }])
        ->paginate(getPaginate(10));

        // Get all active plans
        $plans = Plan::where('status', 1)->get();

        return view('admin.dividend.create', compact('pageTitle', 'investors', 'plans'));
    }

    /**
     * Store new dividend batch and dispatch job
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'amount_per_unit' => 'required|numeric|min:0.01',
            'scope' => 'required|in:all-time,date-range',
            'start_date' => 'required_if:scope,date-range|nullable|date',
            'end_date' => 'required_if:scope,date-range|nullable|date|after_or_equal:start_date',
            'plan_id' => 'nullable|exists:plans,id',
            'notes' => 'nullable|string|max:1000',
        ]);

        // Idempotency guard: block duplicate submissions within 2 minutes
        $adminId = auth()->guard('admin')->user()->id;
        $recentBatch = DividendBatch::where('created_by_id', $adminId)
            ->where('amount_per_unit', $validated['amount_per_unit'])
            ->where('scope', $validated['scope'])
            ->where('plan_id', $validated['plan_id'] ?? null)
            ->where('created_at', '>=', now()->subMinutes(2))
            ->first();

        if ($recentBatch) {
            return redirect()->route('admin.dividend.detail', $recentBatch->id)
                ->with('warning', 'A similar dividend batch was already submitted recently. Showing the existing batch to avoid duplicates.');
        }

        // Calculate affected investments
        $query = Rinvestment::where('status', 1);

        if ($validated['scope'] == 'date-range') {
            $query->whereBetween('created_at', [
                Carbon::parse($validated['start_date'])->startOfDay(),
                Carbon::parse($validated['end_date'])->endOfDay(),
            ]);
        }

        if (!empty($validated['plan_id'])) {
            $query->where('plan_id', $validated['plan_id']);
        }

        $investments = $query->get();

        if ($investments->isEmpty()) {
            return redirect()->back()->with('error', 'No investments found for the selected criteria.');
        }

        // Calculate totals
        $totalUnits = $investments->sum('units');
        $totalAmount = $totalUnits * $validated['amount_per_unit'];
        $totalUsers = $investments->pluck('user_id')->unique()->count();

        // Create batch
        $batch = DividendBatch::create([
            'created_by_id' => auth()->guard('admin')->user()->id,
            'amount_per_unit' => $validated['amount_per_unit'],
            'scope' => $validated['scope'],
            'start_date' => $validated['scope'] == 'date-range' ? $validated['start_date'] : null,
            'end_date' => $validated['scope'] == 'date-range' ? $validated['end_date'] : null,
            'plan_id' => $validated['plan_id'] ?? null,
            'status' => 'pending',
            'total_users' => $totalUsers,
            'total_units' => $totalUnits,
            'total_amount' => $totalAmount,
            'notes' => $validated['notes'] ?? null,
        ]);

        // Dispatch job to process dividend payments
        ProcessDividendPayment::dispatch($batch);

        return redirect()->route('admin.dividend.detail', $batch->id)
            ->with('success', "Dividend batch created successfully. {$totalUsers} users with {$totalUnits} units will receive ₦" . number_format($totalAmount, 2));
    }

    /**
     * Show dividend batch details
     */
    public function detail($batchId)
    {
        $batch = DividendBatch::findOrFail($batchId);
        $pageTitle = "Dividend Batch #{$batch->id}";
        $transactions = $batch->shtransactions()->paginate(getPaginate());

        return view('admin.dividend.detail', compact('pageTitle', 'batch', 'transactions'));
    }

    /**
     * Search transactions by username (AJAX)
     */
    public function searchTransactions($batchId, Request $request)
    {
        $batch = DividendBatch::findOrFail($batchId);
        $search = $request->query('search', '');

        $transactions = $batch->shtransactions()
            ->whereHas('user', function ($query) use ($search) {
                $query->where('username', 'like', "%{$search}%");
            })
            ->with('user', 'rinvestment')
            ->paginate(getPaginate());

        if ($request->ajax()) {
            $html = view('admin.dividend.partials.transaction-rows', compact('transactions'))->render();
            return response()->json([
                'html' => $html,
                'hasPages' => $transactions->hasPages(),
                'pagination' => $transactions->links('vendor.pagination.bootstrap-4')->render()
            ]);
        }

        return response()->json(['error' => 'Invalid request'], 400);
    }
 
    /**
     * Cancel pending dividend batch
     */
    public function cancel($batchId)
    {
        $batch = DividendBatch::findOrFail($batchId);

        if (!$batch->canCancel()) {
            return redirect()->back()->with('error', 'This batch cannot be cancelled. It may already be processing or completed.');
        }

        $batch->cancel();

        return redirect()->route('admin.dividend.history')
            ->with('success', 'Dividend batch cancelled successfully.');
    }

    /**
     * Reverse a completed dividend batch
     */ 
    public function reverse($batchId)
    {
        $batch = DividendBatch::findOrFail($batchId);
 
        if (!$batch->canReverse()) {
            return redirect()->back()->with('error', 'This batch cannot be reversed. Only completed batches can be reversed.');
        }

        $adminId = auth()->guard('admin')->user()->id;

        ReverseDividendPayment::dispatch($batch, $adminId);

        return redirect()->route('admin.dividend.history')
            ->with('success', "Reversal for batch #{$batch->id} has been queued. User balances will be adjusted shortly.");
    }

    /**
     * View dividend payment history
     */
    public function history()
    {
        $pageTitle = 'Dividend Payment History';
        $batches = DividendBatch::whereIn('status', ['completed', 'cancelled', 'failed'])
            ->orderBy('created_at', 'desc')
            ->paginate(getPaginate()); 

        return view('admin.dividend.history', compact('pageTitle', 'batches'));
    }

    /**
     * List all investors with their shares and dividend history
     */
    public function investors(Request $request)
    {
        $pageTitle = 'FFO Investors - Shares & Dividends';

        $search = $request->query('search');

        $investorsQuery = User::whereHas('rinvestment', function($query) {
            $query->where('status', 1);
        });

        if ($search) {
            $investorsQuery->where(function ($q) use ($search) {
                $q->where('username', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('firstname', 'like', "%{$search}%")
                    ->orWhere('lastname', 'like', "%{$search}%");
            });
        }

        $investors = $investorsQuery
            ->with(['rinvestment' => function($q) {
                $q->where('status', 1);
            }])
            ->select('id', 'firstname', 'lastname', 'username', 'email', 'shares')
            ->paginate(getPaginate());

        if ($request->ajax()) {
            $html = view('admin.dividend.partials.investor-rows', compact('investors'))->render();
            return response()->json(['html' => $html, 'hasPages' => $investors->hasPages(), 'pagination' => $investors->links('vendor.pagination.bootstrap-4')->render()]);
        }

        return view('admin.dividend.investors', compact('pageTitle', 'investors'));
    }

    /**
     * View dividends for a specific user
     */
    public function userDividends($userId)
    {
        $user = User::findOrFail($userId);
        $pageTitle = "Dividends for {$user->fullname}";

        $dividends = $user->shtransactions()
            ->with('dividendBatch', 'rinvestment.plan')
            ->orderBy('created_at', 'desc')
            ->paginate(getPaginate());

        $totalDividends = $user->shtransactions()
            ->where('status', 'completed')
            ->sum('amount');

        return view('admin.dividend.user-dividends', compact('pageTitle', 'user', 'dividends', 'totalDividends'));
    }

    /**
     * Helper: Common dividend data retrieval
     */
    protected function dividendData($scope = null, $userId = null)
    {
        $batches = DividendBatch::query();

        if ($scope) {
            $batches->where('status', $scope);
        }

        if ($userId) {
            $batches->whereHas('shtransactions', function($query) use ($userId) {
                $query->where('user_id', $userId);
            });
        }

        return $batches->orderBy('created_at', 'desc');
    }
}
