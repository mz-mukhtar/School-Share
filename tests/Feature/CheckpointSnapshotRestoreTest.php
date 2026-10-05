<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CheckpointSnapshotRestoreTest extends TestCase
{
    use RefreshDatabase;

    public function test_restore_recovers_the_original_tree_after_rename_move_and_deletion(): void
    {
        Storage::fake('local');
        $owner = User::factory()->create(['username' => 'snapshot-owner']);
        $project = $owner->projects()->create(['name' => 'Checkpoint snapshot', 'visibility' => 'private']);

        $this->actingAs($owner)->post(route('projects.folders.store', $project), ['name' => 'sources'])->assertRedirect();
        $folder = $project->folders()->firstOrFail();
        $this->post(route('projects.checkpoints.store', $project), [
            'title' => 'Original tree',
            'folder_id' => $folder->id,
            'files' => [UploadedFile::fake()->createWithContent('notes.txt', 'draft')],
        ])->assertRedirect();

        $source = $project->checkpoints()->where('title', 'Original tree')->firstOrFail();
        $file = $project->files()->firstOrFail();
        $this->put(route('projects.files.update', [$project, $file]), [
            'original_name' => 'renamed.txt',
            'folder_id' => null,
        ])->assertRedirect();
        $this->delete(route('projects.folders.destroy', [$project, $folder]))->assertRedirect();
        $this->delete(route('projects.files.destroy', [$project, $file]))->assertRedirect();
        $historicalVersionId = $source->fileSnapshots()->value('file_version_id');

        $this->get(route('projects.checkpoints.show', [$project, $source]))
            ->assertOk()
            ->assertSee('notes.txt')
            ->assertDontSee('renamed.txt');
        $this->get(route('projects.files.show', [$project, $file]).'?version_id='.$historicalVersionId)
            ->assertOk()
            ->assertSee('draft');

        $this->post(route('projects.checkpoints.store', $project), [
            'title' => 'Unrelated work',
            'files' => [UploadedFile::fake()->createWithContent('later.txt', 'later')],
        ])->assertRedirect();
        $unrelated = $project->files()->where('original_name', 'later.txt')->firstOrFail();

        $this->post(route('projects.checkpoints.restore', [$project, $source]))->assertRedirect();

        $restored = $project->files()->with('folder', 'latestVersion')->where('id', $file->id)->firstOrFail();
        $this->assertSame('notes.txt', $restored->original_name);
        $this->assertSame('sources', $restored->folder->name);
        $this->assertSame('draft', Storage::disk('local')->get($restored->latestVersion->storage_path));
        $this->assertSoftDeleted('project_files', ['id' => $unrelated->id]);
        $this->assertSame(1, $source->fileSnapshots()->count());
        $this->assertSame(1, $source->folderSnapshots()->count());
    }

    public function test_renaming_without_a_folder_id_preserves_its_folder(): void
    {
        $owner = User::factory()->create(['username' => 'rename-owner']);
        $project = $owner->projects()->create(['name' => 'Rename folder', 'visibility' => 'private']);
        $folder = $project->folders()->create(['name' => 'docs']);
        $file = $project->files()->create(['folder_id' => $folder->id, 'original_name' => 'before.txt']);

        $this->actingAs($owner)->put(route('projects.files.update', [$project, $file]), ['original_name' => 'after.txt'])->assertRedirect();

        $file->refresh();
        $this->assertSame('after.txt', $file->original_name);
        $this->assertSame($folder->id, $file->folder_id);
    }

    public function test_deleting_a_contributor_account_keeps_foreign_project_checkpoint_history(): void
    {
        $owner = User::factory()->create(['username' => 'history-owner']);
        $contributor = User::factory()->create(['username' => 'history-contributor']);
        $project = $owner->projects()->create(['name' => 'Preserved history', 'visibility' => 'private']);
        $checkpoint = $project->checkpoints()->create([
            'user_id' => $contributor->id,
            'title' => 'Contributor note',
            'total_size_bytes' => 0,
        ]);

        $this->actingAs($contributor)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertModelExists($project);
        $this->assertDatabaseHas('checkpoints', ['id' => $checkpoint->id, 'user_id' => null]);
        $this->assertDatabaseMissing('users', ['id' => $contributor->id]);
    }
}
