<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;

class AcbController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        abort_unless($user->isAcb(), 403);

        $pageTitle = 'My ACB';

        $history = Transaction::where('user_id', $user->id)
            ->where('remark', 'acb_bonus')
            ->latest()
            ->paginate(20);

        return view('Template::user.acb', compact('pageTitle', 'user', 'history'));
    }
}
