<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Artisan;
use App\Models\UpdateLog;
use App\Models\Admin;
use App\Models\AdminActionLog;
use App\Support\AdminPresenter;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Illuminate\Support\Str;

class SystemController extends Controller
{
    /**
     * System Information (Pages/Admin/System/Info.tsx): application and
     * server details, clear cache, the version and the update log. The old
     * Application, Server, Clear Cache, Update and Update Log pages were five
     * small pages (none in the sidebar); their URLs open this one.
     */
    public function systemInfo()
    {
        $pageTitle = 'System Information';
        $details   = systemDetails();
        $server    = request()->server();

        return Inertia::render('Admin/System/Info', [
            'app' => [
                ['label' => keyToTitle($details['name']) . ' Version', 'value' => (string) $details['version']],
                ['label' => 'ViserAdmin Version', 'value' => (string) ($details['build_version'] ?? '')],
                ['label' => 'Laravel Version', 'value' => app()->version()],
                ['label' => 'Timezone', 'value' => (string) config('app.timezone')],
            ],
            // Only these: $_SERVER can hold environment variables (APP_KEY, DB_PASSWORD ...)
            'server' => [
                ['label' => 'PHP Version', 'value' => PHP_VERSION],
                ['label' => 'Server Software', 'value' => (string) ($server['SERVER_SOFTWARE'] ?? '')],
                ['label' => 'Server IP Address', 'value' => (string) ($server['SERVER_ADDR'] ?? '')],
                ['label' => 'Server Protocol', 'value' => (string) ($server['SERVER_PROTOCOL'] ?? '')],
                ['label' => 'HTTP Host', 'value' => (string) ($server['HTTP_HOST'] ?? '')],
                ['label' => 'Server Port', 'value' => (string) ($server['SERVER_PORT'] ?? '')],
            ],
            'version' => ['current' => (string) $details['version'], 'available' => (string) gs('available_version')],
            'updates' => UpdateLog::orderBy('id', 'desc')->limit(50)->get()->map(fn (UpdateLog $u) => [
                'id'      => $u->id,
                'version' => (string) $u->version,
                'date'    => $u->created_at?->format('Y-m-d'),
                'changes' => array_values(array_map('strval', (array) $u->update_log)),
            ]),
            'urls' => ['clear' => route('admin.system.optimize.clear')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function optimizeClear()
    {
        Artisan::call('optimize:clear');
        $notify[] = ['success', 'Cache cleared successfully'];
        return back()->withNotify($notify);
    }

    /**
     * One-click vendor updates are switched off. They download the vendor's
     * release and unzip it over the whole site, then run its SQL: on this
     * heavily customised installation that would overwrite the custom code
     * and could break the database. Updates go through the repository.
     */
    public function systemUpdateProcess()
    {
        $notify[] = ['error', 'Automatic updates are disabled: this installation is customised, and a vendor update would overwrite it. Updates are deployed from the code repository.'];
        return back()->withNotify($notify);
    }

    /**
     * Every change an admin made (AdminActionLogMiddleware records each
     * successful POST / PUT / PATCH / DELETE). Super-admins only.
     * Pages/Admin/ActionLog.tsx.
     */
    public function adminActionLog(Request $request)
    {
        $logs = AdminActionLog::query()
            ->when($request->filled('admin'), fn ($q) => $q->where('admin_id', $request->integer('admin')))
            ->when(in_array($request->query('method'), ['POST', 'PUT', 'PATCH', 'DELETE'], true), fn ($q) => $q->where('method', $request->query('method')))
            ->when($request->filled('search'), function ($q) use ($request) {
                $term = '%' . $request->query('search') . '%';
                $q->where(fn ($w) => $w->where('route_name', 'like', $term)->orWhere('uri', 'like', $term)->orWhere('description', 'like', $term));
            })
            ->dateFilter()
            ->latest('id')
            ->paginate(getPaginate())
            ->withQueryString()
            ->through(fn (AdminActionLog $log) => [
                'id'          => $log->id,
                'date'        => showDateTime($log->created_at, 'd M, Y h:i A'),
                'admin'       => $log->admin_name ?? $log->admin_username ?? __('Unknown'),
                'role'        => (string) $log->admin_role,
                'method'      => (string) $log->method,
                'action'      => (string) ($log->action ?? $log->method),
                'resource'    => (string) ($log->route_name ?? $log->uri),
                // The old page printed "null" when a log had neither.
                'summary'     => Str::limit((string) ($log->description ?: ($log->meta ? json_encode($log->meta) : '—')), 100),
                // For the detail dialog: the old page cut everything at 100 characters.
                'description' => (string) $log->description,
                'uri'         => (string) $log->uri,
                'subject'     => $log->subject_type ? $log->subject_type . ' #' . $log->subject_id : null,
                'meta'        => $log->meta ? json_encode($log->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) : null,
                'ip'          => (string) $log->ip_address,
                'userAgent'   => (string) $log->user_agent,
            ]);

        return Inertia::render('Admin/ActionLog', [
            'logs'    => $logs,
            'admins'  => Admin::orderBy('username')->get(['id', 'name', 'username'])
                ->map(fn (Admin $a) => ['id' => $a->id, 'label' => $a->name ? "{$a->name} ({$a->username})" : $a->username])->values(),
            'filters' => [
                'search' => (string) $request->query('search', ''),
                'admin'  => (string) $request->query('admin', ''),
                'method' => (string) $request->query('method', ''),
                'date'   => AdminPresenter::dateRange($request->query('date')),
            ],
        ])->withViewData(['pageTitle' => 'Admin Action Log']);
    }
}
