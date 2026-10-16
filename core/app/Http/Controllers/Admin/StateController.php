<?php

namespace App\Http\Controllers\Admin;

use App\Models\State;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;

class StateController extends Controller
{
    public function index()
    {
        $pageTitle = 'Manage States';
        $states    = State::latest('id')->get();
        return view('admin.state.index', compact('pageTitle', 'states'));
    }

    public function store(Request $request, int $id = 0)
    {
        $request->validate([
            'name' => 'required|string|max:100',
            'code' => [
                'required',
                'string',
                'max:10',
                \Illuminate\Validation\Rule::unique('states', 'code')->ignore($id),
            ],
        ]);

        $state       = $id ? State::findOrFail($id) : new State();
        $state->name = trim($request->name);
        $state->code = strtoupper(trim($request->code));
        $state->save();

        $notify[] = ['success', $id ? 'State updated successfully' : 'State added successfully'];
        return back()->withNotify($notify);
    }

    public function destroy(int $id)
    {
        $state = State::findOrFail($id);
        $state->delete();

        $notify[] = ['success', 'State deleted successfully'];
        return back()->withNotify($notify);
    }
}
