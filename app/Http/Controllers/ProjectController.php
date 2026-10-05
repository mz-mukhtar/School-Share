<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\CheckpointFileSnapshot;
use App\Models\CheckpointFolderSnapshot;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\User;
use App\StorageLifecycle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProjectController extends Controller
{
    /**
     * Display a listing of the user's projects.
     */
    public function index(Request $request)
    {
        $query = $request->user()->projects()->latest();

        if ($search = $request->input('q')) {
            $query->where(function (Builder $query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%");
            });
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
        if (! $request->user()->canCreateProject()) {
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
        if (! $request->user()->canCreateProject()) {
            return redirect()->route('projects.index')
                ->with('error', 'You have reached the maximum number of projects for your plan.');
        }

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects')->where('user_id', $request->user()->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'string', 'max:255'],
            'visibility' => ['required', 'in:public,private'],
        ], [
            'name.unique' => 'You already have a project with this name.',
        ]);

        $project = DB::transaction(function () use ($request, $validated): Project {
            $owner = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            if (! $owner->canCreateProject()) {
                abort(409, 'You have reached the maximum number of projects for your plan.');
            }

            return Project::createForOwner($owner, [
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'visibility' => $validated['visibility'],
            ]);
        });

        if (! empty($validated['tags'])) {
            $tagNames = array_map('trim', explode(',', $validated['tags']));
            $tagNames = array_filter($tagNames);
            $tagNames = array_unique($tagNames);

            foreach ($tagNames as $tagName) {
                if (strlen($tagName) > 0 && strlen($tagName) <= 100) {
                    $project->tags()->create(['tag' => $tagName]);
                }
            }
        }

        Activity::log('project_created', $request->user(), $project, [
            'visibility' => $project->visibility,
        ]);

        return redirect()->route('projects.show', $project->slug)
            ->with('success', 'Project created successfully!');
    }

    /**
     * Display the specified project (the Repository).
     */
    public function show(Request $request, Project $project, StorageLifecycle $storage): View
    {
        // Access control
        if (! $project->hasAccess(auth()->user())) {
            abort(403, 'This project is private or you do not have access.');
        }

        $latestCheckpoint = $project->latestCheckpoint;
        $checkpoints = $project->checkpoints()->withCount([
            'fileSnapshots as snapshot_files_count',
            'fileVersions as legacy_files_count',
        ])->limit(5)->get();

        // Folder navigation
        $currentFolderId = $request->query('folder');
        $currentFolder = null;
        $breadcrumbs = [];

        if ($currentFolderId) {
            $currentFolder = $project->folders()->findOrFail($currentFolderId);
            $breadcrumbs = $currentFolder->breadcrumbs();
        }

        $folders = $project->folders()->where('parent_id', $currentFolderId)->get();
        $files = $project->files()->where('folder_id', $currentFolderId)->whereNotNull('latest_version_id')->with('latestVersion')->get();

        $readmeHtml = null;
        $readmeFile = $files->first(function ($f) {
            return strtolower($f->original_name) === 'readme.md';
        });

        if ($readmeFile && $readmeFile->latestVersion) {
            $markdown = $storage->readText($readmeFile->latestVersion, config('schoolshare.operations.max_readme_bytes'));
            if ($markdown !== null) {
                $readmeHtml = Str::markdown($markdown, [
                    'html_input' => 'escape',
                    'allow_unsafe_links' => false,
                    'max_nesting_level' => 32,
                    'max_delimiters_per_line' => 1000,
                ]);
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
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('projects')->where('user_id', $request->user()->id)->ignore($project->id),
            ],
            'description' => ['nullable', 'string', 'max:1000'],
            'tags' => ['nullable', 'string', 'max:255'],
            'visibility' => ['required', 'in:public,private'],
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
        if (! empty($validated['tags'])) {
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
    public function destroy(Project $project, StorageLifecycle $storage): RedirectResponse
    {
        if (auth()->id() !== $project->user_id) {
            abort(403, 'Only the owner can delete the project.');
        }

        abort_if($project->hasForeignFolderReferences(), 409, 'This project has invalid folder references. Repair them before deleting it.');

        $storage->run(function (StorageLifecycle $storage) use ($project) {
            abort_if($project->hasForeignFolderReferences(), 409, 'Repair invalid folder references first.');
            $files = ProjectFile::withTrashed()->where('project_id', $project->id)->with('versions')->get();
            $storage->deleteFiles($files, function () use ($project): void {
                CheckpointFileSnapshot::query()
                    ->whereHas('checkpoint', fn ($query) => $query->where('project_id', $project->id))
                    ->delete();
                CheckpointFolderSnapshot::query()
                    ->whereHas('checkpoint', fn ($query) => $query->where('project_id', $project->id))
                    ->delete();

                $project->delete();
            });
        });

        return redirect()->route('projects.index')
            ->with('success', 'Project deleted successfully.');
    }

    /**
     * Toggle the star status for the authenticated user.
     */
    public function toggleStar(Project $project)
    {
        $user = auth()->user();

        abort_unless($project->hasAccess($user), 403);

        [$status, $starCount] = DB::transaction(function () use ($project, $user): array {
            $lockedProject = Project::query()->lockForUpdate()->findOrFail($project->id);
            $hasStarred = $user->starredProjects()->where('project_id', $lockedProject->id)->exists();

            if ($hasStarred) {
                $user->starredProjects()->detach($lockedProject->id);
                $lockedProject->update(['star_count' => max(0, $lockedProject->star_count - 1)]);

                return ['unstarred', $lockedProject->star_count];
            }

            $user->starredProjects()->syncWithoutDetaching([$lockedProject->id]);
            $lockedProject->increment('star_count');
            Activity::log('project_starred', $user, $lockedProject);

            return ['starred', $lockedProject->fresh()->star_count];
        });

        return response()->json([
            'status' => $status,
            'star_count' => $starCount,
        ]);
    }
}
