<?php

namespace Tests\Feature;

use App\Models\FileVersion;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\StoredBlob;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class StorageLifecycleSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_restored_blob_stays_charged_until_the_last_reference_is_deleted(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $file = $this->upload($user, $project);
        $checkpoint = $file->latestVersion->checkpoint;
        $path = $file->latestVersion->storage_path;

        $this->post(route('projects.checkpoints.restore', [$project, $checkpoint]))->assertRedirect();
        $this->delete(route('projects.checkpoints.destroy', [$project, $checkpoint]))->assertRedirect();

        $this->assertSame(5, $user->refresh()->storage_used_bytes);
        $this->assertSame(1, $file->refresh()->version_count);
        $this->assertSame(1, FileVersion::count());
        Storage::disk('local')->assertExists($path);
        $this->assertDatabaseCount('stored_blobs', 1);

        $this->delete(route('projects.files.destroy', [$project, $file]))->assertRedirect();

        $this->assertSame(0, $user->refresh()->storage_used_bytes);
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseCount('stored_blobs', 0);
    }

    public function test_editor_deletion_credits_the_uploader_and_not_the_actor(): void
    {
        Storage::fake('local');
        [$owner, $project] = $this->project();
        $file = $this->upload($owner, $project);
        $editor = User::factory()->create(['username' => 'editor', 'storage_used_bytes' => 20])->refresh();
        $project->collaborators()->attach($editor, ['role' => 'editor']);

        $this->actingAs($editor)->delete(route('projects.files.destroy', [$project, $file]))->assertRedirect();

        $this->assertSame(0, $owner->refresh()->storage_used_bytes);
        $this->assertSame(20, $editor->refresh()->storage_used_bytes);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    #[TestWith(['folder'])]
    #[TestWith(['project'])]
    #[TestWith(['account'])]
    public function test_cascading_deletion_cleans_all_history_and_credits_collaborator_uploads(string $target): void
    {
        Storage::fake('local');
        [$owner, $project] = $this->project();
        $editor = User::factory()->create(['username' => 'editor'])->refresh();
        $project->collaborators()->attach($editor, ['role' => 'editor']);
        $folder = $project->folders()->create(['name' => 'notes']);
        $file = $this->upload($editor, $project, $folder->id);
        $this->putJson(route('projects.files.update', [$project, $file]), ['content' => 'edited'])->assertJsonPath('success', true);

        $url = match ($target) {
            'folder' => route('projects.folders.destroy', [$project, $folder]),
            'project' => route('projects.destroy', $project),
            'account' => route('profile.destroy'),
        };
        $this->actingAs($owner)->delete($url, ['password' => 'password'])->assertRedirect();

        $this->assertModelMissing($file);
        $this->assertSame(0, $editor->refresh()->storage_used_bytes);
        $this->assertDatabaseCount('stored_blobs', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
        if ($target === 'account') {
            $this->assertModelMissing($owner);
            $this->assertGuest();
        }
    }

    public function test_failed_editor_write_creates_no_version_and_releases_its_reservation(): void
    {
        $disk = Storage::fake('local');
        [$user, $project] = $this->project();
        $file = $this->upload($user, $project);
        $double = Mockery::mock($disk)->makePartial();
        $double->shouldReceive('put')->once()->andReturn(false);
        Storage::shouldReceive('disk')->with('local')->andReturn($double);

        $this->putJson(route('projects.files.update', [$project, $file]), ['content' => 'edited'])->assertServiceUnavailable();

        $this->assertSame(5, $user->refresh()->storage_used_bytes);
        $this->assertDatabaseCount('file_versions', 1);
        $this->assertDatabaseCount('checkpoints', 1);
        $this->assertDatabaseCount('stored_blobs', 1);
        $this->assertCount(1, $disk->allFiles());
    }

    public function test_database_failure_after_a_write_compensates_the_blob_and_charge(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        ProjectFile::creating(fn () => throw new \RuntimeException('Synthetic database failure'));

        try {
            $this->actingAs($user)->postJson(route('projects.checkpoints.store', $project), [
                'title' => 'Upload', 'files' => [UploadedFile::fake()->createWithContent('notes.txt', 'draft')],
            ])->assertInternalServerError();
        } finally {
            ProjectFile::flushEventListeners();
        }

        $this->assertDatabaseCount('checkpoints', 0);
        $this->assertDatabaseCount('file_versions', 0);
        $this->assertDatabaseCount('activities', 0);
        $this->assertDatabaseCount('stored_blobs', 0);
        $this->assertSame(0, $user->refresh()->storage_used_bytes);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_failed_deletion_retains_a_charged_cleanup_record_until_retry(): void
    {
        $disk = Storage::fake('local');
        [$user, $project] = $this->project();
        $file = $this->upload($user, $project);
        $path = $file->latestVersion->storage_path;
        $double = Mockery::mock($disk)->makePartial();
        $failDelete = true;
        $double->shouldReceive('delete')->with($path)->andReturnUsing(function () use (&$failDelete, $disk, $path) {
            return $failDelete ? false : $disk->delete($path);
        });
        Storage::shouldReceive('disk')->with('local')->andReturn($double);

        $this->delete(route('projects.files.destroy', [$project, $file]))->assertRedirect();

        $this->assertModelMissing($file);
        $this->assertSame(5, $user->refresh()->storage_used_bytes);
        $this->assertDatabaseCount('stored_blobs', 1);
        $disk->assertExists($path);
        $failDelete = false;

        $this->artisan('schoolshare:recalculate-storage --cleanup')->assertSuccessful();

        $this->assertSame(0, $user->refresh()->storage_used_bytes);
        $this->assertDatabaseCount('stored_blobs', 0);
        $disk->assertMissing($path);
    }

    public function test_quota_uses_fresh_persisted_usage_and_rejects_without_writes(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        User::whereKey($user->id)->update(['storage_used_bytes' => $user->maxStorageBytes() - 4]);

        $this->actingAs($user)->postJson(route('projects.checkpoints.store', $project), [
            'title' => 'Upload', 'files' => [UploadedFile::fake()->createWithContent('notes.txt', 'draft')],
        ])->assertUnprocessable()->assertJsonValidationErrors('files');

        $this->assertDatabaseCount('checkpoints', 0);
        $this->assertDatabaseCount('stored_blobs', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_busy_lifecycle_returns_503_before_writes(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $lock = fopen(storage_path('framework/cache/storage-lifecycle.lock'), 'c');
        flock($lock, LOCK_EX);

        try {
            $this->actingAs($user)->postJson(route('projects.checkpoints.store', $project), [
                'title' => 'Upload', 'files' => [UploadedFile::fake()->createWithContent('notes.txt', 'draft')],
            ])->assertServiceUnavailable();
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }

        $this->assertDatabaseCount('checkpoints', 0);
        $this->assertSame(0, $user->refresh()->storage_used_bytes);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_restore_of_missing_data_returns_409_without_partial_references(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $file = $this->upload($user, $project);
        $checkpoint = $file->latestVersion->checkpoint;
        Storage::disk('local')->delete($file->latestVersion->storage_path);

        $this->postJson(route('projects.checkpoints.restore', [$project, $checkpoint]))->assertConflict();

        $this->assertDatabaseCount('checkpoints', 1);
        $this->assertDatabaseCount('file_versions', 1);
        $this->assertSame(5, $user->refresh()->storage_used_bytes);
    }

    public function test_account_deletion_cannot_leave_free_files_in_another_users_project(): void
    {
        Storage::fake('local');
        [$owner, $project] = $this->project();
        $editor = User::factory()->create(['username' => 'editor'])->refresh();
        $project->collaborators()->attach($editor, ['role' => 'editor']);
        $file = $this->upload($editor, $project);

        $this->deleteJson(route('profile.destroy'), ['password' => 'password'])->assertConflict();

        $this->assertAuthenticatedAs($editor);
        $this->assertModelExists($editor);
        $this->assertModelExists($file);
        $this->assertSame(5, $editor->refresh()->storage_used_bytes);
    }

    public function test_reconciliation_counts_shared_paths_once_and_discovers_old_orphans_without_deleting(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $file = $this->upload($user, $project);
        $this->post(route('projects.checkpoints.restore', [$project, $file->latestVersion->checkpoint]))->assertRedirect();
        StoredBlob::query()->delete();
        Storage::disk('local')->put("projects/{$user->id}/{$project->id}/old-orphan", 'old');
        $user->update(['storage_used_bytes' => 0]);

        $this->artisan('schoolshare:recalculate-storage')->assertSuccessful();

        $this->assertSame(8, $user->refresh()->storage_used_bytes);
        $this->assertDatabaseCount('stored_blobs', 2);
        $this->assertCount(2, Storage::disk('local')->allFiles());
    }

    public function test_fork_copies_only_current_bytes_and_keeps_independent_billing(): void
    {
        Storage::fake('local');
        [$owner, $project] = $this->project();
        $project->update(['visibility' => 'public']);
        $file = $this->upload($owner, $project);
        $reader = User::factory()->create(['username' => 'reader'])->refresh();

        $this->actingAs($reader)->post(route('projects.fork', $project))->assertRedirect();

        $fork = $reader->projects()->firstOrFail();
        $forkFile = $fork->files()->firstOrFail();
        $this->assertNotSame($file->latestVersion->storage_path, $forkFile->latestVersion->storage_path);
        $this->assertSame(5, $reader->refresh()->storage_used_bytes);
        $this->assertSame(5, $owner->refresh()->storage_used_bytes);
        $this->assertSame('draft', Storage::disk('local')->get($forkFile->latestVersion->storage_path));
    }

    public function test_partial_fork_copy_failure_cleans_completed_copies_and_all_reservations(): void
    {
        $disk = Storage::fake('local');
        [$owner, $project] = $this->project();
        $project->update(['visibility' => 'public']);
        $this->upload($owner, $project);
        $this->post(route('projects.checkpoints.store', $project), [
            'title' => 'Second file', 'files' => [UploadedFile::fake()->createWithContent('extra.txt', 'extra')],
        ])->assertRedirect();
        $reader = User::factory()->create(['username' => 'reader'])->refresh();
        $copies = 0;
        $double = Mockery::mock($disk)->makePartial();
        $double->shouldReceive('copy')->twice()->andReturnUsing(function (string $from, string $to) use ($disk, &$copies) {
            return ++$copies === 1 ? $disk->copy($from, $to) : false;
        });
        Storage::shouldReceive('disk')->with('local')->andReturn($double);

        $this->actingAs($reader)->postJson(route('projects.fork', $project))->assertServiceUnavailable();

        $this->assertSame(0, $reader->refresh()->storage_used_bytes);
        $this->assertSame(10, $owner->refresh()->storage_used_bytes);
        $this->assertSame(0, $reader->projects()->count());
        $this->assertDatabaseCount('stored_blobs', 2);
        $this->assertDatabaseCount('project_forks', 0);
        $this->assertCount(2, $disk->allFiles());
    }

    public function test_unreconciled_legacy_versions_block_new_writes_with_409(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $file = $this->upload($user, $project);
        StoredBlob::query()->delete();

        $this->putJson(route('projects.files.update', [$project, $file]), ['content' => 'new'])->assertConflict();

        $this->assertDatabaseCount('file_versions', 1);
        $this->assertSame(5, $user->refresh()->storage_used_bytes);
        $this->assertCount(1, Storage::disk('local')->allFiles());
    }

    public function test_deleting_an_original_legacy_checkpoint_repairs_undercharged_retained_bytes(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $file = $this->upload($user, $project);
        $checkpoint = $file->latestVersion->checkpoint;
        $this->post(route('projects.checkpoints.restore', [$project, $checkpoint]))->assertRedirect();
        StoredBlob::query()->delete();
        $user->update(['storage_used_bytes' => 0]);

        $this->delete(route('projects.checkpoints.destroy', [$project, $checkpoint]))->assertRedirect();

        $this->assertSame(5, $user->refresh()->storage_used_bytes);
        $this->assertDatabaseCount('stored_blobs', 1);
        Storage::disk('local')->assertExists($file->refresh()->latestVersion->storage_path);
    }

    public function test_empty_text_edit_creates_a_zero_byte_blob_without_additional_charge(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $file = $this->upload($user, $project);

        $this->putJson(route('projects.files.update', [$project, $file]), ['content' => ''])->assertJsonPath('success', true);

        $this->assertSame(5, $user->refresh()->storage_used_bytes);
        $this->assertSame(0, $file->refresh()->latestVersion->size_bytes);
        $this->assertDatabaseCount('stored_blobs', 2);
    }

    public function test_cleanup_removes_only_expired_generated_exports_and_keeps_fresh_exports_and_project_data(): void
    {
        Storage::fake('local');
        [$user, $project] = $this->project();
        $file = $this->upload($user, $project);
        $old = 'exports/11111111-1111-4111-8111-111111111111.zip';
        $fresh = 'exports/22222222-2222-4222-8222-222222222222.zip';
        Storage::disk('local')->put($old, 'old');
        Storage::disk('local')->put($fresh, 'fresh');
        touch(Storage::disk('local')->path($old), now()->subHours(2)->timestamp);

        $this->artisan('schoolshare:recalculate-storage --cleanup')->assertSuccessful();

        Storage::disk('local')->assertMissing($old);
        Storage::disk('local')->assertExists($fresh);
        Storage::disk('local')->assertExists($file->latestVersion->storage_path);
        $this->assertSame(5, $user->refresh()->storage_used_bytes);
    }

    #[TestWith([true])]
    #[TestWith([false])]
    public function test_fork_preflight_rejects_quota_or_missing_source_without_leaking_copies(bool $missing): void
    {
        Storage::fake('local');
        [$owner, $project] = $this->project();
        $project->update(['visibility' => 'public']);
        $file = $this->upload($owner, $project);
        $reader = User::factory()->create(['username' => 'reader'])->refresh();
        if ($missing) {
            Storage::disk('local')->delete($file->latestVersion->storage_path);
        } else {
            $reader->update(['storage_used_bytes' => $reader->maxStorageBytes()]);
        }

        $response = $this->actingAs($reader)->postJson(route('projects.fork', $project));

        $response->assertStatus($missing ? 409 : 422);
        $this->assertSame(0, $reader->projects()->count());
        $this->assertDatabaseCount('stored_blobs', 1);
        $this->assertDatabaseCount('project_forks', 0);
        $this->assertCount($missing ? 0 : 1, Storage::disk('local')->allFiles());
    }

    private function project(): array
    {
        $user = User::factory()->create(['username' => 'owner'])->refresh();

        return [$user, $user->projects()->create(['name' => 'Storage project', 'visibility' => 'private'])];
    }

    private function upload(User $user, Project $project, ?int $folderId = null): ProjectFile
    {
        $this->actingAs($user)->post(route('projects.checkpoints.store', $project), [
            'title' => 'Upload', 'folder_id' => $folderId, 'files' => [UploadedFile::fake()->createWithContent('notes.txt', 'draft')],
        ])->assertSessionHasNoErrors()->assertRedirect();

        return $project->files()->with('latestVersion.checkpoint')->firstOrFail();
    }
}
