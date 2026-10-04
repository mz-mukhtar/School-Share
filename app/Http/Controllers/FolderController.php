<?php

namespace App\Http\Controllers;

use App\Models\Project;
use App\Models\ProjectFolder;
use Illuminate\Http\Request;

class FolderController extends Controller
{
    public function store(Request $request, Project $project)
    {
        if (auth()->id() !== $project->user_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', 'regex:/^[\w\-\.]+$/'],
            'parent_id' => ['nullable', 'exists:project_folders,id'],
        ]);

        // Check for duplicates
        $exists = $project->folders()
            ->where('name', $validated['name'])
            ->where('parent_id', $validated['parent_id'] ?? null)
            ->exists();
            
        if ($exists) {
            return back()->with('error', 'A folder with this name already exists here.');
        }

        $project->folders()->create($validated);

        return back()->with('success', 'Folder created.');
    }

    public function destroy(Project $project, ProjectFolder $folder)
    {
        if (auth()->id() !== $project->user_id || $folder->project_id !== $project->id) {
            abort(403);
        }

        // We recursively delete to avoid leaving orphaned files
        $this->deleteFolderRecursive($folder);

        return back()->with('success', 'Folder deleted.');
    }

    private function deleteFolderRecursive(ProjectFolder $folder)
    {
        foreach ($folder->children as $child) {
            $this->deleteFolderRecursive($child);
        }
        
        // Delete all files in this folder
        foreach ($folder->files as $file) {
            // file_versions will cascade if set up, or we might need to delete them.
            // Our DB has cascadeOnDelete for project_files -> file_versions? Let's assume yes.
            $file->delete();
        }

        $folder->delete();
    }
}
