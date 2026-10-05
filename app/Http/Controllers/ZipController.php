<?php

namespace App\Http\Controllers;

use App\ArchivePath;
use App\Models\Project;
use App\StorageLifecycle;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

class ZipController extends Controller
{
    public function downloadProject(Project $project, StorageLifecycle $storage): BinaryFileResponse
    {
        if (! $project->hasAccess(auth()->user())) {
            abort(403);
        }

        return $storage->run(function () use ($project) {
            $disk = Storage::disk('local');
            abort_if($project->folders()->count() + $project->files()->count() > config('schoolshare.operations.max_project_entries'), 413, 'This project is too large to export synchronously.');
            $deadline = microtime(true) + config('schoolshare.operations.max_operation_seconds');
            $folders = $project->folders()->get()->keyBy('id');
            $entries = [];
            $names = [];
            foreach ($folders as $folder) {
                $name = $this->folderPath($folder->id, $folders).'/';
                $this->reserveName($name, $names);
                $entries[] = [$name, null];
            }
            $total = 0;
            foreach ($project->files()->with('latestVersion')->get() as $file) {
                abort_if(microtime(true) > $deadline, 503, 'The export operation timed out.');
                ArchivePath::validate($file->original_name);
                if (! $file->latestVersion) {
                    continue;
                }
                $name = ($file->folder_id ? $this->folderPath($file->folder_id, $folders).'/' : '').$file->original_name;
                $this->reserveName($name, $names);
                $path = $file->latestVersion->storage_path;
                abort_unless($disk->exists($path), 409, 'A project file is missing. Export was cancelled.');
                $total += $disk->size($path);
                abort_if($total > config('schoolshare.operations.max_archive_bytes'), 413, 'This project is too large to export synchronously.');
                $entries[] = [$name, $disk->path($path)];
            }
            abort_unless($disk->makeDirectory('exports'), 503, 'The export directory could not be created.');
            $tempPath = 'exports/'.Str::uuid().'.zip';
            $absolutePath = $disk->path($tempPath);
            $zip = new ZipArchive;
            $opened = false;
            $success = false;
            try {
                abort_unless($zip->open($absolutePath, ZipArchive::CREATE | ZipArchive::EXCL) === true, 503, 'The archive could not be created.');
                $opened = true;
                foreach ($entries as [$name, $path]) {
                    abort_if(microtime(true) > $deadline, 503, 'The export operation timed out.');
                    abort_unless($path === null ? $zip->addEmptyDir(rtrim($name, '/')) : $zip->addFile($path, $name), 503, 'An archive entry could not be written.');
                    if ($path !== null) {
                        abort_unless($zip->setCompressionName($name, ZipArchive::CM_STORE), 503, 'The archive entry could not be configured.');
                    }
                }
                if ($entries === []) {
                    abort_unless($zip->addFromString('README.txt', 'This project has no files yet.'), 503);
                }
                $closed = $zip->close();
                $opened = false;
                abort_unless($closed && $disk->exists($tempPath), 503, 'The archive could not be finalized.');
                abort_if(microtime(true) > $deadline, 503, 'The export operation timed out.');
                $response = response()->download($absolutePath, (Str::slug($project->name) ?: 'project').'.zip')->deleteFileAfterSend(true);
                $success = true;

                return $response;
            } finally {
                if ($opened) {
                    $zip->close();
                }
                if (! $success && $disk->exists($tempPath) && ! $disk->delete($tempPath)) {
                    report(new \RuntimeException('Temporary export cleanup failed.'));
                }
            }
        });
    }

    /**
     * Recursively add project files/folders to the zip. Returns total file count added.
     */
    private function folderPath(int $folderId, Collection $folders): string
    {
        $segments = [];
        $visited = [];
        while ($folderId) {
            abort_if(isset($visited[$folderId]) || ! isset($folders[$folderId]), 422, 'The project has invalid folder references. Repair them before exporting.');
            $visited[$folderId] = true;
            $folder = $folders[$folderId];
            ArchivePath::validate($folder->name);
            array_unshift($segments, $folder->name);
            abort_if(count($segments) > config('schoolshare.operations.max_folder_depth'), 413, 'The folder tree is too deep to export.');
            $folderId = $folder->parent_id ?? 0;
        }

        return implode('/', $segments);
    }

    /** @param array<string, bool> $names */
    private function reserveName(string $name, array &$names): void
    {
        $key = mb_strtolower(rtrim($name, '/'));
        abort_if(isset($names[$key]), 422, 'The project has colliding archive names. Rename them before exporting.');
        $names[$key] = true;
    }
}
