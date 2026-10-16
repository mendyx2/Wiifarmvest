<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Models\UserAward;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AwardController extends Controller
{
    public function index()
    {
        $pageTitle = 'Award Management';
        $awards    = Award::withCount('userAwards')->orderBy('sort_order')->get();

        return view('admin.awards.index', compact('pageTitle', 'awards'));
    }

    public function create()
    {
        $pageTitle = 'Add Award';
        $awards    = Award::orderBy('sort_order')->get();

        return view('admin.awards.form', compact('pageTitle', 'awards'));
    }

    public function store(Request $request)
    {
        $this->validateAward($request);

        $award = new Award();
        $request->merge(['prerequisite_award_id' => $request->prerequisite_award_id ?: null]);
        $award->fill($request->only([
            'name', 'description', 'required_total_pv', 'required_left_pv',
            'required_right_pv', 'prerequisite_award_id', 'payment_amount',
            'sort_order', 'status',
        ]));

        if ($request->hasFile('image')) {
            $award->image = fileUploader(
                $request->image,
                getFilePath('awards'),
                getFileSize('awards')
            );
        }

        $award->save();

        $notify[] = ['success', 'Award created successfully.'];
        return redirect()->route('admin.awards.index')->withNotify($notify);
    }

    public function edit(Award $award)
    {
        $pageTitle = 'Edit Award';
        $awards    = Award::orderBy('sort_order')->get();

        return view('admin.awards.form', compact('pageTitle', 'award', 'awards'));
    }

    public function update(Request $request, Award $award)
    {
        $this->validateAward($request, $award->id);

        $request->merge(['prerequisite_award_id' => $request->prerequisite_award_id ?: null]);
        $award->fill($request->only([
            'name', 'description', 'required_total_pv', 'required_left_pv',
            'required_right_pv', 'prerequisite_award_id', 'payment_amount',
            'sort_order', 'status',
        ]));

        if ($request->hasFile('image')) {
            $oldImage    = $award->image;
            $award->image = fileUploader(
                $request->image,
                getFilePath('awards'),
                getFileSize('awards'),
                $oldImage
            );
        }

        $award->save();

        $notify[] = ['success', 'Award updated successfully.'];
        return redirect()->route('admin.awards.index')->withNotify($notify);
    }

    public function destroy(Award $award)
    {
        if ($award->image) {
            fileManager()->removeFile(getFilePath('awards') . '/' . $award->image);
        }

        $award->delete();

        $notify[] = ['success', 'Award deleted successfully.'];
        return back()->withNotify($notify);
    }

    public function toggle(Award $award)
    {
        $award->status = $award->status ? 0 : 1;
        $award->save();

        return response()->json(['success' => true]);
    }
 
    public function payments(Request $request)
    {
        $pageTitle = 'Award Payments';
        $payments  = UserAward::with(['user', 'award'])
            ->when($request->status !== null && $request->status !== '', function ($q) use ($request) {
                $q->where('status', $request->status);
            })
            ->latest()
            ->paginate(20);

        return view('admin.awards.payments', compact('pageTitle', 'payments'));
    }

    public function pay(Request $request, UserAward $userAward)
    {
        $request->validate([
            'note' => 'nullable|string',
        ]);

        DB::transaction(function () use ($request, $userAward) {
            $award = $userAward->award;

            $userAward->user->increment('awards', $award->payment_amount);

            $userAward->update([
                'status'      => 1,
                'paid_at'     => now(),
                'paid_by'     => auth('admin')->id(),
                'paid_amount' => $award->payment_amount,
                'note'        => $request->note,
            ]);

            // Debit from isolated Member Compensation Reserve Ledger (Strategic Reserve pool)
            try {
                app(\App\Services\RevenueAllocationService::class)->disburseCompensation(
                    (float) $award->payment_amount,
                    getTrx(),
                    'Award payment for ' . $award->name . ' to ' . $userAward->user->username,
                    \App\Models\CompensationReserveLedger::SUB_POOL_SR,
                    'award_bonus',
                    $userAward->user->id
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[RevenueAllocation] Failed to debit award payment: ' . $e->getMessage());
            }
        });

        $notify[] = ['success', 'Award payment processed successfully.'];
        return back()->withNotify($notify);
    }

    private function validateAward(Request $request, ?int $excludeId = null): void
    {
        $request->validate([
            'name'                  => 'required|string|max:150|unique:awards,name' . ($excludeId ? ",{$excludeId}" : ''),
            'description'           => 'nullable|string',
            'required_total_pv'     => 'required|numeric|min:0',
            'required_left_pv'      => 'required|numeric|min:0',
            'required_right_pv'     => 'required|numeric|min:0',
            'prerequisite_award_id' => 'nullable|exists:awards,id',
            'payment_amount'        => 'required|numeric|min:0',
            'image'                 => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'sort_order'            => 'required|integer|min:0',
            'status'                => 'required|in:0,1',
        ]);
    }
}
