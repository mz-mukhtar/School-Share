<?php

namespace App;

use App\Models\Checkpoint;
use App\Models\FileVersion;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\ProjectFolder;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class CheckpointSnapshotService
{
    public function capture(Checkpoint $checkpoint): void
    {
        $project = $checkpoint->project;
        $folders = $project->folders()->orderBy('id')->get();
        $folderPaths = $this->folderPaths($folders);

        $checkpoint->folderSnapshots()->delete();
        foreach ($folders as $folder) {
            $checkpoint->folderSnapshots()->create([
                'source_folder_id' => $folder->id,
                'folder_path' => $folderPaths[$folder->id],
            ]);
        }

        $checkpoint->fileSnapshots()->delete();
        $files = $project->files()->with('latestVersion')->get();
        foreach ($files as $file) {
            if (! $file->latestVersion) {
                continue;
            }
            $checkpoint->fileSnapshots()->create([
                'project_file_id' => $file->id,
                'file_version_id' => $file->latestVersion->id,
                'original_name' => $file->original_name,
                'mime_type' => $file->latestVersion->mime_type ?? $file->mime_type,
                'folder_path' => $file->folder_id ? $folderPaths[$file->folder_id] : [],
            ]);
        }
    }

    public function restore(Project $project, Checkpoint $source, User $user): Checkpoint
    {
        $source->load(['fileSnapshots.fileVersion', 'folderSnapshots']);
        abort_if($source->fileSnapshots->isEmpty(), 409, 'This legacy checkpoint has no snapshot manifest and cannot be restored safely.');
        abort_if($source->fileSnapshots->count() + $source->folderSnapshots->count() > config('schoolshare.operations.max_project_entries'), 413, 'This checkpoint is too large to restore synchronously.');

        foreach ($source->fileSnapshots as $snapshot) {
            $version = $snapshot->fileVersion;
            abort_unless($version && Storage::disk('local')->exists($version->storage_path), 409, 'A checkpoint file is missing. Restore was cancelled.');
            $this->validatePath($snapshot->folder_path ?? []);
            ArchivePath::validate($snapshot->original_name);
        }
        foreach ($source->folderSnapshots as $snapshot) {
            $this->validatePath($snapshot->folder_path);
        }

        $newCheckpoint = $project->checkpoints()->create([
            'user_id' => $user->id,
            'title' => 'Restored: '.$source->title,
            'message' => 'Restored to checkpoint from '.$source->created_at->format('M d, Y H:i'),
            'total_size_bytes' => 0,
        ]);

        $sourceFileIds = $source->fileSnapshots->pluck('project_file_id')->filter()->all();
        $project->files()->whereNotIn('id', $sourceFileIds)->each(function (ProjectFile $file): void {
            $file->delete();
        });

        $project->files()->update(['folder_id' => null]);
        $project->folders()->delete();
        $folderIds = $this->rebuildFolders($project, $source);
        $existingFiles = ProjectFile::withTrashed()->where('project_id', $project->id)->get()->keyBy('id');

        foreach ($source->fileSnapshots as $snapshot) {
            $version = $snapshot->fileVersion;
            $file = $snapshot->project_file_id ? $existingFiles->get($snapshot->project_file_id) : null;
            $folderKey = $this->pathKey($snapshot->folder_path ?? []);
            $folderId = $folderIds[$folderKey] ?? null;

            if (! $file) {
                $file = $project->files()->create([
                    'folder_id' => $folderId,
                    'original_name' => $snapshot->original_name,
                    'mime_type' => $snapshot->mime_type,
                    'version_count' => 0,
                ]);
            } else {
                if ($file->trashed()) {
                    $file->restore();
                }
                $file->update([
                    'folder_id' => $folderId,
                    'original_name' => $snapshot->original_name,
                    'mime_type' => $snapshot->mime_type,
                ]);
            }

            $newVersion = $file->versions()->create([
                'checkpoint_id' => $newCheckpoint->id,
                'storage_path' => $version->storage_path,
                'size_bytes' => $version->size_bytes,
                'mime_type' => $snapshot->mime_type,
                'version_number' => $file->version_count + 1,
            ]);
            $file->update([
                'latest_version_id' => $newVersion->id,
                'version_count' => $file->version_count + 1,
            ]);
        }

        $this->capture($newCheckpoint);

        return $newCheckpoint;
    }

    /** @param Collection<int, ProjectFolder> $folders
     *  @return array<int, list<string>>
     */
    private function folderPaths(Collection $folders): array
    {
        $byId = $folders->keyBy('id');
        $paths = [];
        foreach ($folders as $folder) {
            $paths[$folder->id] = $this->folderPath($folder, $byId, []);
        }

        return $paths;
    }

    /** @param Collection<int, ProjectFolder> $folders
     *  @param array<int, bool> $visited
     *  @return list<string>
     */
    private function folderPath(ProjectFolder $folder, Collection $folders, array $visited): array
    {
        abort_if(isset($visited[$folder->id]), 409, 'A folder cycle prevents checkpoint capture.');
        $visited[$folder->id] = true;
        ArchivePath::validate($folder->name);
        if (! $folder->parent_id) {
            return [$folder->name];
        }
        $parent = $folders->get($folder->parent_id);
        abort_unless($parent && $parent->project_id === $folder->project_id, 409, 'A folder has an invalid parent.');

        return [...$this->folderPath($parent, $folders, $visited), $folder->name];
    }

    /** @return array<string, int> */
    private function rebuildFolders(Project $project, Checkpoint $source): array
    {
        $folderIds = ['' => null];
        $snapshots = $source->folderSnapshots->sortBy(fn ($snapshot): int => count($snapshot->folder_path));
        foreach ($snapshots as $snapshot) {
            $path = $snapshot->folder_path;
            $key = $this->pathKey($path);
            if (array_key_exists($key, $folderIds)) {
                continue;
            }
            $parentPath = array_slice($path, 0, -1);
            $parentKey = $this->pathKey($parentPath);
            abort_unless(array_key_exists($parentKey, $folderIds), 409, 'A snapshot folder has no parent.');
            $folder = $project->folders()->create([
                'parent_id' => $folderIds[$parentKey],
                'name' => last($path),
            ]);
            $folderIds[$key] = $folder->id;
        }

        return $folderIds;
    }

    /** @param list<string> $path */
    private function validatePath(array $path): void
    {
        foreach ($path as $segment) {
            ArchivePath::validate($segment);
        }
    }

    /** @param list<string> $path */
    private function pathKey(array $path): string
    {
        return implode("\0", $path);
    }
}
