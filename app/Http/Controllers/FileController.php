<?php

namespace App\Http\Controllers;

use App\Models\Checkpoint;
use App\Models\Project;
use App\Models\ProjectFile;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class FileController extends Controller
{
    /**
     * Preview a file in the browser (or prompt download if not previewable).
     */
    public function show(Project $project, ProjectFile $file)
    {
        $this->authorizeView($project, $file);

        // For non-previewable files or large text files, just download
        if (!$file->isPreviewable()) {
            return $this->download($project, $file);
        }

        $content = null;
        if ($file->isText()) {
            // Only load text content if under 512 KB to avoid memory issues
            if ($file->size_bytes <= 524288) {
                $content = Storage::disk('local')->get($file->storage_path);
            }
        }

        $file->load('checkpoint');
        return view('projects.files.show', compact('project', 'file', 'content'));
    }

    /**
     * Force-download a file.
     */
    public function download(Project $project, ProjectFile $file)
    {
        $this->authorizeView($project, $file);

        if (!Storage::disk('local')->exists($file->storage_path)) {
            abort(404, 'File not found on disk.');
        }

        return Storage::disk('local')->download($file->storage_path, $file->original_name);
    }

    /**
     * Delete a single file from a checkpoint.
     */
    public function destroy(Project $project, ProjectFile $file)
    {
        if (auth()->id() !== $project->user_id) {
            abort(403);
        }
        abort_if($file->project_id !== $project->id, 404);

        $size = $file->size_bytes;
        Storage::disk('local')->delete($file->storage_path);
        $file->delete();

        // Reclaim quota
        $user = auth()->user();
        $user->decrement('storage_used_bytes', min($size, $user->storage_used_bytes));

        // Update checkpoint size too
        $checkpoint = Checkpoint::find($file->checkpoint_id);
        if ($checkpoint) {
            $checkpoint->decrement('total_size_bytes', min($size, $checkpoint->total_size_bytes));
        }

        return back()->with('success', 'File deleted.');
    }

    // -------------------------------------------------------------------------

    private function authorizeView(Project $project, ProjectFile $file): void
    {
        abort_if($file->project_id !== $project->id, 404);
        if ($project->visibility === 'private' && auth()->id() !== $project->user_id) {
            abort(403);
        }
    }
}
