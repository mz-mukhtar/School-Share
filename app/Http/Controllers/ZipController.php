<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use ZipArchive;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ZipController extends Controller
{
    public function downloadProject(Project $project)
    {
        if ($project->visibility === 'private' && auth()->id() !== $project->user_id) {
            abort(403);
        }

        $zip = new ZipArchive();
        $zipFileName = Str::slug($project->name) . '.zip';
        $zipFilePath = storage_path('app/public/temp/' . $zipFileName);

        if (!file_exists(storage_path('app/public/temp'))) {
            mkdir(storage_path('app/public/temp'), 0755, true);
        }

        if ($zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE) === TRUE) {
            $this->addFolderToZip($zip, $project, null, '');
            $zip->close();
            
            return response()->download($zipFilePath)->deleteFileAfterSend(true);
        }

        return back()->with('error', 'Could not create zip file.');
    }

    private function addFolderToZip($zip, $project, $folderId, $currentPath)
    {
        $files = $project->files()->where('folder_id', $folderId)->get();
        foreach ($files as $file) {
            $version = $file->latestVersion;
            if ($version && Storage::disk('local')->exists($version->storage_path)) {
                $filePath = Storage::disk('local')->path($version->storage_path);
                $zip->addFile($filePath, $currentPath . $file->original_name);
            }
        }

        // Recursively add child folders
        $folders = $project->folders()->where('parent_id', $folderId)->get();
        foreach ($folders as $folder) {
            $zip->addEmptyDir($currentPath . $folder->name);
            $this->addFolderToZip($zip, $project, $folder->id, $currentPath . $folder->name . '/');
        }
    }
}
