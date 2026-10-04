<?php

namespace App\Http\Controllers;

use App\Models\Checkpoint;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CheckpointController extends Controller
{
    /**
     * Show all checkpoints for a project (project timeline).
     */
    public function index(Project $project)
    {
        $this->authorizeView($project);

        $checkpoints = $project->checkpoints()
            ->withCount('fileVersions as files_count')
            ->with('author')
            ->paginate(10);

        return view('projects.checkpoints.index', compact('project', 'checkpoints'));
    }

    /**
     * Show the form for creating a new checkpoint (upload form).
     */
    public function create(Project $project)
    {
        $this->authorizeOwner($project);

        $user = auth()->user();

        if ($user->isStorageFull()) {
            return redirect()->route('projects.show', $project->slug)
                ->with('error', 'Your storage quota is full. Delete some files to free up space.');
        }

        return view('projects.checkpoints.create', compact('project', 'user'));
    }

    /**
     * Store a new checkpoint and upload files.
     */
    public function store(Request $request, Project $project)
    {
        $this->authorizeOwner($project);

        $user = $request->user();

        $request->validate([
            'title'     => ['required', 'string', 'max:255'],
            'message'   => ['nullable', 'string', 'max:2000'],
            'folder_id' => ['nullable', 'exists:project_folders,id'],
            'files'     => ['required', 'array', 'min:1'],
            'files.*'   => ['file', 'max:102400'], // 100 MB per file (102400 KB)
        ], [
            'files.required'   => 'Please select at least one file to upload.',
            'files.*.max'      => 'Each file may not be larger than 100 MB.',
        ]);

        // Check total size vs remaining quota
        $totalUploadBytes = collect($request->file('files'))->sum(fn($f) => $f->getSize());
        if ($totalUploadBytes > $user->remainingStorageBytes()) {
            return back()->withInput()->with('error', 'Not enough storage space. You have ' . $user->storageUsedHuman() . ' / ' . $user->maxStorageHuman() . ' used.');
        }

        DB::transaction(function () use ($request, $project, $user, $totalUploadBytes) {
            // Create the checkpoint
            $checkpoint = $project->checkpoints()->create([
                'user_id'          => $user->id,
                'title'            => $request->input('title'),
                'message'          => $request->input('message'),
                'total_size_bytes' => $totalUploadBytes,
            ]);

            $folderId = $request->input('folder_id');

            // Store each file
            foreach ($request->file('files') as $uploadedFile) {
                $dir = "projects/{$user->id}/{$project->id}/{$checkpoint->id}";
                $storedName = $uploadedFile->store($dir, 'local');
                $originalName = $uploadedFile->getClientOriginalName();
                $mimeType = $uploadedFile->getMimeType();
                $size = $uploadedFile->getSize();

                // Find existing ProjectFile in this folder with same name
                $projectFile = ProjectFile::firstOrCreate(
                    [
                        'project_id' => $project->id,
                        'folder_id' => $folderId,
                        'original_name' => $originalName,
                    ],
                    [
                        'mime_type' => $mimeType,
                        'version_count' => 0,
                    ]
                );

                // Create FileVersion
                $version = $projectFile->versions()->create([
                    'checkpoint_id' => $checkpoint->id,
                    'storage_path' => $storedName,
                    'size_bytes' => $size,
                    'mime_type' => $mimeType,
                ]);

                // Update ProjectFile
                $projectFile->latest_version_id = $version->id;
                $projectFile->version_count += 1;
                $projectFile->mime_type = $mimeType; // Update mime in case it changed
                $projectFile->save();
            }

            // Update user storage quota
            $user->increment('storage_used_bytes', $totalUploadBytes);
            
            // Create activity
            \App\Models\Activity::log('checkpoint_created', $user, $checkpoint, [
                'project_name' => $project->name,
                'project_slug' => $project->slug,
            ]);
        });

        return redirect()->route('projects.show', $project->slug)
            ->with('success', 'Checkpoint created successfully!');
    }

    /**
     * Show a specific checkpoint with its files.
     */
    public function show(Project $project, Checkpoint $checkpoint)
    {
        $this->authorizeView($project);

        // Make sure the checkpoint belongs to the project
        abort_if($checkpoint->project_id !== $project->id, 404);

        $checkpoint->load(['files', 'author']);

        return view('projects.checkpoints.show', compact('project', 'checkpoint'));
    }

    /**
     * Delete a checkpoint and all its files.
     */
    public function destroy(Project $project, Checkpoint $checkpoint)
    {
        $this->authorizeOwner($project);
        abort_if($checkpoint->project_id !== $project->id, 404);

        DB::transaction(function () use ($project, $checkpoint) {
            $user = auth()->user();
            $totalSize = $checkpoint->total_size_bytes;

            // Delete stored files from disk
            foreach ($checkpoint->fileVersions as $version) {
                $pathCount = \App\Models\FileVersion::where('storage_path', $version->storage_path)->count();
                if ($pathCount <= 1) {
                    Storage::disk('local')->delete($version->storage_path);
                }
                
                $file = $version->projectFile;
                if ($file && $file->version_count <= 1) {
                    $file->delete();
                } elseif ($file) {
                    $file->decrement('version_count');
                    if ($file->latest_version_id === $version->id) {
                        $file->latest_version_id = null;
                        $previous = $file->versions()->where('id', '!=', $version->id)->latest()->first();
                        if ($previous) {
                            $file->latest_version_id = $previous->id;
                        }
                        $file->save();
                    }
                }
            }

            // Delete DB records
            $checkpoint->fileVersions()->delete();
            $checkpoint->delete();

            // Reclaim storage
            $user->decrement('storage_used_bytes', min($totalSize, $user->storage_used_bytes));
        });

        return redirect()->route('projects.show', $project->slug)
            ->with('success', 'Checkpoint deleted.');
    }

    /**
     * Restore the project to a previous checkpoint.
     */
    public function restore(Project $project, Checkpoint $checkpoint)
    {
        $this->authorizeOwner($project);
        abort_if($checkpoint->project_id !== $project->id, 404);

        DB::transaction(function () use ($project, $checkpoint) {
            $user = auth()->user();

            $newCheckpoint = $project->checkpoints()->create([
                'user_id' => $user->id,
                'title' => 'Restored: ' . $checkpoint->title,
                'message' => 'Restored to checkpoint from ' . $checkpoint->created_at->format('M d, Y H:i'),
                'total_size_bytes' => 0, // No new data uploaded
            ]);

            $files = $project->files;
            
            foreach ($files as $file) {
                $versionAtCheckpoint = $file->versions()
                    ->where('checkpoint_id', '<=', $checkpoint->id)
                    ->orderByDesc('id')
                    ->first();

                if ($versionAtCheckpoint) {
                    $newVersion = $file->versions()->create([
                        'checkpoint_id' => $newCheckpoint->id,
                        'storage_path'  => $versionAtCheckpoint->storage_path,
                        'size_bytes'    => $versionAtCheckpoint->size_bytes,
                        'mime_type'     => $versionAtCheckpoint->mime_type,
                    ]);

                    $file->latest_version_id = $newVersion->id;
                    $file->version_count += 1;
                    $file->save();
                } else {
                    $file->latest_version_id = null;
                    $file->save();
                }
            }
            
            \App\Models\Activity::log('checkpoint_restored', $user, $newCheckpoint, [
                'project_name' => $project->name,
                'project_slug' => $project->slug,
            ]);
        });

        return redirect()->route('projects.show', $project->slug)
            ->with('success', 'Project restored to checkpoint: ' . $checkpoint->title);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function authorizeView(Project $project): void
    {
        if (!$project->hasAccess(auth()->user())) {
            abort(403, 'This project is private or you do not have access.');
        }
    }

    private function authorizeOwner(Project $project): void
    {
        if (!$project->canEdit(auth()->user())) {
            abort(403, 'Only the project owner or editors can do this.');
        }
    }
}
