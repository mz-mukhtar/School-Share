<?php

namespace App\Http\Controllers;

use App\ArchivePath;
use App\Models\Project;
use App\Models\ProjectFolder;
use App\StorageLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FolderController extends Controller
{
    public function store(Request $request, Project $project, StorageLifecycle $storage): RedirectResponse
    {
        if (! $project->canEdit($request->user())) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[\w\-\.]+$/'],
            'parent_id' => ['nullable', 'integer', Rule::exists('project_folders', 'id')->where('project_id', $project->id)],
        ]);
        ArchivePath::validate($validated['name']);

        // Check for duplicates
        $exists = $project->folders()
            ->where('name', $validated['name'])
            ->where('parent_id', $validated['parent_id'] ?? null)
            ->exists();

        if ($exists) {
            return back()->with('error', 'A folder with this name already exists here.');
        }

        $storage->run(function (StorageLifecycle $storage) use ($project, $validated) {
            $storage->checkProjectCapacity($project, 0, 1);
            $project->folders()->create($validated);
        });

        return back()->with('success', 'Folder created.');
    }

    public function destroy(Project $project, ProjectFolder $folder, StorageLifecycle $storage): RedirectResponse
    {
        abort_if($folder->project_id !== $project->id, 404);
        if (! $project->canEdit(auth()->user())) {
            abort(403);
        }

        abort_if($project->hasForeignFolderReferences(), 409, 'This project has invalid folder references. Repair them before deleting folders.');

        $storage->run(function (StorageLifecycle $storage) use ($project, $folder) {
            abort_if($project->hasForeignFolderReferences(), 409, 'Repair invalid folder references first.');
            $ids = [$folder->id];
            $frontier = [$folder->id];
            while ($frontier !== []) {
                $frontier = $project->folders()->whereIn('parent_id', $frontier)->whereNotIn('id', $ids)->pluck('id')->all();
                $ids = array_merge($ids, $frontier);
                abort_if(count($ids) > config('schoolshare.operations.max_project_entries'), 413, 'Too many folders to delete synchronously.');
            }
            $files = $project->files()->whereIn('folder_id', $ids)->with('versions')->get();
            $storage->deleteFiles($files, function () use ($project, $ids, $files) {
                foreach ($files as $file) {
                    $file->delete();
                }
                $project->folders()->whereIn('id', $ids)->delete();
            });
        });

        return back()->with('success', 'Folder deleted.');
    }
}
