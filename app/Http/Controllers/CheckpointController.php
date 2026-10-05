<?php

namespace App\Http\Controllers;

use App\ArchivePath;
use App\CheckpointSnapshotService;
use App\Models\Activity;
use App\Models\Checkpoint;
use App\Models\CheckpointFileSnapshot;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Notifications\NewCheckpointNotification;
use App\StorageLifecycle;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\Rule;

class CheckpointController extends Controller
{
    /**
     * Show all checkpoints for a project (project timeline).
     */
    public function index(Project $project)
    {
        $this->authorizeView($project);

        $checkpoints = $project->checkpoints()
            ->withCount([
                'fileSnapshots as snapshot_files_count',
                'fileVersions as legacy_files_count',
            ])
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
    public function store(Request $request, Project $project, StorageLifecycle $storage, CheckpointSnapshotService $snapshots): RedirectResponse
    {
        $this->authorizeOwner($project);

        $user = $request->user();

        $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'folder_id' => ['nullable', 'integer', Rule::exists('project_folders', 'id')->where('project_id', $project->id)],
            'files' => ['required', 'array', 'min:1', 'max:'.config('schoolshare.operations.max_upload_files')],
            'files.*' => ['required', 'file', 'max:'.(int) ceil(config('schoolshare.max_file_bytes') / 1024)],
        ], [
            'files.required' => 'Please select at least one file to upload.',
            'files.*.max' => 'Each file may not be larger than 100 MB.',
        ]);

        $totalUploadBytes = collect($request->file('files'))->sum(fn ($f) => $f->getSize());
        abort_if($totalUploadBytes > config('schoolshare.operations.max_upload_bytes'), 413, 'This upload batch is too large.');
        foreach ($request->file('files') as $uploadedFile) {
            ArchivePath::validate($uploadedFile->getClientOriginalName());
        }

        $storage->run(function (StorageLifecycle $storage) use ($request, $project, $user, $totalUploadBytes, $snapshots) {
            $storage->checkQuota($user, $totalUploadBytes);
            $newNames = [];
            foreach ($request->file('files') as $uploadedFile) {
                $name = $uploadedFile->getClientOriginalName();
                if (! $project->files()->where('folder_id', $request->input('folder_id'))->where('original_name', $name)->exists()) {
                    $newNames[$name] = true;
                }
            }
            $storage->checkProjectCapacity($project, count($request->file('files')), count($newNames));
            $uploads = [];
            $deadline = microtime(true) + config('schoolshare.operations.max_operation_seconds');
            foreach ($request->file('files') as $uploadedFile) {
                abort_if(microtime(true) > $deadline, 503, 'The upload operation timed out.');
                $blob = $storage->create($user, $project, $uploadedFile->getSize(), fn (string $path) => $uploadedFile->storeAs(dirname($path), basename($path), 'local'));
                $uploads[] = [$uploadedFile, $blob];
            }
            DB::transaction(function () use ($request, $project, $user, $totalUploadBytes, $uploads, $snapshots) {
                // Create the checkpoint
                $checkpoint = $project->checkpoints()->create([
                    'user_id' => $user->id,
                    'title' => $request->input('title'),
                    'message' => $request->input('message'),
                    'total_size_bytes' => $totalUploadBytes,
                ]);

                $folderId = $request->input('folder_id');

                // Store each file
                foreach ($uploads as [$uploadedFile, $blob]) {
                    $storedName = $blob->storage_path;
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
                        'version_number' => $projectFile->version_count + 1,
                    ]);

                    // Update ProjectFile
                    $projectFile->latest_version_id = $version->id;
                    $projectFile->version_count += 1;
                    $projectFile->mime_type = $mimeType; // Update mime in case it changed
                    $projectFile->save();
                }

                // Create activity
                Activity::log('checkpoint_created', $user, $checkpoint, [
                    'project_name' => $project->name,
                    'project_slug' => $project->slug,
                ]);
                $snapshots->capture($checkpoint);
            });
        });

        // Notify all collaborators (except the uploader)
        $collaborators = $project->collaborators()->where('user_id', '!=', $user->id)->get();
        Notification::send(
            $collaborators,
            new NewCheckpointNotification($project, $user, $request->input('title'))
        );

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

        $checkpoint->load(['fileSnapshots.fileVersion.projectFile', 'fileVersions.projectFile', 'author', 'comments.user']);

        return view('projects.checkpoints.show', compact('project', 'checkpoint'));
    }

    /**
     * Delete a checkpoint and all its files.
     */
    public function destroy(Project $project, Checkpoint $checkpoint, StorageLifecycle $storage): RedirectResponse
    {
        $this->authorizeOwner($project);
        abort_if($checkpoint->project_id !== $project->id, 404);

        $storage->run(function (StorageLifecycle $storage) use ($checkpoint) {
            $versions = $checkpoint->fileVersions()->get();
            $checkpoint->fileSnapshots()->delete();
            $paths = [];
            $filesToRefresh = [];
            foreach ($versions as $version) {
                if (CheckpointFileSnapshot::where('file_version_id', $version->id)->exists()) {
                    $version->update(['checkpoint_id' => null]);

                    continue;
                }
                $storage->adopt($version->storage_path);
                $paths[] = $version->storage_path;
                $filesToRefresh[$version->project_file_id] = true;
                $version->delete();
            }
            DB::transaction(function () use ($checkpoint, $filesToRefresh) {
                foreach (array_keys($filesToRefresh) as $fileId) {
                    $file = ProjectFile::withTrashed()->find($fileId);
                    if (! $file) {
                        continue;
                    }
                    $latest = $file->versions()->orderByDesc('id')->first();
                    if (! $latest) {
                        $file->delete();

                        continue;
                    }
                    $file->update(['version_count' => $file->versions()->count(), 'latest_version_id' => $latest->id]);
                }
                $checkpoint->delete();
            });
            $storage->collect($paths);
        });

        return redirect()->route('projects.show', $project->slug)
            ->with('success', 'Checkpoint deleted.');
    }

    /**
     * Restore the project to a previous checkpoint.
     */
    public function restore(Project $project, Checkpoint $checkpoint, StorageLifecycle $storage, CheckpointSnapshotService $snapshots): RedirectResponse
    {
        $this->authorizeOwner($project);
        abort_if($checkpoint->project_id !== $project->id, 404);

        $storage->run(fn () => DB::transaction(function () use ($project, $checkpoint, $snapshots) {
            $user = auth()->user();
            $newCheckpoint = $snapshots->restore($project, $checkpoint, $user);

            Activity::log('checkpoint_restored', $user, $newCheckpoint, [
                'project_name' => $project->name,
                'project_slug' => $project->slug,
            ]);
        }));

        return redirect()->route('projects.show', $project->slug)
            ->with('success', 'Project restored to checkpoint: '.$checkpoint->title);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function authorizeView(Project $project): void
    {
        if (! $project->hasAccess(auth()->user())) {
            abort(403, 'This project is private or you do not have access.');
        }
    }

    private function authorizeOwner(Project $project): void
    {
        if (! $project->canEdit(auth()->user())) {
            abort(403, 'Only the project owner or editors can do this.');
        }
    }
}
