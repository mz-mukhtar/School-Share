<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Stub controller — to be fully implemented in Phase 3 (Projects).
 */
class ProjectController extends Controller
{
    public function index()
    {
        return view('projects.index', ['projects' => collect()]);
    }

    public function create()
    {
        return view('projects.create');
    }

    public function store(Request $request)
    {
        // Phase 3
        return redirect()->route('projects.index')->with('warning', 'Project creation coming soon in Phase 3!');
    }

    public function show($project)
    {
        abort(404);
    }

    public function edit($project)
    {
        abort(404);
    }

    public function update(Request $request, $project)
    {
        abort(404);
    }

    public function destroy($project)
    {
        abort(404);
    }
}
