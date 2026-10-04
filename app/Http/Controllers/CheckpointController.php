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
            ->withCount('files')
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
            'title'   => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'files'   => ['required', 'array', 'min:1'],
            'files.*' => ['file', 'max:102400'], // 100 MB per file (102400 KB)
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

            // Store each file
            foreach ($request->file('files') as $uploadedFile) {
                $dir = "projects/{$user->id}/{$project->id}/{$checkpoint->id}";
                $storedName = $uploadedFile->store($dir, 'local');

                $checkpoint->files()->create([
                    'project_id'    => $project->id,
                    'original_name' => $uploadedFile->getClientOriginalName(),
                    'storage_path'  => $storedName,
                    'mime_type'     => $uploadedFile->getMimeType(),
                    'size_bytes'    => $uploadedFile->getSize(),
                ]);
            }

            // Update user storage quota
            $user->increment('storage_used_bytes', $totalUploadBytes);
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
            foreach ($checkpoint->files as $file) {
                Storage::disk('local')->delete($file->storage_path);
            }

            // Delete DB records
            $checkpoint->files()->delete();
            $checkpoint->delete();

            // Reclaim storage
            $user->decrement('storage_used_bytes', min($totalSize, $user->storage_used_bytes));
        });

        return redirect()->route('projects.show', $project->slug)
            ->with('success', 'Checkpoint deleted.');
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    private function authorizeView(Project $project): void
    {
        if ($project->visibility === 'private' && auth()->id() !== $project->user_id) {
            abort(403, 'This project is private.');
        }
    }

    private function authorizeOwner(Project $project): void
    {
        if (auth()->id() !== $project->user_id) {
            abort(403, 'Only the project owner can do this.');
        }
    }
}
