<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class ProjectController extends Controller
{
    /**
     * Display a listing of the user's projects.
     */
    public function index(Request $request)
    {
        $query = $request->user()->projects()->latest();

        if ($search = $request->input('q')) {
            $query->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
        }

        $projects = $query->paginate(12);
        
        return view('projects.index', compact('projects'));
    }

    /**
     * Show the form for creating a new project.
     */
    public function create(Request $request)
    {
        if (!$request->user()->canCreateProject()) {
            return redirect()->route('projects.index')
                ->with('error', 'You have reached the maximum number of projects for your plan.');
        }

        return view('projects.create');
    }

    /**
     * Store a newly created project in storage.
     */
    public function store(Request $request)
    {
        if (!$request->user()->canCreateProject()) {
            return redirect()->route('projects.index')
                ->with('error', 'You have reached the maximum number of projects for your plan.');
        }

        $validated = $request->validate([
            'name'        => [
                'required', 
                'string', 
                'max:255', 
                \Illuminate\Validation\Rule::unique('projects')->where('user_id', $request->user()->id)
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'subject_tag' => ['nullable', 'string', 'max:100'],
            'visibility'  => ['required', 'in:public,private'],
        ], [
            'name.unique' => 'You already have a project with this name.',
        ]);

        $project = $request->user()->projects()->create($validated);

        return redirect()->route('projects.show', $project->slug)
            ->with('success', 'Project created successfully!');
    }

    /**
     * Display the specified project (the Repository).
     */
    public function show(Project $project)
    {
        // Access control
        if ($project->visibility === 'private' && auth()->id() !== $project->user_id) {
            abort(403, 'This project is private.');
        }

        $latestCheckpoint = $project->latestCheckpoint()->with('files')->first();
        $checkpoints = $project->checkpoints()->withCount('files')->limit(5)->get();

        return view('projects.show', compact('project', 'latestCheckpoint', 'checkpoints'));
    }

    /**
     * Show the form for editing the specified project settings.
     */
    public function edit(Project $project)
    {
        if (auth()->id() !== $project->user_id) {
            abort(403, 'Only the owner can edit settings.');
        }

        return view('projects.edit', compact('project'));
    }

    /**
     * Update the specified project in storage.
     */
    public function update(Request $request, Project $project)
    {
        if (auth()->id() !== $project->user_id) {
            abort(403, 'Only the owner can edit settings.');
        }

        $validated = $request->validate([
            'name'        => [
                'required', 
                'string', 
                'max:255', 
                \Illuminate\Validation\Rule::unique('projects')->where('user_id', $request->user()->id)->ignore($project->id)
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'subject_tag' => ['nullable', 'string', 'max:100'],
            'visibility'  => ['required', 'in:public,private'],
        ], [
            'name.unique' => 'You already have a project with this name.',
        ]);

        // If name changes, we could update the slug, but it breaks old URLs. 
        // For simplicity, we keep the original slug.

        $project->update($validated);

        return redirect()->route('projects.edit', $project->slug)
            ->with('success', 'Project settings updated!');
    }

    /**
     * Remove the specified project from storage.
     */
    public function destroy(Project $project)
    {
        if (auth()->id() !== $project->user_id) {
            abort(403, 'Only the owner can delete the project.');
        }

        $project->delete();

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }
}
