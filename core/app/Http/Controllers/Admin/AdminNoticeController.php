<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\User;
use App\Rules\FileTypeValidate;
use App\Services\AdminNoticeService;
use Illuminate\Http\Request;

class AdminNoticeController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Direct Notices';
        $search    = $request->search;

        $totalSent    = NotificationLog::where('is_admin_notice', true)->count();
        $sentToday    = NotificationLog::where('is_admin_notice', true)->whereDate('created_at', today())->count();
        $usersReached = NotificationLog::where('is_admin_notice', true)->distinct()->count('user_id');

        $notices = NotificationLog::with(['user', 'notifyingAdmin'])
            ->where('is_admin_notice', true)
            ->when($search, fn($q) => $q->whereHas('user', fn($u) => $u
                ->where('username', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")
            ))
            ->latest()
            ->paginate(25)
            ->withQueryString();

        return view('admin.notices.index', compact(
            'pageTitle', 'search', 'notices', 'totalSent', 'sentToday', 'usersReached'
        ));
    }

    public function create()
    {
        $pageTitle = 'Send Direct Notice';
        return view('admin.notices.create', compact('pageTitle'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'message'  => 'required',
            'via'      => 'required|in:email,sms,push',
            'subject'  => 'required_if:via,email,push',
            'image'    => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        if (!gs('en') && !gs('sn') && !gs('pn')) {
            $notify[] = ['warning', 'Notification options are disabled currently'];
            return to_route('admin.dashboard')->withNotify($notify);
        }

        $user = User::where('username', $request->username)->first();
        if (!$user) {
            $notify[] = ['error', 'No user found with that username.'];
            return back()->withInput()->withNotify($notify);
        }

        $imageUrl = null;
        if ($request->via == 'push' && $request->hasFile('image')) {
            $imageUrl = fileUploader($request->image, getFilePath('push'));
        }

        [$success, $message] = (new AdminNoticeService())->send(
            $user,
            $request->via,
            $request->subject,
            $request->message,
            $imageUrl,
            auth('admin')->id()
        );

        $notify[] = [$success ? 'success' : 'warning', $message];

        return $success
            ? to_route('admin.notices.index')->withNotify($notify)
            : back()->withInput()->withNotify($notify);
    }
}
