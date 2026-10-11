<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProductionMigrationTest extends TestCase
{
    private bool $canResetDatabase = false;

    protected function setUp(): void
    {
        parent::setUp();

        $database = DB::connection()->getDatabaseName();
        $this->assertTrue(app()->environment('testing'));
        $this->assertTrue($database === ':memory:' || str_ends_with($database, '_testing'), 'Migration rehearsals require an isolated _testing database.');
        $this->canResetDatabase = true;
    }

    protected function tearDown(): void
    {
        try {
            if ($this->canResetDatabase) {
                Schema::dropAllTables();
                RefreshDatabaseState::$migrated = false;
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_clean_installation_applies_every_migration_and_accepts_a_verified_account(): void
    {
        Schema::dropAllTables();

        $this->artisan('migrate', ['--force' => true])->assertSuccessful();

        $user = User::factory()->create();
        $this->actingAs($user)->get(route('dashboard'))->assertOk();
        $this->assertSame(count(glob(database_path('migrations/*.php'))), DB::table('migrations')->count());
    }

    public function test_populated_legacy_upgrade_preserves_version_bytes_and_repairs_root_identities(): void
    {
        Storage::fake('local');
        Schema::dropAllTables();
        $paths = collect(glob(database_path('migrations/*.php')))->sort()->values();
        $this->artisan('migrate', [
            '--path' => $paths->filter(fn (string $path): bool => basename($path) <= '2026_10_04_170424_add_profile_fields_to_users_table.php')->all(),
            '--realpath' => true, '--force' => true,
        ])->assertSuccessful();
        $owner = User::factory()->create(['plan' => 'student']);
        DB::table('users')->where('id', $owner->id)->update(['username' => null]);
        $project = $owner->projects()->create(['name' => 'Legacy project', 'visibility' => 'private']);
        $checkpoint = $project->checkpoints()->create(['user_id' => $owner->id, 'title' => 'Legacy checkpoint']);
        $path = 'projects/'.$project->id.'/legacy.txt';
        Storage::disk('local')->put($path, 'legacy draft');
        $fileIds = [];
        foreach (['notes.txt', 'notes.txt'] as $name) {
            $fileIds[] = DB::table('project_files')->insertGetId([
                'project_id' => $project->id, 'checkpoint_id' => $checkpoint->id,
                'original_name' => $name, 'storage_path' => $path, 'size_bytes' => 12,
                'mime_type' => 'text/plain', 'created_at' => now(), 'updated_at' => now(),
            ]);
        }
        $this->artisan('migrate', [
            '--path' => $paths->filter(fn (string $path): bool => basename($path) < '2026_10_05_000000')->all(),
            '--realpath' => true, '--force' => true,
        ])->assertSuccessful();
        foreach (['docs', 'docs'] as $name) {
            DB::table('project_folders')->insert([
                'project_id' => $project->id, 'name' => $name, 'parent_id' => null,
                'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $this->artisan('schoolshare:recalculate-storage')->assertSuccessful();

        $owner->refresh();
        $this->assertSame('user_'.$owner->id, $owner->username);
        $this->assertSame('free', $owner->plan);
        $this->assertSame(12, $owner->storage_used_bytes);
        $this->assertDatabaseCount('stored_blobs', 1);
        $this->assertDatabaseCount('file_versions', 2);
        $this->assertSame(['notes.txt', 'notes-'.$fileIds[1].'.txt'], $project->files()->orderBy('id')->pluck('original_name')->all());
        foreach ($project->files()->with('latestVersion')->get() as $file) {
            $this->assertSame(0, $file->folder_scope);
            $this->assertSame(1, $file->version_count);
            $this->assertSame('text/plain', $file->latestVersion->mime_type);
            $this->assertSame($checkpoint->id, $file->latestVersion->checkpoint_id);
            $this->assertSame('legacy draft', Storage::disk('local')->get($file->latestVersion->storage_path));
        }
        $this->assertSame(2, $project->folders()->distinct()->count('name'));
        $this->assertSame(0, $project->folders()->where('parent_scope', '!=', 0)->count());
        $this->actingAs($owner)->get(route('projects.show', $project))->assertOk();
        $this->post(route('projects.checkpoints.restore', [$project, $checkpoint]))->assertStatus(409);

        $this->assertDatabaseCount('checkpoint_file_snapshots', 0);
        $this->artisan('migrate', ['--force' => true])->expectsOutputToContain('Nothing to migrate')->assertSuccessful();
    }

    #[TestWith([['docs', 'DOCS'], ['notes.txt', 'NOTES.txt']])]
    #[TestWith([['cafe', 'café'], ['resume.txt', 'résumé.txt']])]
    #[TestWith([['docs', 'docs '], ['notes.txt', 'notes.txt ']])]
    public function test_populated_upgrade_deduplicates_names_under_the_target_database_collation(array $folderNames, array $fileNames): void
    {
        Schema::dropAllTables();
        $paths = collect(glob(database_path('migrations/*.php')))->sort()->values();
        $this->artisan('migrate', [
            '--path' => $paths->filter(fn (string $path): bool => basename($path) < '2026_10_05_000000')->all(),
            '--realpath' => true, '--force' => true,
        ])->assertSuccessful();
        $owner = User::factory()->create();
        $project = $owner->projects()->create(['name' => 'Case-sensitive legacy names', 'visibility' => 'private']);
        foreach ($folderNames as $name) {
            DB::table('project_folders')->insert(['project_id' => $project->id, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach ($fileNames as $name) {
            DB::table('project_files')->insert(['project_id' => $project->id, 'original_name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }

        $this->artisan('migrate', ['--force' => true])->assertSuccessful();

        $this->assertDatabaseCount('project_files', 2);
        $this->assertDatabaseCount('project_folders', 2);
    }

    public function test_populated_upgrade_preserves_existing_names_that_match_generated_collision_suffixes(): void
    {
        Schema::dropAllTables();
        $paths = collect(glob(database_path('migrations/*.php')))->sort()->values();
        $this->artisan('migrate', [
            '--path' => $paths->filter(fn (string $path): bool => basename($path) < '2026_10_05_000000')->all(),
            '--realpath' => true, '--force' => true,
        ])->assertSuccessful();
        $owner = User::factory()->create();
        $project = $owner->projects()->create(['name' => 'Legacy suffix collisions', 'visibility' => 'private']);
        foreach (['docs', 'docs', 'docs-2'] as $name) {
            DB::table('project_folders')->insert(['project_id' => $project->id, 'name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }
        foreach (['notes.txt', 'notes.txt', 'notes-2.txt'] as $name) {
            DB::table('project_files')->insert(['project_id' => $project->id, 'original_name' => $name, 'created_at' => now(), 'updated_at' => now()]);
        }

        $this->artisan('migrate', ['--force' => true])->assertSuccessful();

        $this->assertSame(['notes.txt', 'notes-2-1.txt', 'notes-2.txt'], $project->files()->orderBy('id')->pluck('original_name')->all());
        $this->assertSame(['docs', 'docs-2-1', 'docs-2'], $project->folders()->orderBy('id')->pluck('name')->all());
    }
}
