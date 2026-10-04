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
        
        $sharedProjects = $request->user()->sharedProjects()->latest()->get();

        return view('projects.index', compact('projects', 'sharedProjects'));
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
            'tags' => ['nullable', 'string', 'max:255'],
            'visibility'  => ['required', 'in:public,private'],
        ], [
            'name.unique' => 'You already have a project with this name.',
        ]);

        $project = $request->user()->projects()->create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'visibility' => $validated['visibility'],
        ]);

        if (!empty($validated['tags'])) {
            $tagNames = array_map('trim', explode(',', $validated['tags']));
            $tagNames = array_filter($tagNames);
            $tagNames = array_unique($tagNames);
            
            foreach ($tagNames as $tagName) {
                if (strlen($tagName) > 0 && strlen($tagName) <= 100) {
                    $project->tags()->create(['tag' => $tagName]);
                }
            }
        }

        \App\Models\Activity::log('project_created', $request->user(), $project, [
            'visibility' => $project->visibility,
        ]);

        return redirect()->route('projects.show', $project->slug)
            ->with('success', 'Project created successfully!');
    }

    /**
     * Display the specified project (the Repository).
     */
    public function show(Request $request, Project $project)
    {
        // Access control
        if (!$project->hasAccess(auth()->user())) {
            abort(403, 'This project is private or you do not have access.');
        }

        $latestCheckpoint = $project->latestCheckpoint;
        $checkpoints = $project->checkpoints()->withCount('fileVersions as files_count')->limit(5)->get();


        // Folder navigation
        $currentFolderId = $request->query('folder');
        $currentFolder = null;
        $breadcrumbs = [];

        if ($currentFolderId) {
            $currentFolder = $project->folders()->findOrFail($currentFolderId);
            $breadcrumbs = $currentFolder->breadcrumbs();
        }

        $folders = $project->folders()->where('parent_id', $currentFolderId)->get();
        $files = $project->files()->where('folder_id', $currentFolderId)->with('latestVersion')->get();

        $readmeHtml = null;
        $readmeFile = $files->first(function ($f) {
            return strtolower($f->original_name) === 'readme.md';
        });

        if ($readmeFile && $readmeFile->latestVersion) {
            $path = \Illuminate\Support\Facades\Storage::disk('local')->path($readmeFile->latestVersion->storage_path);
            if (file_exists($path)) {
                $markdown = file_get_contents($path);
                $readmeHtml = Str::markdown($markdown);
            }
        }

        $allFolders = $project->folders()->get();

        return view('projects.show', compact('project', 'latestCheckpoint', 'checkpoints', 'currentFolder', 'currentFolderId', 'breadcrumbs', 'folders', 'files', 'readmeHtml', 'allFolders'));

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
            'tags' => ['nullable', 'string', 'max:255'],
            'visibility'  => ['required', 'in:public,private'],
        ], [
            'name.unique' => 'You already have a project with this name.',
        ]);

        // If name changes, we could update the slug, but it breaks old URLs. 
        // For simplicity, we keep the original slug.

        $project->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'visibility' => $validated['visibility'],
        ]);

        // Process tags
        $project->tags()->delete(); // Clear old tags
        if (!empty($validated['tags'])) {
            $tagNames = array_map('trim', explode(',', $validated['tags']));
            $tagNames = array_filter($tagNames);
            $tagNames = array_unique($tagNames);
            
            foreach ($tagNames as $tagName) {
                if (strlen($tagName) > 0 && strlen($tagName) <= 100) {
                    $project->tags()->create(['tag' => $tagName]);
                }
            }
        }

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

    /**
     * Toggle the star status for the authenticated user.
     */
    public function toggleStar(Project $project)
    {
        $user = auth()->user();
        
        $hasStarred = $user->starredProjects()->where('project_id', $project->id)->exists();

        if ($hasStarred) {
            $user->starredProjects()->detach($project->id);
            $project->decrement('star_count');
            $status = 'unstarred';
        } else {
            $user->starredProjects()->attach($project->id);
            $project->increment('star_count');
            $status = 'starred';
            
            \App\Models\Activity::log('project_starred', $user, $project);
        }

        return response()->json([
            'status' => $status,
            'star_count' => $project->star_count,
        ]);
    }
}
