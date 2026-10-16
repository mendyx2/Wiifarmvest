<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\UnilevelGeneration;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UnilevelController extends Controller
{
    // ── Generations ─────────────────────────────────────────────────

    public function generationIndex()
    {
        $pageTitle   = 'Unilevel Generations';
        $generations = UnilevelGeneration::orderBy('number')->get();
        $usedNumbers = $generations->pluck('number')->toArray();
        $available   = array_diff(range(1, 15), $usedNumbers);

        return view('admin.unilevel.generations', compact('pageTitle', 'generations', 'available'));
    }

    public function generationStore(Request $request, int $id = 0)
    {
        $generation = $id ? UnilevelGeneration::findOrFail($id) : new UnilevelGeneration();

        $request->validate([
            'number'      => [
                'required', 'integer', 'min:1', 'max:15',
                Rule::unique('unilevel_generations', 'number')->ignore($generation->id),
            ],
            'title'       => 'required|string|max:100',
            'percentage'  => 'required|numeric|min:0|max:100',
            'description' => 'nullable|string|max:500',
            'status'      => 'nullable|boolean',
        ]);

        $generation->fill([
            'number'      => $request->number,
            'title'       => $request->title,
            'percentage'  => $request->percentage,
            'description' => $request->description,
            'status'      => $request->boolean('status', true),
        ])->save();

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

    public function generationToggle(int $id)
    {
        $generation         = UnilevelGeneration::findOrFail($id);
        $generation->status = !$generation->status;
        $generation->save();

        $state   = $generation->status ? 'enabled' : 'disabled';
        $notify[] = ['success', "Generation {$generation->number} {$state}."];
        return back()->withNotify($notify);
    }

    // ── Allocation ───────────────────────────────────────────────────

    public function allocationIndex(Request $request)
    {
        $pageTitle   = 'Allocate Generations';
        $projects    = Project::active()->withCount('users')->get();
        $generations = UnilevelGeneration::orderBy('number')->get();

        $selectedProject = null;
        $assignedIds     = collect();

        if ($request->filled('project')) {
            $selectedProject = Project::findOrFail($request->project);
            $assignedIds     = $selectedProject->unilevelGenerations()->pluck('unilevel_generations.id');
        }

        return view('admin.unilevel.allocate', compact(
            'pageTitle', 'projects', 'generations', 'selectedProject', 'assignedIds'
        ));
    }

    public function allocationStore(Request $request, int $projectId)
    {
        $request->validate([
            'generations'   => 'nullable|array',
            'generations.*' => 'integer|exists:unilevel_generations,id',
        ]);

        $project = Project::findOrFail($projectId);
        $project->unilevelGenerations()->sync($request->input('generations', []));

        $count    = count($request->input('generations', []));
        $notify[] = ['success', "{$count} generation(s) allocated to {$project->title}."];
        return redirect()->route('admin.unilevel.allocate.index', ['project' => $projectId])
                         ->withNotify($notify);
    }
}
