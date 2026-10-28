<?php

namespace App\Http\Controllers\Admin;

use App\Models\Order;
use App\Models\ProductStatePrice;
use App\Models\State;
use App\Models\Stockist;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

class StateController extends Controller
{
    /** Shop → Manage States (Pages/Admin/Shop/States.tsx). */
    public function index()
    {
        $pageTitle = 'Manage States';
        $prices    = ProductStatePrice::selectRaw('state_id, count(*) as n')->groupBy('state_id')->pluck('n', 'state_id');
        $stockists = Stockist::selectRaw('state_id, count(*) as n')->groupBy('state_id')->pluck('n', 'state_id');

        $states = State::orderBy('name')->get()->map(fn (State $state) => [
            'id'        => $state->id,
            'name'      => (string) $state->name,
            'code'      => (string) $state->code,
            'prices'    => (int) ($prices[$state->id] ?? 0),
            'stockists' => (int) ($stockists[$state->id] ?? 0),
        ]);

        return Inertia::render('Admin/Shop/States', [
            'states' => $states,
            'urls'   => [
                'store'    => route('admin.state.store'),
                'update'   => route('admin.state.update', ':id'),
                'destroy'  => route('admin.state.destroy', ':id'),
                'products' => route('admin.product.index'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function store(Request $request, int $id = 0)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:100', Rule::unique('states', 'name')->ignore($id)],
            'code' => ['required', 'string', 'max:10', 'alpha_num', Rule::unique('states', 'code')->ignore($id)],
        ]);

        $state       = $id ? State::findOrFail($id) : new State();
        $state->name = trim($request->name);
        $state->code = strtoupper(trim($request->code));
        $state->save();

        $notify[] = ['success', $id ? 'State updated successfully' : 'State added successfully'];
        return back()->withNotify($notify);
    }

    /**
     * Delete a state and its product prices. Refused while stockists or
     * orders use it: deleting it left them pointing at nothing, and the
     * prices it promised to remove were left behind.
     */
    public function destroy(int $id)
    {
        $state = State::findOrFail($id);

        $stockists = Stockist::where('state_id', $state->id)->count();
        $orders    = Order::where('state_id', $state->id)->count();
        if ($stockists || $orders) {
            $notify[] = ['error', "{$state->name} is used by {$stockists} stockist(s) and {$orders} order(s), so it cannot be deleted."];
            return back()->withNotify($notify);
        }

        DB::transaction(function () use ($state) {
            ProductStatePrice::where('state_id', $state->id)->delete();
            $state->delete();
        });

        $notify[] = ['success', 'State deleted successfully'];
        return back()->withNotify($notify);
    }
}
