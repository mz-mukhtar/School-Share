<?php

namespace App\Http\Controllers;

use App\Models\Activity;
use App\Models\Checkpoint;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\FileVersion;
use App\Models\ProjectFolder;
use App\Models\ProjectFork;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ForkController extends Controller
{
    /**
     * Fork a project to the current user's account.
     */
    public function store(Request $request, Project $project)
    {
        if ($project->visibility === 'private' && auth()->id() !== $project->user_id) {
            abort(403, 'This project is private.');
        }

        if (auth()->id() === $project->user_id) {
            return back()->with('error', 'You cannot fork your own project.');
        }

        $user = auth()->user();

        if (!$user->canCreateProject()) {
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

        try {
            DB::beginTransaction();

            // 1. Create the new Project
            // Ensure unique slug
            $baseName = $project->name;
            $slug = Str::slug($baseName);
            $count = 1;
            while (Project::where('slug', $slug)->exists()) {
                $slug = Str::slug($baseName . '-' . $count);
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
            $allFolders = $project->folders()->orderBy('parent_id')->get(); // Get root folders first

            foreach ($allFolders as $folder) {
                $newFolder = $forkedProject->folders()->create([
                    'name' => $folder->name,
                    'parent_id' => $folder->parent_id ? ($folderMap[$folder->parent_id] ?? null) : null,
                ]);
                $folderMap[$folder->id] = $newFolder->id;
            }

            // 4. Create initial Checkpoint
            $checkpoint = $forkedProject->checkpoints()->create([
                'user_id' => $user->id,
                'title' => 'Initial commit (Forked)',
                'message' => 'Forked from ' . $project->user->username . '/' . $project->slug,
                'total_size_bytes' => 0, // Will update as we copy files
            ]);

            // 5. Copy Files (only the latest versions)
            $totalBytes = 0;
            $allFiles = $project->files()->with('latestVersion')->get();

            foreach ($allFiles as $file) {
                $version = $file->latestVersion;
                if (!$version) continue;

                // Check quota
                if ($totalBytes + $version->size_bytes > $user->remainingStorageBytes()) {
                    DB::rollBack();
                    return back()->with('error', 'Not enough storage space to fork this project.');
                }

                // Create new ProjectFile
                $newFile = $forkedProject->files()->create([
                    'folder_id' => $file->folder_id ? ($folderMap[$file->folder_id] ?? null) : null,
                    'original_name' => $file->original_name,
                    'mime_type' => $file->mime_type,
                    'version_count' => 1,
                ]);

                // Copy physical file
                $oldPath = $version->storage_path;
                $newPath = "projects/{$user->id}/{$forkedProject->id}/{$checkpoint->id}/" . Str::random(10) . '_' . $file->original_name;
                
                if (Storage::disk('local')->exists($oldPath)) {
                    Storage::disk('local')->copy($oldPath, $newPath);
                }

                // Create new FileVersion
                $newVersion = $newFile->versions()->create([
                    'checkpoint_id' => $checkpoint->id,
                    'storage_path' => $newPath,
                    'size_bytes' => $version->size_bytes,
                ]);

                $newFile->update(['latest_version_id' => $newVersion->id]);
                $totalBytes += $version->size_bytes;
            }

            $checkpoint->update(['total_size_bytes' => $totalBytes]);
            $user->increment('storage_used_bytes', $totalBytes);

            // 6. Log Activity
            Activity::log('project_created', $user, $forkedProject, [
                'project_name' => $forkedProject->name,
                'project_slug' => $forkedProject->slug,
                'forked_from' => $project->name,
            ]);

            DB::commit();

            return redirect()->route('projects.show', $forkedProject->slug)
                ->with('success', 'Project forked successfully!');

        } catch (\Exception $e) {
            DB::rollBack();
            return back()->with('error', 'An error occurred while forking: ' . $e->getMessage());
        }
    }
}
