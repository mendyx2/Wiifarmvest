<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Middleware\AdminAccessMiddleware;
use App\Models\Pin;
use App\Models\User;
use App\Support\AdminPresenter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Inertia\Inertia;

/**
 * E-pins (Pages/Admin/Pins/Index.tsx). The sidebar's five pin pages and the
 * member's "pins created" / "pins used" pages were seven copies of one Blade
 * table; they are now presets of one page, which can also be filtered by
 * status and by who created the pin.
 */
class PinController extends Controller
{
    /** The most pins one "Create Pins" can make; the old form had no limit. */
    private const MAX_BATCH = 500;

    public function allPins()
    {
        return $this->listPage('All Pins');
    }

    public function usedPins()
    {
        return $this->listPage('All Used Pins', status: 'used');
    }

    public function unusedPins()
    {
        return $this->listPage('All Unused Pins', status: 'unused');
    }

    public function adminPins()
    {
        return $this->listPage('All Admin Pins', source: 'admin');
    }

    public function userPins()
    {
        return $this->listPage('All User Pins', source: 'user');
    }

    /** admin.users.generate.pin: pins this member bought. */
    public function generatedBy($id)
    {
        $user = User::findOrFail($id);
        return $this->listPage('Generate pin - ' . $user->username, member: fn (Builder $q) => $q->where('generate_user_id', $user->id));
    }

    /** admin.users.used.pin: pins this member redeemed. */
    public function usedBy($id)
    {
        $user = User::findOrFail($id);
        return $this->listPage('Used pin - ' . $user->username, member: fn (Builder $q) => $q->where('user_id', $user->id));
    }

    /**
     * $status and $source preset the filters for the page's own route; the
     * query string (?status=, ?source=) can still narrow any of them.
     */
    private function listPage(string $pageTitle, string $status = '', string $source = '', ?\Closure $member = null)
    {
        $request = request();
        $status  = in_array($request->query('status'), ['used', 'unused'], true) ? $request->query('status') : $status;
        $source  = in_array($request->query('source'), ['admin', 'user'], true) ? $request->query('source') : $source;
        $admin   = auth('admin')->user();
        // Pins are bearer codes worth money: only the owner roles see them in full, as before.
        $fullPin = $admin->hasRole('Owner|super-admin');

        $pins = Pin::query()
            ->when($member, $member)
            ->when($status === 'used', fn ($q) => $q->where('status', Status::ENABLE))
            ->when($status === 'unused', fn ($q) => $q->where('status', Status::DISABLE))
            ->when($source === 'admin', fn ($q) => $q->whereNull('generate_user_id'))
            ->when($source === 'user', fn ($q) => $q->whereNotNull('generate_user_id'))
            ->searchable(['pin'])
            ->with(['createUser:id,firstname,lastname,username', 'user:id,firstname,lastname,username'])
            ->orderByDesc('id')
            ->paginate(getPaginate())
            ->withQueryString()
            ->through(fn (Pin $pin) => [
                'id'      => $pin->id,
                'details' => (string) __($pin->details),
                'creator' => $pin->generate_user_id ? AdminPresenter::user($pin->createUser) : null,
                'usedBy'  => (int) $pin->status === Status::ENABLE && $pin->user ? AdminPresenter::user($pin->user) : null,
                'amount'  => showAmount($pin->amount),
                'pin'     => $fullPin ? (string) $pin->pin : Str::limit((string) $pin->pin, 20),
                'used'    => (int) $pin->status === Status::ENABLE,
                'usedAgo' => (int) $pin->status === Status::ENABLE ? diffForHumans($pin->updated_at) : null,
                'when'    => AdminPresenter::when($pin->created_at),
            ]);

        return Inertia::render('Admin/Pins/Index', [
            'title'    => $pageTitle,
            'pins'     => $pins,
            'filters'  => ['search' => (string) $request->query('search', ''), 'status' => $status, 'source' => $source],
            // The member pages are fixed to that member, so they offer no status / source filter.
            'member'   => $member !== null,
            'urls'     => ['store' => AdminAccessMiddleware::allows($admin, 'admin.pin.store', 'POST') ? route('admin.pin.store') : null],
            'currency' => gs('cur_text'),
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|gt:0',
            'number' => 'required|integer|min:1|max:' . self::MAX_BATCH,
        ]);

        DB::transaction(function () use ($request) {
            for ($i = 1; $i <= $request->number; $i++) {
                $pin          = new Pin();
                $pin->amount  = $request->amount;
                $pin->pin     = Pin::newCode();
                $pin->details = 'Created via admin';
                $pin->save();
            }
        });

        $notify[] = ['success', $request->number . ' pin(s) created'];
        return to_route('admin.pin.unused', ['source' => 'admin'])->withNotify($notify);
    }
}
