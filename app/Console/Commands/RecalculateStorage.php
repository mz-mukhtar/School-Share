<?php

namespace App\Console\Commands;

use App\Models\FileVersion;
use App\Models\StoredBlob;
use App\Models\User;
use App\StorageLifecycle;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class RecalculateStorage extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'schoolshare:recalculate-storage {--cleanup : Delete unreferenced ledger blobs and expired generated exports}';

    /**
     * The console command description.
     */
    protected $description = 'Recalculates and updates the storage_used_bytes for all users based on physical files.';

    /**
     * Execute the console command.
     */
    public function handle(StorageLifecycle $storage): int
    {
        $storage->run(function (StorageLifecycle $storage): void {
            foreach (FileVersion::select('storage_path')->distinct()->cursor() as $version) {
                $storage->adopt($version->storage_path);
            }
            foreach (Storage::disk('local')->allFiles('projects') as $path) {
                if (StoredBlob::where('storage_path', $path)->exists()) {
                    continue;
                }
                if (! preg_match('#^projects/(\d+)/#', $path, $matches) || ! User::whereKey((int) $matches[1])->exists()) {
                    throw new \RuntimeException('An untracked stored file has no billing owner. Audit it before reconciling.');
                }
                StoredBlob::create(['storage_path' => $path, 'billing_user_id' => (int) $matches[1], 'size_bytes' => Storage::disk('local')->size($path)]);
            }
            foreach (User::cursor() as $user) {
                $user->update(['storage_used_bytes' => (int) StoredBlob::where('billing_user_id', $user->id)->sum('size_bytes')]);
            }
            if ($this->option('cleanup')) {
                StoredBlob::orderBy('id')->chunkById(100, fn ($blobs) => $storage->collect($blobs->pluck('storage_path')->all()));
                foreach (Storage::disk('local')->allFiles('exports') as $path) {
                    if (preg_match('#^exports/[a-f0-9-]{36}\.zip$#', $path)
                        && Storage::disk('local')->lastModified($path) < now()->timestamp - config('schoolshare.operations.export_retention_seconds')
                        && ! FileVersion::where('storage_path', $path)->exists()
                        && ! StoredBlob::where('storage_path', $path)->exists()) {
                        if (! Storage::disk('local')->delete($path)) {
                            throw new \RuntimeException('An expired export could not be cleaned up. Please retry.');
                        }
                    }
                }
            }
        });
        $this->info('Storage charges reconciled from the private-disk blob ledger.');
        $pending = StoredBlob::whereNotIn('storage_path', FileVersion::select('storage_path'))->count();
        if ($pending > 0) {
            $this->warn($pending.' unreferenced blobs remain charged. Inspect them and retry with --cleanup when appropriate.');

            return $this->option('cleanup') ? self::FAILURE : self::SUCCESS;
        }

        return self::SUCCESS;
    }
}
