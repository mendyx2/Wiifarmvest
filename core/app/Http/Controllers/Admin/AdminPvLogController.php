<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\PvLog;
use App\Support\AdminPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminPvLogController extends Controller
{
    /** PV Logs (Pages/Admin/PvLog.tsx): every point-volume movement on both legs. */
    public function index(Request $request)
    {
        $search   = (string) $request->query('search', '');
        $position = (string) $request->query('position', ''); // 1=left, 2=right, ''=all
        $type     = (string) $request->query('type', '');     // +=credit, -=debit, ''=all

        $logs = PvLog::with('user')
            ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($u) => $u
                ->where('username', 'like', "%{$search}%")
                ->orWhere('firstname', 'like', "%{$search}%")
                ->orWhere('lastname', 'like', "%{$search}%")
            ))
            ->when(in_array($position, ['1', '2'], true), fn ($q) => $q->where('position', $position))
            ->when(in_array($type, ['+', '-'], true), fn ($q) => $q->where('trx_type', $type))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $first = $logs->firstItem() ?? 0;
        $rows  = $logs->through(function (PvLog $log) use (&$first) {
            $user = $log->user;

            return [
                'id'       => $log->id,
                'number'   => $first++,
                'user'     => AdminPresenter::user($user),
                'initials' => $user ? strtoupper(substr((string) $user->firstname, 0, 1) . substr((string) $user->lastname, 0, 1)) : '',
                'left'     => (int) $log->position === Status::LEFT,
                'credit'   => $log->trx_type === '+',
                'amount'   => $log->trx_type . number_format((float) $log->amount, 2),
                'details'  => (string) $log->details,
                'date'     => $log->created_at?->format('d M Y') ?? '',
                'time'     => $log->created_at?->format('h:i A') ?? '',
            ];
        });

        return Inertia::render('Admin/PvLog', [
            'logs'   => $rows,
            // Over the whole log, whatever the filter (as before).
            'totals' => [
                'left'    => number_format((float) PvLog::where('position', Status::LEFT)->where('trx_type', '+')->sum('amount'), 2),
                'right'   => number_format((float) PvLog::where('position', Status::RIGHT)->where('trx_type', '+')->sum('amount'), 2),
                'cut'     => number_format((float) PvLog::where('trx_type', '-')->sum('amount'), 2),
                'records' => number_format(PvLog::count()),
            ],
            'filters' => ['search' => $search, 'position' => $position, 'type' => $type],
        ])->withViewData(['pageTitle' => 'PV Logs']);
    }
}
