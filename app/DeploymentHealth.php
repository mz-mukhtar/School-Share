<?php

namespace App;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

class DeploymentHealth
{
    /** @return array<string, bool> */
    public function checks(): array
    {
        return [
            'database' => $this->passes(fn (): bool => DB::select('SELECT 1') !== []),
            'cache' => $this->passes(function (): bool {
                $key = 'health:'.Str::uuid();

                try {
                    Cache::put($key, 'ok', 30);

                    return Cache::get($key) === 'ok';
                } finally {
                    Cache::forget($key);
                }
            }),
            'private_storage' => $this->passes(function (): bool {
                $disk = Storage::disk('local');
                $path = '.health/'.Str::uuid();

                try {
                    return $disk->put($path, 'ok') && $disk->get($path) === 'ok';
                } finally {
                    $disk->delete($path);
                }
            }),
            'runtime_directories' => $this->passes(fn (): bool => collect([
                storage_path('app/public'),
                storage_path('framework/cache'),
                storage_path('framework/sessions'),
                storage_path('framework/views'),
                app()->bootstrapPath('cache'),
            ])->every(fn (string $path): bool => is_dir($path) && is_writable($path))),
            'disk_capacity' => $this->passes(function (): bool {
                $availableBytes = disk_free_space(storage_path());

                return $availableBytes !== false
                    && $availableBytes >= max(0, (int) config('schoolshare.monitoring.minimum_free_bytes'));
            }),
        ];
    }

    private function passes(callable $check): bool
    {
        try {
            return $check();
        } catch (Throwable) {
            return false;
        }
    }
}
