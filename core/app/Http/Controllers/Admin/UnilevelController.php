<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\UnilevelGeneration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;

/**
 * Manage Unilevel (Pages/Admin/Unilevel/*): the generations (a percentage of
 * a product's PV paid to the buyer's upline at that depth; generation 1 is
 * the buyer) and which generations each project pays (UnilevelService).
 */
class UnilevelController extends Controller
{
    public function generationIndex()
    {
        $pageTitle   = 'Unilevel Generations';
        $generations = UnilevelGeneration::withCount('projects')->orderBy('number')->get();

        return Inertia::render('Admin/Unilevel/Generations', [
            'generations' => $generations->map(fn (UnilevelGeneration $g) => [
                'id'          => $g->id,
                'number'      => $g->number,
                'title'       => (string) $g->title,
                'percentage'  => (float) $g->percentage,
                'description' => (string) $g->description,
                'active'      => (bool) $g->status,
                'projects'    => (int) $g->projects_count,
            ]),
            'available' => array_values(array_diff(range(1, 15), $generations->pluck('number')->all())),
            'urls'      => [
                'store'    => route('admin.unilevel.generations.store'),
                'update'   => route('admin.unilevel.generations.update', ':id'),
                'destroy'  => route('admin.unilevel.generations.destroy', ':id'),
                'toggle'   => route('admin.unilevel.generations.toggle', ':id'),
                'allocate' => route('admin.unilevel.allocate.index'),
            ],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function generationStore(Request $request, int $id = 0)
    {
        $generation = $id ? UnilevelGeneration::findOrFail($id) : new UnilevelGeneration();

        $request->validate([
            'number'      => ['required', 'integer', 'min:1', 'max:15', Rule::unique('unilevel_generations', 'number')->ignore($generation->id)],
            'title'       => 'required|string|max:100',
            'percentage'  => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string|max:500',
        ]);

        $generation->number      = $request->number;
        $generation->title       = $request->title;
        $generation->percentage  = $request->percentage;
        $generation->description = $request->description;
        // A new generation starts on; editing keeps the current state (it used to switch a disabled one back on)
        if (!$id) {
            $generation->status = true;
        }
        $generation->save();

        $notify[] = ['success', $id ? 'Generation updated successfully.' : 'Generation created successfully.'];
        return back()->withNotify($notify);
    }

    public function generationDestroy(int $id)
    {
        $generation = UnilevelGeneration::findOrFail($id);
        $generation->projects()->detach();
        $generation->delete();

        $notify[] = ['success', "Generation {$generation->number} deleted successfully."];
        return back()->withNotify($notify);
    }

    /** On (state=enable) or off (state=disable): a disabled generation pays nothing. */
    public function generationToggle(Request $request, int $id)
    {
        $request->validate(['state' => 'required|in:enable,disable']);
        $generation = UnilevelGeneration::findOrFail($id);
        $on         = $request->state === 'enable';
        if ($generation->status === $on) {
            $notify[] = ['info', "Generation {$generation->number} is already " . ($on ? 'on' : 'off') . '.'];
            return back()->withNotify($notify);
        }
        $generation->status = $on;
        $generation->save();

        $notify[] = ['success', "Generation {$generation->number} " . ($on ? 'enabled' : 'disabled') . '.'];
        return back()->withNotify($notify);
    }

    public function allocationIndex(Request $request)
    {
        $pageTitle = 'Allocate Generations';
        $projects  = Project::active()->withCount('users')->get();
        $selected  = $request->filled('project') ? Project::findOrFail($request->project) : null;

        return Inertia::render('Admin/Unilevel/Allocate', [
            'projects' => $projects->map(fn (Project $p) => [
                'id'    => $p->id,
                'title' => (string) $p->title,
                'users' => (int) $p->users_count,
                'url'   => route('admin.unilevel.allocate.index', ['project' => $p->id]),
            ]),
            'generations' => UnilevelGeneration::orderBy('number')->get()->map(fn (UnilevelGeneration $g) => [
                'id'         => $g->id,
                'number'     => $g->number,
                'title'      => (string) $g->title,
                'percentage' => (float) $g->percentage,
                'active'     => (bool) $g->status,
            ]),
            'selected' => $selected ? [
                'id'       => $selected->id,
                'title'    => (string) $selected->title,
                'assigned' => $selected->unilevelGenerations()->pluck('unilevel_generations.id')->all(),
                'url'      => route('admin.unilevel.allocate.store', $selected->id),
            ] : null,
            'urls' => ['generations' => route('admin.unilevel.generations.index')],
        ])->withViewData(['pageTitle' => $pageTitle]);
    }

    public function allocationStore(Request $request, int $projectId)
    {
        $request->validate([
            'generations'   => 'nullable|array',
            'generations.*' => 'integer|exists:unilevel_generations,id',
        ]);

        $project = Project::findOrFail($projectId);
        $ids     = array_values(array_unique(array_map('intval', $request->input('generations', []))));
        $project->unilevelGenerations()->sync($ids);

        $notify[] = ['success', count($ids) . " generation(s) allocated to {$project->title}."];
        return to_route('admin.unilevel.allocate.index', ['project' => $projectId])->withNotify($notify);
    }
}
