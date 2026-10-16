<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Models\PvLog;
use Illuminate\Http\Request;

class AdminPvLogController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'PV Logs'; 
        $search    = $request->search;
        $position  = $request->position;   // 1=left, 2=right, ''=all
        $type      = $request->type;       // +=credit, -=debit, ''=all

        $logs = PvLog::with('user')
            ->when($search, fn($q) => $q->whereHas('user', fn($u) => $u
                ->where('username',  'like', "%{$search}%")
                ->orWhere('firstname', 'like', "%{$search}%")
                ->orWhere('lastname',  'like', "%{$search}%")
            ))
            ->when($position !== null && $position !== '', fn($q) => $q->where('position', $position))
            ->when($type      !== null && $type      !== '', fn($q) => $q->where('trx_type', $type))
            ->latest()
            ->paginate(30)
            ->withQueryString();

        $totalLeft   = PvLog::where('position', Status::LEFT)->where('trx_type', '+')->sum('amount');
        $totalRight  = PvLog::where('position', Status::RIGHT)->where('trx_type', '+')->sum('amount');
        $totalCut    = PvLog::where('trx_type', '-')->sum('amount');
        $totalRecords = PvLog::count();

        return view('admin.pv-log.index', compact(
            'pageTitle', 'logs', 'search', 'position', 'type',
            'totalLeft', 'totalRight', 'totalCut', 'totalRecords'
        ));
    }
}
