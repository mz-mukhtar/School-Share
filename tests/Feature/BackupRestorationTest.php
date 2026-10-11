<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use ZipArchive;

#[Group('recovery')]
class BackupRestorationTest extends TestCase
{
    public function test_database_and_private_bytes_restore_together_with_snapshots_and_retained_charges(): void
    {
        if (getenv('STAGING_RECOVERY') !== '1') {
            $this->markTestSkipped('Opt-in dump/restore requires isolated engine databases and the database client.');
        }
        $connectionName = config('database.default');
        $this->assertContains($connectionName, ['mysql', 'mariadb']);
        $this->assertSame('schoolshare_testing', DB::connection()->getDatabaseName());
        $connection = config('database.connections.'.$connectionName);
        $fixtureRoot = sys_get_temp_dir().'/schoolshare-recovery-'.Str::uuid();
        mkdir($fixtureRoot, 0700);
        config(['filesystems.disks.local.root' => $fixtureRoot.'/source']);
        Storage::forgetDisk('local');
        config(['database.connections.recovery' => [...$connection, 'url' => null, 'database' => 'schoolshare_restore_testing']]);

        try {
            $this->artisan('migrate:fresh', ['--force' => true])->assertSuccessful();
            $owner = User::factory()->create();
            $project = $owner->projects()->create(['name' => 'Coordinated recovery', 'visibility' => 'private']);
            $this->actingAs($owner)->post(route('projects.checkpoints.store', $project), [
                'title' => 'Recovery point', 'files' => [UploadedFile::fake()->createWithContent('notes.txt', 'recovery draft')],
            ])->assertRedirect();
            $checkpoint = $project->checkpoints()->firstOrFail();
            $file = $project->files()->with('latestVersion')->firstOrFail();
            $this->put(route('projects.files.update', [$project, $file]), ['content' => 'retained revision'])->assertOk();
            $this->delete(route('projects.files.destroy', [$project, $file]))->assertRedirect();
            $this->assertSame(31, $owner->fresh()->storage_used_bytes);
            $expectedFiles = collect(Storage::disk('local')->allFiles('projects'))
                ->mapWithKeys(fn (string $path): array => [$path => hash('sha256', Storage::disk('local')->get($path))])->all();
            $dump = new Process([
                'mariadb-dump', '--single-transaction', '--skip-lock-tables', '--skip-comments', '--no-tablespaces', '--skip-ssl',
                '--host='.$connection['host'], '--port='.$connection['port'], '--user='.$connection['username'], 'schoolshare_testing',
            ], env: ['MYSQL_PWD' => $connection['password']]);
            $dump->setTimeout(60)->run();
            $this->assertSame(0, $dump->getExitCode(), 'The coordinated database dump must succeed.');
            $archivePath = $fixtureRoot.'/backup.zip';
            $archive = new ZipArchive;
            $this->assertTrue($archive->open($archivePath, ZipArchive::CREATE));
            $archive->addFromString('database.sql', $dump->getOutput());
            $archive->addFromString('manifest.json', json_encode($expectedFiles, JSON_THROW_ON_ERROR));
            foreach (array_keys($expectedFiles) as $path) {
                $archive->addFromString('private/'.$path, Storage::disk('local')->get($path));
            }
            $archive->close();
            chmod($archivePath, 0600);
            Storage::disk('local')->put($file->latestVersion->storage_path, 'corrupted');

            $this->assertSame('schoolshare_restore_testing', DB::connection('recovery')->getDatabaseName());
            Schema::connection('recovery')->dropAllTables();
            $archive = new ZipArchive;
            $this->assertTrue($archive->open($archivePath));
            $restore = new Process([
                'mariadb', '--skip-ssl', '--host='.$connection['host'], '--port='.$connection['port'], '--user='.$connection['username'], 'schoolshare_restore_testing',
            ], env: ['MYSQL_PWD' => $connection['password']]);
            $restore->setInput($archive->getFromName('database.sql'));
            $restore->setTimeout(60)->run();
            $this->assertSame(0, $restore->getExitCode(), 'The database backup must restore into the separate recovery database.');
            config(['filesystems.disks.local.root' => $fixtureRoot.'/restored']);
            Storage::forgetDisk('local');
            foreach ($expectedFiles as $path => $checksum) {
                Storage::disk('local')->put($path, $archive->getFromName('private/'.$path));
                $this->assertSame($checksum, hash('sha256', Storage::disk('local')->get($path)));
            }
            $archive->close();
            config(['database.default' => 'recovery']);
            $restoredOwner = User::findOrFail($owner->id);
            $restoredProject = $restoredOwner->projects()->firstOrFail();
            $restoredCheckpoint = $restoredProject->checkpoints()->findOrFail($checkpoint->id);

            $this->artisan('schoolshare:recalculate-storage')->assertSuccessful();
            $this->actingAs($restoredOwner)->post(route('projects.checkpoints.restore', [$restoredProject, $restoredCheckpoint]))->assertRedirect();

            $this->assertSame(31, $restoredOwner->fresh()->storage_used_bytes);
            $this->assertDatabaseCount('stored_blobs', 2);
            $restoredFile = $restoredProject->files()->with('latestVersion')->findOrFail($file->id);
            $this->assertSame('recovery draft', Storage::disk('local')->get($restoredFile->latestVersion->storage_path));
            $this->assertSame(1, $restoredCheckpoint->fileSnapshots()->count());
            $this->assertSame(3, $restoredFile->version_count);
            $this->get(route('projects.files.download', [$restoredProject, $restoredFile]))->assertDownload('notes.txt');
        } finally {
            config(['database.default' => $connectionName]);
            Schema::connection('recovery')->dropAllTables();
            Schema::dropAllTables();
            RefreshDatabaseState::$migrated = false;
            Storage::forgetDisk('local');
            $this->app['files']->deleteDirectory($fixtureRoot);
        }

    }
}
