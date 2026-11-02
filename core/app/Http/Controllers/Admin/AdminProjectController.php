<?php

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;
use Inertia\Inertia;

/**
 * MLM Projects (Pages/Admin/Projects.tsx): the membership tiers, their price,
 * PV and commissions.
 */
class AdminProjectController extends Controller
{
    private const MONEY = ['amount', 'direct_commission', 'indirect_commission', 'pv', 'pairing_per_day', 'cash_back', 'monthly_maintenance', 'upgrade_bonus', 'unilevel_bonus'];

    public function index()
    {
        $pageTitle = 'Projects';
        $projects  = Project::withCount('users')->orderBy('sort_order')->orderBy('amount')->get();

        return Inertia::render('Admin/Projects', [
            'projects' => $projects->map(fn (Project $p) => [
                'id'          => $p->id,
                'title'       => (string) $p->title,
                'slug'        => (string) $p->slug,
                'icon'        => (string) ($p->icon ?: 'las la-briefcase'),
                'color'       => preg_match('/^#[0-9A-Fa-f]{3,6}$/', (string) $p->color) ? $p->color : '#059669',
                'description' => (string) $p->description,
                'users'       => (int) $p->users_count,
                'active'      => (bool) $p->status,
                'default'     => (bool) $p->is_default,
                'show'        => [
                    'amount'              => showAmount($p->amount),
                    'pv'                  => getAmount($p->pv),
                    'cash_back'           => showAmount($p->cash_back),
                    'direct_commission'   => showAmount($p->direct_commission),
                    'indirect_commission' => showAmount($p->indirect_commission),
                    'pairing_per_day'     => $p->pairing_per_day > 0 ? showAmount($p->pairing_per_day) : 'Unlimited',
                    'upgrade_bonus'       => getAmount($p->upgrade_bonus) . '%',
                    'unilevel_bonus'      => showAmount($p->unilevel_bonus),
                    'monthly_maintenance' => $p->monthly_maintenance > 0 ? showAmount($p->monthly_maintenance) : null,
                ],
                'form' => collect(self::MONEY)->mapWithKeys(fn ($k) => [$k => (string) getAmount($p->$k)])->all() + [
                    'title'             => (string) $p->title,
                    'description'       => (string) $p->description,
                    'icon'              => (string) $p->icon,
                    'color'             => (string) $p->color,
                    'max_pairing_slots' => (string) (int) $p->max_pairing_slots,
                    'sort_order'        => (string) (int) $p->sort_order,
                    'upgrade_allowed'   => (bool) $p->upgrade_allowed,
                    'is_default'        => (bool) $p->is_default,
                    'status'            => (bool) $p->status,
                ],
            ]),
            'currency' => gs('cur_text'),
            'urls'     => [
                'store'   => route('admin.projects.store'),
                'update'  => route('admin.projects.update', ':id'),
                'destroy' => route('admin.projects.destroy', ':id'),
                'toggle'  => route('admin.projects.toggle', ':id'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function store(Request $request, int $id = 0)
    {
        $project = $id ? Project::findOrFail($id) : new Project();

        $request->validate([
            'title'               => ['required', 'string', 'max:150',
                                      Rule::unique('projects', 'title')->ignore($project->id)],
            'description'         => 'nullable|string|max:1000',
            'icon'                => ['nullable', 'string', 'max:60', 'regex:/^[a-z0-9 _-]+$/i'],
            'color'               => ['nullable', 'regex:/^#([0-9A-Fa-f]{3}|[0-9A-Fa-f]{6})$/'],
            'amount'              => 'required|numeric|min:0',
            'direct_commission'   => 'required|numeric|min:0',
            'indirect_commission' => 'required|numeric|min:0',
            'pv'                  => 'required|numeric|min:0',
            'pairing_per_day'     => 'required|numeric|min:0',
            'cash_back'           => 'required|numeric|min:0',
            'monthly_maintenance' => 'required|numeric|min:0',
            'upgrade_bonus'       => 'required|numeric|min:0|max:100',
            'unilevel_bonus'      => 'required|numeric|min:0',
            'max_pairing_slots'   => 'required|integer|min:0',
            'sort_order'          => 'required|integer|min:0',
            'upgrade_allowed'     => 'required|boolean',
            'is_default'          => 'required|boolean',
            // Required: the old form's unticked box sent nothing and the server defaulted to "on"
            'status'              => 'required|boolean',
        ]);

        // Only one project can be the default
        if ($request->boolean('is_default')) {
            Project::where('id', '!=', $project->id)->update(['is_default' => false]);
        }

        $project->fill([
            'title'               => $request->title,
            'description'         => $request->description,
            'icon'                => $request->input('icon') ?: 'las la-briefcase',
            'color'               => $request->input('color') ?: '#059669',
            'amount'              => $request->amount,
            'direct_commission'   => $request->direct_commission,
            'indirect_commission' => $request->indirect_commission,
            'pv'                  => $request->pv,
            'pairing_per_day'     => $request->pairing_per_day,
            'cash_back'           => $request->cash_back,
            'monthly_maintenance' => $request->monthly_maintenance,
            'upgrade_bonus'       => $request->upgrade_bonus,
            'unilevel_bonus'      => $request->unilevel_bonus,
            'max_pairing_slots'   => $request->max_pairing_slots,
            'sort_order'          => $request->sort_order,
            'upgrade_allowed'     => $request->boolean('upgrade_allowed'),
            'is_default'          => $request->boolean('is_default'),
            'status'              => $request->boolean('status'),
        ])->save();

        $notify[] = ['success', $id ? 'Project updated successfully.' : 'Project created successfully.'];
        return back()->withNotify($notify);
    }

    public function destroy(int $id)
    {
        $project = Project::withCount('users')->findOrFail($id);

        if ($project->users_count > 0) {
            $notify[] = ['error', "Cannot delete \"{$project->title}\" — {$project->users_count} user(s) are subscribed to it."];
            return back()->withNotify($notify);
        }

        $project->delete();
        $notify[] = ['success', 'Project deleted successfully.'];
        return back()->withNotify($notify);
    }

    /** On (state=enable) or off (state=disable). */
    public function toggleStatus(Request $request, int $id)
    {
        $request->validate(['state' => 'required|in:enable,disable']);
        $project = Project::findOrFail($id);
        $on      = $request->state === 'enable';
        if ((bool) $project->status === $on) {
            $notify[] = ['info', "{$project->title} is already " . ($on ? 'active' : 'inactive') . '.'];
            return back()->withNotify($notify);
        }
        $project->status = $on;
        $project->save();

        $notify[] = ['success', "Project " . ($on ? 'activated' : 'deactivated') . ' successfully.'];
        return back()->withNotify($notify);
    }
}
