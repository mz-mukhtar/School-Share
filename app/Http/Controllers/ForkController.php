<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Project;
use App\Models\ProjectFork;
use App\StorageLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ForkController extends Controller
{
    /**
     * Fork a project to the current user's account.
     */
    public function store(Request $request, Project $project, StorageLifecycle $storage): RedirectResponse
    {
        if ($project->visibility === 'private' && auth()->id() !== $project->user_id) {
            abort(403, 'This project is private.');
        }

        if (auth()->id() === $project->user_id) {
            return back()->with('error', 'You cannot fork your own project.');
        }

        $user = auth()->user();

        if (! $user->canCreateProject()) {
            return back()->with('error', 'You have reached the maximum number of projects for your plan.');
        }

        // Check if already forked
        $existingFork = ProjectFork::where('original_project_id', $project->id)
            ->whereHas('fork', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })->first();

        if ($existingFork) {
            return redirect()->route('projects.show', $existingFork->fork->slug)
                ->with('info', 'You already forked this project.');
        }

        $forkedProject = $storage->run(function (StorageLifecycle $storage) use ($project, $user) {
            abort_unless($user->fresh()->canCreateProject(), 422, 'Your project limit has been reached.');
            $existingFork = ProjectFork::where('original_project_id', $project->id)->whereHas('fork', fn ($query) => $query->where('user_id', $user->id))->first();
            if ($existingFork) {
                return $existingFork->fork;
            }
            abort_if($project->files()->count() + $project->folders()->count() > config('schoolshare.operations.max_project_entries'), 413, 'This project is too large to fork synchronously.');
            $allFiles = $project->files()->with('latestVersion')->get();
            $totalBytes = 0;
            foreach ($allFiles as $file) {
                if ($file->latestVersion) {
                    abort_unless(Storage::disk('local')->exists($file->latestVersion->storage_path), 409, 'A source file is missing. Fork was cancelled.');
                    $totalBytes += Storage::disk('local')->size($file->latestVersion->storage_path);
                }
            }
            abort_if($totalBytes > config('schoolshare.operations.max_archive_bytes'), 413, 'This project is too large to fork synchronously.');
            $storage->checkQuota($user, $totalBytes);
            $copied = [];
            $deadline = microtime(true) + config('schoolshare.operations.max_operation_seconds');
            foreach ($allFiles as $file) {
                if ($file->latestVersion) {
                    abort_if(microtime(true) > $deadline, 503, 'The fork operation timed out.');
                    $oldPath = $file->latestVersion->storage_path;
                    $copied[$file->id] = $storage->create($user, $project, Storage::disk('local')->size($oldPath), fn (string $path) => Storage::disk('local')->copy($oldPath, $path));
                }
            }

            return DB::transaction(function () use ($project, $user, $allFiles, $copied, $totalBytes, $deadline) {

                // 1. Create the new Project
                // Ensure unique slug
                $baseName = $project->name;
                $slug = Str::slug($baseName);
                $count = 1;
                while (Project::where('slug', $slug)->exists()) {
                    $slug = Str::slug($baseName.'-'.$count);
                    $count++;
                }

                $forkedProject = Project::create([
                    'user_id' => $user->id,
                    'name' => $baseName,
                    'slug' => $slug,
                    'description' => $project->description,
                    'visibility' => 'public', // Default to public or keep original? Default public.
                ]);

                // Copy tags
                foreach ($project->tags as $tag) {
                    $forkedProject->tags()->create(['tag' => $tag->tag]);
                }

                // 2. Record the fork
                ProjectFork::create([
                    'original_project_id' => $project->id,
                    'forked_project_id' => $forkedProject->id,
                ]);

                // 3. Duplicate Folders
                // We need a mapping of old_folder_id => new_folder_id to maintain hierarchy
                $folderMap = [];
                $allFolders = $project->folders()->get();

                foreach ($allFolders as $folder) {
                    abort_if(microtime(true) > $deadline, 503, 'The fork operation timed out.');
                    $newFolder = $forkedProject->folders()->create([
                        'name' => $folder->name,
                        'parent_id' => null,
                    ]);
                    $folderMap[$folder->id] = $newFolder->id;
                }
                foreach ($allFolders as $folder) {
                    if ($folder->parent_id) {
                        abort_unless(isset($folderMap[$folder->parent_id]), 409, 'A source folder has an invalid parent. Fork was cancelled.');
                        $forkedProject->folders()->whereKey($folderMap[$folder->id])->update(['parent_id' => $folderMap[$folder->parent_id]]);
                    }
                }

                // 4. Create initial Checkpoint
                $checkpoint = $forkedProject->checkpoints()->create([
                    'user_id' => $user->id,
                    'title' => 'Initial commit (Forked)',
                    'message' => 'Forked from '.$project->owner->username.'/'.$project->slug,
                    'total_size_bytes' => 0, // Will update as we copy files
                ]);

                // 5. Copy Files (only the latest versions)
                foreach ($allFiles as $file) {
                    abort_if(microtime(true) > $deadline, 503, 'The fork operation timed out.');
                    $version = $file->latestVersion;
                    if (! $version) {
                        continue;
                    }

                    abort_if($file->folder_id && ! isset($folderMap[$file->folder_id]), 409, 'A source file has an invalid folder. Fork was cancelled.');
                    // Create new ProjectFile
                    $newFile = $forkedProject->files()->create([
                        'folder_id' => $file->folder_id ? ($folderMap[$file->folder_id] ?? null) : null,
                        'original_name' => $file->original_name,
                        'mime_type' => $file->mime_type,
                        'version_count' => 1,
                    ]);

                    // Create new FileVersion
                    $newVersion = $newFile->versions()->create([
                        'checkpoint_id' => $checkpoint->id,
                        'storage_path' => $copied[$file->id]->storage_path,
                        'size_bytes' => $copied[$file->id]->size_bytes,
                        'mime_type' => $version->mime_type,
                        'version_number' => 1,
                    ]);

                    $newFile->update(['latest_version_id' => $newVersion->id]);
                }

                $checkpoint->update(['total_size_bytes' => $totalBytes]);

                // 6. Log Activity
                Activity::log('project_created', $user, $forkedProject, [
                    'project_name' => $forkedProject->name,
                    'project_slug' => $forkedProject->slug,
                    'forked_from' => $project->name,
                ]);

                return $forkedProject;
            });
        });

        return redirect()->route('projects.show', $forkedProject->slug)->with('success', 'Project forked successfully!');
    }
}
