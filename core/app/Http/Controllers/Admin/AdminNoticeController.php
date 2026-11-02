<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use App\Models\User;
use App\Rules\FileTypeValidate;
use App\Services\AdminNoticeService;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Direct Notices (Pages/Admin/Notices.tsx): messages an admin sent to one
 * member, and the form to send one by username. A member's own page has
 * the same form (Users → Send Notification); both use AdminNoticeService.
 */
class AdminNoticeController extends Controller
{
    public function index(Request $request)
    {
        $pageTitle = 'Direct Notices';
        $search    = trim((string) $request->search);
        $sent      = NotificationLog::where('is_admin_notice', true);

        $notices = NotificationLog::with(['user', 'notifyingAdmin'])
            ->where('is_admin_notice', true)
            ->when($search !== '', fn ($q) => $q->whereHas('user', fn ($u) => $u->where(fn ($w) => $w->where('username', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))))
            ->latest('id')
            ->paginate(getPaginate())
            ->withQueryString();

        return Inertia::render('Admin/Notices', [
            'stats' => [
                'total'   => (clone $sent)->count(),
                'today'   => (clone $sent)->whereDate('created_at', today())->count(),
                'members' => (clone $sent)->distinct()->count('user_id'),
            ],
            'search'   => $search,
            'notices'  => $notices->through(fn (NotificationLog $n) => [
                'id'      => $n->id,
                'member'  => $n->user ? ['name' => (string) $n->user->fullname, 'username' => (string) $n->user->username, 'url' => route('admin.users.detail', $n->user->id)] : null,
                'subject' => (string) $n->subject,
                'message' => trim(html_entity_decode(strip_tags((string) $n->message))),
                'via'     => (string) $n->notification_type,
                'by'      => (string) ($n->notifyingAdmin?->name ?: $n->notifyingAdmin?->username ?: '—'),
                'date'    => showDateTime($n->created_at),
            ]),
            'channels' => $this->channels(),
            'urls'     => ['store' => route('admin.notices.store'), 'index' => route('admin.notices.index')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    private function channels(): array
    {
        return array_keys(array_filter(['email' => (bool) gs('en'), 'sms' => (bool) gs('sn'), 'push' => (bool) gs('pn')]));
    }

    public function store(Request $request)
    {
        $channels = $this->channels();
        if (!$channels) {
            $notify[] = ['warning', 'Notification options are disabled currently'];
            return back()->withNotify($notify);
        }
        $request->validate([
            'username' => 'required|string|max:40',
            'message'  => 'required|string|max:65000',
            // Only a channel switched on in settings
            'via'      => 'required|in:' . implode(',', $channels),
            'subject'  => 'required_if:via,email,push|nullable|string|max:255',
            'image'    => ['nullable', 'image', new FileTypeValidate(['jpg', 'jpeg', 'png'])],
        ]);

        $user = User::where('username', $request->username)->first();
        if (!$user) {
            return back()->withErrors(['username' => 'No member has this username.']);
        }

        $imageUrl = null;
        if ($request->via == 'push' && $request->hasFile('image')) {
            $imageUrl = fileUploader($request->image, getFilePath('push'));
        }

        [$success, $message] = (new AdminNoticeService())->send($user, $request->via, $request->subject, $request->message, $imageUrl, auth('admin')->id());

        $notify[] = [$success ? 'success' : 'warning', $message];
        return back()->withNotify($notify);
    }
}
