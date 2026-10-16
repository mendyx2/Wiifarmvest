<?php

namespace App\Http\Controllers\Admin;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Http\Controllers\Controller;

class AdminProjectController extends Controller
{
    public function index()
    {
        $pageTitle = 'Projects';
        $projects  = Project::withCount('users')->orderBy('sort_order')->orderBy('amount')->get();
        return view('admin.projects.index', compact('pageTitle', 'projects'));
    }

    public function store(Request $request, int $id = 0)
    {
        $project = $id ? Project::findOrFail($id) : new Project();

        $request->validate([
            'title'               => ['required', 'string', 'max:150',
                                      Rule::unique('projects', 'title')->ignore($project->id)],
            'description'         => 'nullable|string|max:1000',
            'icon'                => 'nullable|string|max:60',
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
            'upgrade_allowed'     => 'nullable|boolean',
            'is_default'          => 'nullable|boolean',
            'status'              => 'nullable|boolean',
        ]);

        // Only one project can be the default
        if ($request->boolean('is_default')) {
            Project::where('id', '!=', $project->id)->update(['is_default' => false]);
        }

        $project->fill([
            'title'               => $request->title,
            'description'         => $request->description,
            'icon'                => $request->input('icon', 'las la-briefcase'),
            'color'               => $request->input('color', '#059669'),
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
            'status'              => $request->boolean('status', true),
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

    public function toggleStatus(int $id)
    {
        $project = Project::findOrFail($id);
        $project->status = !$project->status;
        $project->save();

        $state   = $project->status ? 'activated' : 'deactivated';
        $notify[] = ['success', "Project {$state} successfully."];
        return back()->withNotify($notify);
    }
}
