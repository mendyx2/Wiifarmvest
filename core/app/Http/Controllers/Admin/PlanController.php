<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\Rinvestment;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Manage FFO → Project Plans (Pages/Admin/Plans/*): the share plans members
 * buy units of, their commissions, and each plan's holders.
 */
class PlanController extends Controller
{
    public function index()
    {
        $pageTitle = 'Project Plans';

        return Inertia::render('Admin/Plans/Index', [
            'plans' => Plan::orderBy('price')->withCount(['rinvestments as holders' => fn ($q) => $q->where('status', 1)])->withSum(['rinvestments as units' => fn ($q) => $q->where('status', 1)], 'units')->get()->map(fn (Plan $p) => [
                'id'      => $p->id,
                'name'    => (string) $p->name,
                'price'   => showAmount($p->price),
                'refCom'  => showAmount($p->ref_com),
                'lev2'    => showAmount($p->lev2),
                'lev3'    => showAmount($p->lev3),
                'lev4'    => showAmount($p->lev4),
                'holders' => (int) $p->holders,
                'units'   => number_format((float) $p->units),
                'active'  => (int) $p->status === Status::ENABLE,
                'form'    => ['name' => (string) $p->name, 'price' => (string) getAmount($p->price), 'ref_com' => (string) getAmount($p->ref_com), 'lev2' => (string) getAmount($p->lev2), 'lev3' => (string) getAmount($p->lev3), 'lev4' => (string) getAmount($p->lev4)],
                'url'     => route('admin.plan.userPlan', $p->id),
            ]),
            'currency' => gs('cur_text'),
            'urls'     => ['store' => route('admin.plan.store'), 'update' => route('admin.plan.store', ':id'), 'status' => route('admin.plan.status', ':id')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function store(Request $request, $id = 0)
    {
        $request->validate([
            'name'    => 'required|string|max:40',
            'price'   => 'required|numeric|gt:0',
            'ref_com' => 'required|numeric|min:0',
            'lev2'    => 'required|numeric|min:0',
            'lev3'    => 'required|numeric|min:0',
            'lev4'    => 'required|numeric|min:0',
        ]);

        $plan = $id ? Plan::findOrFail($id) : new Plan();
        if (!$id) {
            // Not on the form: the old form sent them as hidden zeros
            $plan->bv       = 0;
            $plan->tree_com = 0;
        }
        $plan->name    = $request->name;
        $plan->price   = $request->price;
        $plan->ref_com = $request->ref_com;
        $plan->lev2    = $request->lev2;
        $plan->lev3    = $request->lev3;
        $plan->lev4    = $request->lev4;
        $plan->save();

        $notify[] = ['success', $id ? 'Plan updated successfully' : 'Plan added successfully'];
        return back()->withNotify($notify);
    }

    /** The members holding units of a plan. */
    public function userPlan($id = null)
    {
        // The id was optional and defaulted to 1; a missing plan crashed the page
        $plan = Plan::findOrFail($id);

        return Inertia::render('Admin/Plans/Holders', [
            'plan'     => ['id' => $plan->id, 'name' => (string) $plan->name, 'price' => showAmount($plan->price)],
            'holdings' => Rinvestment::where('plan_id', $plan->id)->with('user')->orderByDesc('id')->paginate(getPaginate())->through(fn (Rinvestment $r) => [
                'id'       => $r->id,
                'member'   => $r->user ? ['name' => (string) $r->user->fullname, 'username' => (string) $r->user->username, 'url' => route('admin.users.detail', $r->user_id)] : null,
                'unitCost' => showAmount($r->unit_cost),
                'units'    => number_format((float) $r->units),
                'paid'     => showAmount($r->five),
                'value'    => showAmount($r->unit_cost * $r->units),
                'active'   => (int) $r->status === Status::ENABLE,
                'date'     => showDateTime($r->created_at),
            ]),
            'urls' => ['status' => route('admin.plan.statusUser', ':id'), 'back' => route('admin.plan.index')],
        ])->withViewData(['pageTitle' => $plan->name]);
    }

    public function status($id)
    {
        return Plan::changeStatus($id);
    }

    /** Switch one holding on or off: a disabled holding is left out of dividend batches. */
    public function statusUser($id)
    {
        return Rinvestment::changeStatus($id);
    }
}
