<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Award;
use App\Models\UserAward;
use App\Rules\FileTypeValidate;
use App\Services\WalletService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Manage Awards (Pages/Admin/Awards/*): the ranks members earn from their
 * PV (CheckUserAwardJob records them), and paying the award money into the
 * member's "awards" wallet.
 */
class AwardController extends Controller
{
    public function index()
    {
        $pageTitle = 'Award Management';

        return Inertia::render('Admin/Awards/Index', [
            'awards' => Award::with('prerequisite')->withCount(['userAwards', 'userAwards as unpaid' => fn ($q) => $q->where('status', 0)])->orderBy('sort_order')->get()->map(fn (Award $a) => [
                'id'          => $a->id,
                'name'        => (string) $a->name,
                'description' => (string) $a->description,
                'image'       => $a->image_url,
                'order'       => (int) $a->sort_order,
                'totalPv'     => number_format((float) $a->required_total_pv),
                'leftPv'      => number_format((float) $a->required_left_pv),
                'rightPv'     => number_format((float) $a->required_right_pv),
                'payment'     => showAmount($a->payment_amount),
                'prerequisite' => $a->prerequisite?->name,
                'earned'      => (int) $a->user_awards_count,
                'unpaid'      => (int) $a->unpaid,
                'active'      => (bool) $a->status,
                'edit'        => route('admin.awards.edit', $a->id),
            ]),
            'urls' => [
                'create'   => route('admin.awards.create'),
                'payments' => route('admin.awards.payments'),
                'toggle'   => route('admin.awards.toggle', ':id'),
                'destroy'  => route('admin.awards.destroy', ':id'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function create()
    {
        return $this->form(null);
    }

    public function edit(Award $award)
    {
        return $this->form($award);
    }

    private function form(?Award $award)
    {
        $pageTitle = $award ? 'Edit Award' : 'Add Award';

        return Inertia::render('Admin/Awards/Form', [
            'title' => $pageTitle,
            'award' => [
                'name'                  => (string) ($award->name ?? ''),
                'description'           => (string) ($award->description ?? ''),
                'required_total_pv'     => $award ? (string) getAmount($award->required_total_pv) : '0',
                'required_left_pv'      => $award ? (string) getAmount($award->required_left_pv) : '0',
                'required_right_pv'     => $award ? (string) getAmount($award->required_right_pv) : '0',
                'prerequisite_award_id' => (string) ($award->prerequisite_award_id ?? ''),
                'payment_amount'        => $award ? (string) getAmount($award->payment_amount) : '',
                'sort_order'            => (string) ($award->sort_order ?? (int) Award::max('sort_order') + 1),
                'status'                => $award ? (bool) $award->status : true,
            ],
            'image'     => $award?->image_url ?? getImage('', getFileSize('awards')),
            'size'      => getFileSize('awards'),
            // An award cannot require itself
            'awards'    => Award::when($award, fn ($q) => $q->where('id', '!=', $award->id))->orderBy('sort_order')->get(['id', 'name'])->map(fn ($a) => ['id' => $a->id, 'name' => (string) $a->name]),
            'currency'  => gs('cur_text'),
            'urls'      => ['submit' => $award ? route('admin.awards.update', $award->id) : route('admin.awards.store'), 'back' => route('admin.awards.index')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function store(Request $request)
    {
        $this->validateAward($request);
        $award = new Award();
        $this->fill($award, $request);

        $notify[] = ['success', 'Award created successfully.'];
        return to_route('admin.awards.index')->withNotify($notify);
    }

    public function update(Request $request, Award $award)
    {
        $this->validateAward($request, $award);
        $this->fill($award, $request);

        $notify[] = ['success', 'Award updated successfully.'];
        return to_route('admin.awards.index')->withNotify($notify);
    }

    private function fill(Award $award, Request $request): void
    {
        $award->name                  = $request->name;
        $award->description           = $request->description;
        $award->required_total_pv     = $request->required_total_pv;
        $award->required_left_pv      = $request->required_left_pv;
        $award->required_right_pv     = $request->required_right_pv;
        $award->prerequisite_award_id = $request->prerequisite_award_id ?: null;
        $award->payment_amount        = $request->payment_amount;
        $award->sort_order            = $request->sort_order;
        $award->status                = $request->boolean('status') ? 1 : 0;

        if ($request->hasFile('image')) {
            $award->image = fileUploader($request->image, getFilePath('awards'), getFileSize('awards'), $award->image);
        }
        $award->save();
    }

    /** Delete an award nobody has earned and no other award requires; otherwise switch it off. */
    public function destroy(Award $award)
    {
        if ($award->userAwards()->exists()) {
            $notify[] = ['error', 'Members have earned this award: switch it off instead of deleting it.'];
            return back()->withNotify($notify);
        }
        if (Award::where('prerequisite_award_id', $award->id)->exists()) {
            $notify[] = ['error', 'Another award requires this one first. Change that award before deleting this one.'];
            return back()->withNotify($notify);
        }

        if ($award->image) {
            fileManager()->removeFile(getFilePath('awards') . '/' . $award->image);
        }
        $award->delete();

        $notify[] = ['success', 'Award deleted successfully.'];
        return back()->withNotify($notify);
    }

    /** On (state=enable) or off (state=disable). It was an AJAX flip that answered JSON. */
    public function toggle(Request $request, Award $award)
    {
        $request->validate(['state' => 'required|in:enable,disable']);
        $on = $request->state === 'enable';
        if ((bool) $award->status === $on) {
            $notify[] = ['info', $award->name . ' is already ' . ($on ? 'active' : 'inactive') . '.'];
            return back()->withNotify($notify);
        }
        $award->status = $on ? 1 : 0;
        $award->save();

        $notify[] = ['success', $award->name . ($on ? ' activated.' : ' deactivated.')];
        return back()->withNotify($notify);
    }

    public function payments(Request $request)
    {
        $pageTitle = 'Award Payments';
        $status    = in_array($request->status, ['0', '1'], true) ? (int) $request->status : null;

        return Inertia::render('Admin/Awards/Payments', [
            'status'   => $status,
            'counts'   => ['all' => UserAward::count(), 'unpaid' => UserAward::where('status', 0)->count(), 'paid' => UserAward::where('status', 1)->count()],
            'payments' => UserAward::with(['user', 'award'])
                ->when($status !== null, fn ($q) => $q->where('status', $status))
                ->latest('id')
                ->paginate(getPaginate())->withQueryString()
                ->through(fn (UserAward $p) => [
                    'id'     => $p->id,
                    'member' => $p->user ? ['name' => (string) $p->user->fullname, 'username' => (string) $p->user->username, 'url' => route('admin.users.detail', $p->user_id)] : null,
                    'award'  => (string) ($p->award?->name ?: '—'),
                    'image'  => $p->award?->image_url,
                    'earned' => $p->earned_at ? showDateTime($p->earned_at) : '—',
                    // What was paid, or what paying now would pay
                    'amount' => showAmount((int) $p->status === 1 ? $p->paid_amount : ($p->award->payment_amount ?? 0)),
                    'paid'   => (int) $p->status === 1,
                    'paidAt' => $p->paid_at ? showDateTime($p->paid_at) : null,
                    'note'   => (string) $p->note,
                ]),
            'urls' => [
                'self'  => route('admin.awards.payments'),
                'pay'   => route('admin.awards.pay', ':id'),
                'index' => route('admin.awards.index'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    /**
     * Pay an earned award into the member's "awards" wallet. The award row is
     * locked and must still be unpaid: a second click used to pay it again.
     * The credit goes through WalletService, so it is in the member's
     * transactions (it used to be a bare increment with no record).
     */
    public function pay(Request $request, UserAward $userAward)
    {
        $request->validate(['note' => 'nullable|string|max:500']);

        $paid = DB::transaction(function () use ($request, $userAward) {
            $row = UserAward::with('award', 'user')->lockForUpdate()->find($userAward->id);
            if (!$row || (int) $row->status === 1 || !$row->award || !$row->user) {
                return null;
            }

            $amount = (float) $row->award->payment_amount;
            $trx    = getTrx();
            if ($amount > 0) {
                app(WalletService::class)->credit($row->user, $amount, 'award_bonus', 'Award payment: ' . $row->award->name, $trx, 'awards');
            }

            $row->update([
                'status'      => 1,
                'paid_at'     => now(),
                'paid_by'     => auth('admin')->id(),
                'paid_amount' => $amount,
                'note'        => $request->note,
            ]);

            // Debit from isolated Member Compensation Reserve Ledger (Strategic Reserve pool)
            try {
                app(\App\Services\RevenueAllocationService::class)->disburseCompensation(
                    $amount,
                    $trx,
                    'Award payment for ' . $row->award->name . ' to ' . $row->user->username,
                    \App\Models\CompensationReserveLedger::SUB_POOL_SR,
                    'award_bonus',
                    $row->user->id
                );
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('[RevenueAllocation] Failed to debit award payment: ' . $e->getMessage());
            }

            return $row;
        });

        if (!$paid) {
            $notify[] = ['info', 'This award was already paid (or its member or award no longer exists).'];
            return back()->withNotify($notify);
        }

        $notify[] = ['success', showAmount($paid->paid_amount) . ' paid to @' . $paid->user->username . ' for ' . $paid->award->name . '.'];
        return back()->withNotify($notify);
    }

    private function validateAward(Request $request, ?Award $award = null): void
    {
        $request->validate([
            'name'                  => ['required', 'string', 'max:150', Rule::unique('awards', 'name')->ignore($award?->id)],
            'description'           => 'nullable|string|max:2000',
            'required_total_pv'     => 'required|numeric|min:0',
            'required_left_pv'      => 'required|numeric|min:0',
            'required_right_pv'     => 'required|numeric|min:0',
            'prerequisite_award_id' => ['nullable', 'integer', 'exists:awards,id', Rule::notIn(array_filter([$award?->id]))],
            'payment_amount'        => 'required|numeric|min:0',
            'image'                 => ['nullable', 'image', 'max:2048', new FileTypeValidate(['jpeg', 'png', 'jpg', 'gif'])],
            'sort_order'            => 'required|integer|min:0',
            'status'                => 'required|boolean',
        ], ['prerequisite_award_id.not_in' => 'An award cannot require itself.']);
    }
}
