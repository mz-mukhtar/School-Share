<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Tests\TestCase;

class DeploymentHealthTest extends TestCase
{
    public function test_readiness_returns_only_status_and_does_not_cache_the_response(): void
    {
        Storage::fake('local');

        $this->getJson('/ready')->assertOk()->assertExactJson(['status' => 'ok'])
            ->assertHeader('Cache-Control', 'no-store, private');
        $this->assertSame([], Storage::disk('local')->allFiles());
        $this->artisan('schoolshare:health')->assertSuccessful();
    }

    public function test_database_failure_returns_503_without_exposing_the_exception(): void
    {
        Storage::fake('local');
        DB::shouldReceive('select')->with('SELECT 1')->once()
            ->andThrow(new RuntimeException('password=private-database-credential'));

        $this->getJson('/ready')->assertServiceUnavailable()->assertExactJson(['status' => 'unavailable']);
    }

    public function test_cache_failure_returns_503_without_exposing_the_exception(): void
    {
        Storage::fake('local');
        Cache::shouldReceive('put')->once()->andThrow(new RuntimeException('private-cache-credential'));
        Cache::shouldReceive('forget')->once();

        $this->getJson('/ready')->assertServiceUnavailable()->assertExactJson(['status' => 'unavailable']);
    }

    public function test_unwritable_private_storage_returns_503(): void
    {
        Storage::shouldReceive('disk')->with('local')->once()->andThrow(new RuntimeException('Permission denied'));

        $this->getJson('/ready')->assertServiceUnavailable()->assertExactJson(['status' => 'unavailable']);
    }

    public function test_insufficient_disk_capacity_fails_the_operational_command(): void
    {
        Storage::fake('local');
        config(['schoolshare.monitoring.minimum_free_bytes' => PHP_INT_MAX]);

        $this->artisan('schoolshare:health')->expectsOutput('Unavailable: disk_capacity')->assertFailed();
    }
}
