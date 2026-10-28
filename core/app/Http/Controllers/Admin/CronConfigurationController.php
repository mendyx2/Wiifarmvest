<?php

namespace App\Http\Controllers\Admin;

use App\Constants\Status;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CronController;
use App\Models\CronJob;
use App\Models\CronJobLog;
use App\Models\CronSchedule;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Inertia\Inertia;

/**
 * Cron Jobs (Pages/Admin/Cron/*): the scheduled jobs, their schedules, and
 * each job's run log.
 */
class CronConfigurationController extends Controller
{
    public function cronJobs()
    {
        $pageTitle = 'Cron Jobs';
        $crons     = CronJob::with('schedule')->withCount(['logs as errors' => fn ($q) => $q->whereNotNull('error')])->get();

        return Inertia::render('Admin/Cron/Index', [
            'crons' => $crons->map(fn (CronJob $c) => [
                'id'         => $c->id,
                'name'       => (string) __($c->name),
                'alias'      => (string) $c->alias,
                'schedule'   => (string) ($c->schedule->name ?? '—'),
                'nextRun'    => $c->next_run ? ['date' => showDateTime($c->next_run), 'ago' => diffForHumans($c->next_run)] : null,
                'lastRun'    => $c->last_run ? ['date' => showDateTime($c->last_run), 'ago' => diffForHumans($c->last_run)] : null,
                'running'    => (bool) $c->is_running,
                'default'    => (bool) $c->is_default,
                'errors'     => (int) $c->errors,
                'form'       => [
                    'id'               => $c->id,
                    'name'             => (string) $c->name,
                    'next_run'         => $c->next_run ? Carbon::parse($c->next_run)->format('Y-m-d\TH:i') : '',
                    'cron_schedule_id' => (string) $c->cron_schedule_id,
                    'url'              => (string) $c->url,
                ],
            ]),
            'schedules' => CronSchedule::active()->orderBy('interval')->get(['id', 'name'])->map(fn ($s) => ['id' => $s->id, 'name' => (string) $s->name]),
            // The command the server's crontab must run (every minute)
            'command'   => 'curl -s "' . route('cron') . '?token=' . (config('app.cron_secret') ? '<CRON_SECRET>' : '') . '"',
            'secretSet' => (bool) config('app.cron_secret'),
            'urls'      => [
                'store'     => route('admin.cron.store'),
                'update'    => route('admin.cron.update'),
                'delete'    => route('admin.cron.delete', ':id'),
                'run'       => route('admin.cron.run', ':id'),
                'pause'     => route('admin.cron.schedule.pause', ':id'),
                'logs'      => route('admin.cron.schedule.logs', ':id'),
                'schedules' => route('admin.cron.schedule'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    private function jobRules(): array
    {
        return [
            'name'             => 'required|string|max:255',
            'next_run'         => 'required|date',
            'cron_schedule_id' => 'required|integer|exists:cron_schedules,id',
        ];
    }

    public function cronJobStore(Request $request)
    {
        $request->validate($this->jobRules() + ['url' => 'required|url:http,https|max:500']);

        $cronJob                   = new CronJob();
        $cronJob->name             = $request->name;
        $cronJob->alias            = titleToKey($request->name);
        $cronJob->next_run         = Carbon::parse($request->next_run)->toDateTimeString();
        $cronJob->cron_schedule_id = $request->cron_schedule_id;
        $cronJob->url              = $request->url;
        $cronJob->is_default       = Status::NO;
        $cronJob->save();

        $notify[] = ['success', 'Cron job added successfully'];
        return to_route('admin.cron.index')->withNotify($notify);
    }

    public function cronJobUpdate(Request $request)
    {
        $request->validate($this->jobRules() + ['id' => 'required|integer']);

        $cronJob       = CronJob::findOrFail($request->id);
        $cronJob->name = $request->name;
        // A built-in job runs code, not a URL: its URL and alias stay as they are
        if (!$cronJob->is_default) {
            $request->validate(['url' => 'required|url:http,https|max:500']);
            $cronJob->url   = $request->url;
            $cronJob->alias = titleToKey($request->name);
        }
        $cronJob->next_run         = Carbon::parse($request->next_run)->toDateTimeString();
        $cronJob->cron_schedule_id = $request->cron_schedule_id;
        $cronJob->save();

        $notify[] = ['success', 'Cron job update successfully'];
        return back()->withNotify($notify);
    }

    public function CronJobDelete($id)
    {
        $cronJob = CronJob::where('is_default', 0)->where('id', $id)->firstOrFail();
        $cronJob->delete();
        CronJobLog::where('cron_job_id', $id)->delete();

        $notify[] = ['success', 'Cron job deleted successfully'];
        return back()->withNotify($notify);
    }

    /**
     * Run one job now. The old "Run Now" linked to the public /cron URL
     * without its secret token, so it always answered 403.
     */
    public function runNow($id)
    {
        $cron = CronJob::with('schedule')->findOrFail($id);
        app(CronController::class)->runJobs([$cron]);

        $error = CronJobLog::where('cron_job_id', $cron->id)->latest('id')->value('error');
        $notify[] = $error ? ['error', $cron->name . ' ran with an error: ' . \Illuminate\Support\Str::limit($error, 200)] : ['success', $cron->name . ' executed successfully'];
        return back()->withNotify($notify);
    }

    public function schedule()
    {
        $pageTitle = 'Cron Schedules';

        return Inertia::render('Admin/Cron/Schedules', [
            'schedules' => CronSchedule::orderBy('interval')->paginate(getPaginate())->withQueryString()->through(fn (CronSchedule $s) => [
                'id'       => $s->id,
                'name'     => (string) $s->name,
                'interval' => (int) $s->interval,
                'active'   => (bool) $s->status,
            ]),
            'urls' => [
                'store'  => route('admin.cron.schedule.store'),
                'status' => route('admin.cron.schedule.status', ':id'),
                'jobs'   => route('admin.cron.index'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function scheduleStore(Request $request)
    {
        $request->validate([
            'id'       => 'nullable|integer',
            'name'     => 'required|string|max:255',
            'interval' => 'required|integer|gt:0',
        ]);

        if ($request->id) {
            $schedule = CronSchedule::findOrFail($request->id);
            $message  = 'Cron schedule updated successfully';
        } else {
            $schedule         = new CronSchedule();
            $schedule->status = Status::ENABLE;
            $message          = 'Cron schedule added successfully';
        }
        // Editing used to switch a disabled schedule back on as a side effect
        $schedule->name     = $request->name;
        $schedule->interval = $request->interval;
        $schedule->save();

        $notify[] = ['success', $message];
        return back()->withNotify($notify);
    }

    public function scheduleStatus($id)
    {
        return CronSchedule::changeStatus($id);
    }

    /** Pause / play a job (POST with ?state=; it was a GET link). */
    public function schedulePause(Request $request, $id)
    {
        CronJob::findOrFail($id);
        return CronJob::changeStatus($id, 'is_running');
    }

    public function scheduleLogs($id)
    {
        $cronJob   = CronJob::findOrFail($id);
        $pageTitle = $cronJob->name . ' Cron Schedule Logs';

        return Inertia::render('Admin/Cron/Logs', [
            'title' => $pageTitle,
            'logs'  => CronJobLog::where('cron_job_id', $cronJob->id)->orderBy('id', 'DESC')->paginate(getPaginate())->withQueryString()->through(fn (CronJobLog $l) => [
                'id'       => $l->id,
                'start'    => showDateTime($l->start_at),
                'end'      => showDateTime($l->end_at),
                'duration' => (int) $l->duration,
                'error'    => $l->error,
            ]),
            'urls' => [
                'resolve' => route('admin.cron.schedule.log.resolved', ':id'),
                'flush'   => route('admin.cron.log.flush', $cronJob->id),
                'jobs'    => route('admin.cron.index'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function scheduleLogResolved($id)
    {
        $log        = CronJobLog::findOrFail($id);
        $log->error = null;
        $log->save();

        $notify[] = ['success', 'Cron log resolved successfully'];
        return back()->withNotify($notify);
    }

    public function logFlush($id)
    {
        $cronJob = CronJob::findOrFail($id);
        CronJobLog::where('cron_job_id', $cronJob->id)->delete();

        $notify[] = ['success', 'All logs flushed successfully'];
        return back()->withNotify($notify);
    }
}
