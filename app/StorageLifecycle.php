<?php

namespace App;

use App\Models\FileVersion;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\StoredBlob;
use App\Models\User;
use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StorageLifecycle
{
    /** @var list<string> */
    private array $createdPaths = [];

    /**
     * The application uses one private local disk. A non-expiring filesystem lock
     * coordinates its writers, exports, and reconciliation workers.
     */
    public function run(Closure $operation): mixed
    {
        $handle = fopen(storage_path('framework/cache/storage-lifecycle.lock'), 'c');
        abort_if($handle === false, 503, 'Storage is temporarily unavailable.');
        if (! flock($handle, LOCK_EX | LOCK_NB)) {
            fclose($handle);
            abort(503, 'Another storage operation is running. Please retry.');
        }
        $this->createdPaths = [];
        try {
            return $operation($this);
        } finally {
            try {
                $this->collect($this->createdPaths);
            } finally {
                flock($handle, LOCK_UN);
                fclose($handle);
            }
        }
    }

    public function checkQuota(User $user, int $bytes): void
    {
        abort_if(FileVersion::whereNotIn('storage_path', StoredBlob::select('storage_path'))->exists(), 409, 'Legacy storage must be reconciled before new uploads or edits.');
        $user->refresh();
        $ledgerBytes = (int) StoredBlob::where('billing_user_id', $user->id)->sum('size_bytes');
        if ($user->storage_used_bytes < $ledgerBytes) {
            $user->update(['storage_used_bytes' => $ledgerBytes]);
        }
        if ($bytes > $user->remainingStorageBytes()) {
            throw ValidationException::withMessages(['files' => 'Not enough storage space for this operation.']);
        }
    }

    public function checkProjectCapacity(Project $project, int $addedVersions, int $addedEntries = 0): void
    {
        $versions = FileVersion::whereHas('projectFile', fn ($query) => $query->where('project_id', $project->id))->count();
        abort_if($versions + $addedVersions > config('schoolshare.operations.max_project_versions'), 413, 'This project has too much version history. Remove old checkpoints before adding more.');
        abort_if($project->files()->count() + $project->folders()->count() + $addedEntries > config('schoolshare.operations.max_project_entries'), 413, 'This project has too many files and folders.');
    }

    /** Persist a write-ahead charge before touching the filesystem. */
    public function create(User $user, Project $project, int $size, Closure $write): StoredBlob
    {
        $path = "projects/{$user->id}/{$project->id}/blobs/".Str::uuid();
        $blob = DB::transaction(function () use ($user, $path, $size): StoredBlob {
            $charged = $size === 0 ? (int) User::whereKey($user->id)->exists() : User::whereKey($user->id)
                ->whereRaw('storage_used_bytes + ? <= ?', [$size, $user->fresh()->maxStorageBytes()])
                ->increment('storage_used_bytes', $size);
            if ($charged !== 1) {
                throw ValidationException::withMessages(['files' => 'Not enough storage space for this operation.']);
            }

            return StoredBlob::create(['storage_path' => $path, 'billing_user_id' => $user->id, 'size_bytes' => $size]);
        });
        $this->createdPaths[] = $path;
        abort_unless($write($path), 503, 'The file could not be stored. Please retry.');
        abort_unless(Storage::disk('local')->exists($path) && Storage::disk('local')->size($path) === $size, 503, 'The stored file could not be verified.');

        return $blob;
    }

    /** Import an existing charge without trusting the current actor or restore author. */
    public function adopt(string $path): StoredBlob
    {
        $blob = StoredBlob::where('storage_path', $path)->first();
        if ($blob) {
            return $blob;
        }
        $version = FileVersion::where('storage_path', $path)->orderBy('id')->firstOrFail();
        $ownerId = preg_match('#^projects/(\d+)/#', $path, $matches)
            ? (int) $matches[1] : $version->checkpoint?->user_id;
        abort_unless($ownerId && User::whereKey($ownerId)->exists(), 409, 'A legacy file has no identifiable billing owner. Repair it before continuing.');
        $size = Storage::disk('local')->exists($path) ? Storage::disk('local')->size($path) : $version->size_bytes;

        return DB::transaction(function () use ($path, $ownerId, $size): StoredBlob {
            $blob = StoredBlob::create(['storage_path' => $path, 'billing_user_id' => $ownerId, 'size_bytes' => $size]);
            $user = User::whereKey($ownerId)->lockForUpdate()->firstOrFail();
            $ledgerBytes = (int) StoredBlob::where('billing_user_id', $ownerId)->sum('size_bytes');
            $user->update(['storage_used_bytes' => max($user->storage_used_bytes, $ledgerBytes)]);

            return $blob;
        });
    }

    /** @param list<string> $paths */
    public function collect(array $paths): void
    {
        foreach (array_unique($paths) as $path) {
            if (FileVersion::where('storage_path', $path)->exists()) {
                continue;
            }
            $blob = StoredBlob::where('storage_path', $path)->first();
            if (! $blob) {
                continue;
            }
            try {
                if (Storage::disk('local')->exists($path) && ! Storage::disk('local')->delete($path)) {
                    report(new \RuntimeException('Stored blob cleanup failed: '.$blob->id));

                    continue;
                }
                DB::transaction(function () use ($blob): void {
                    if ($blob->billing_user_id) {
                        $user = User::whereKey($blob->billing_user_id)->lockForUpdate()->first();
                        $remainingCharge = (int) StoredBlob::where('billing_user_id', $blob->billing_user_id)->whereKeyNot($blob->id)->sum('size_bytes');
                        $user?->update(['storage_used_bytes' => max($remainingCharge, $user->storage_used_bytes - $blob->size_bytes)]);
                    }
                    $blob->delete();
                });
            } catch (Throwable $exception) {
                report($exception);
            }
        }
    }

    /** @param iterable<ProjectFile> $files */
    public function deleteFiles(iterable $files, Closure $delete): void
    {
        $paths = [];
        foreach ($files as $file) {
            foreach ($file->versions as $version) {
                $this->adopt($version->storage_path);
                $paths[] = $version->storage_path;
            }
        }
        DB::transaction($delete);
        $this->collect($paths);
    }

    public function readText(FileVersion $version, int $limit): ?string
    {
        $disk = Storage::disk('local');
        if (! $disk->exists($version->storage_path)) {
            return null;
        }
        if ($version->size_bytes > $limit || $disk->size($version->storage_path) > $limit) {
            return null;
        }
        $stream = $disk->readStream($version->storage_path);
        abort_unless(is_resource($stream), 503, 'The file could not be read.');
        try {
            $text = stream_get_contents($stream, $limit + 1);
            abort_if($text === false, 503, 'The file could not be read.');

            return strlen($text) <= $limit ? $text : null;
        } finally {
            fclose($stream);
        }
    }
}
