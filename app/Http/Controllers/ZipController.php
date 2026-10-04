<?php

namespace App\Http\Controllers;

use App\Models\Project;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use ZipArchive;

class ZipController extends Controller
{
    public function downloadProject(Project $project)
    {
        if (!$project->hasAccess(auth()->user())) {
            abort(403);
        }

        // Use storage/app/temp — same disk as uploaded files (local)
        $tempDir = storage_path('app/temp');
        if (!is_dir($tempDir)) {
            mkdir($tempDir, 0755, true);
        }

        $zipFileName = Str::slug($project->name) . '-' . uniqid() . '.zip';
        $zipFilePath = $tempDir . '/' . $zipFileName;

        $zip = new ZipArchive();
        $result = $zip->open($zipFilePath, ZipArchive::CREATE | ZipArchive::OVERWRITE);

        if ($result !== true) {
            return back()->with('error', 'Could not create zip file (code: ' . $result . ').');
        }

        $fileCount = $this->addFolderToZip($zip, $project, null, '');

        // If empty project, still add a placeholder so the zip is valid
        if ($fileCount === 0) {
            $zip->addFromString('README.txt', 'This project has no files yet.');
        }

        $zip->close();

        if (!file_exists($zipFilePath)) {
            return back()->with('error', 'Zip file could not be written to disk.');
        }

        return response()
            ->download($zipFilePath, Str::slug($project->name) . '.zip')
            ->deleteFileAfterSend(true);
    }

    /**
     * Recursively add project files/folders to the zip. Returns total file count added.
     */
    private function addFolderToZip(ZipArchive $zip, Project $project, ?int $folderId, string $currentPath): int
    {
        $count = 0;

        $files = $project->files()->where('folder_id', $folderId)->with('latestVersion')->get();
        foreach ($files as $file) {
            $version = $file->latestVersion;
            if ($version && Storage::disk('local')->exists($version->storage_path)) {
                $absolutePath = Storage::disk('local')->path($version->storage_path);
                $zip->addFile($absolutePath, $currentPath . $file->original_name);
                $count++;
            }
        }

        $folders = $project->folders()->where('parent_id', $folderId)->get();
        foreach ($folders as $folder) {
            $zip->addEmptyDir($currentPath . $folder->name);
            $count += $this->addFolderToZip($zip, $project, $folder->id, $currentPath . $folder->name . '/');
        }

        return $count;
    }
}
