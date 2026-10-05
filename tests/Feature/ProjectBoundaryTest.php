<?php

namespace Tests\Feature;

use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProjectBoundaryTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith([true])]
    #[TestWith([false])]
    public function test_folder_creation_rejects_parent_from_another_project_even_with_the_same_owner(bool $sameOwner): void
    {
        $owner = $this->account('owner');
        $project = $this->project($owner, 'Current project');
        $other = $this->project($sameOwner ? $owner : $this->account('other'), 'Other project');
        $foreign = $other->folders()->create(['name' => 'foreign']);

        $response = $this->actingAs($owner)->postJson(route('projects.folders.store', $project), ['name' => 'child', 'parent_id' => $foreign->id]);

        $response->assertUnprocessable()->assertJsonValidationErrors('parent_id');
        $this->assertDatabaseCount('project_folders', 1);
        $this->assertModelExists($foreign);
    }

    public function test_file_move_rejects_a_foreign_folder_before_changing_the_file(): void
    {
        $owner = $this->account('owner');
        $project = $this->project($owner, 'Current project');
        $other = $this->project($owner, 'Other project');
        $foreign = $other->folders()->create(['name' => 'foreign']);
        $file = $project->files()->create(['original_name' => 'notes.txt', 'mime_type' => 'text/plain']);

        $response = $this->actingAs($owner)->putJson(route('projects.files.update', [$project, $file]), ['original_name' => 'renamed.txt', 'folder_id' => $foreign->id]);

        $response->assertUnprocessable()->assertJsonValidationErrors('folder_id');
        $this->assertSame('notes.txt', $file->refresh()->original_name);
        $this->assertNull($file->folder_id);
        $this->assertDatabaseCount('checkpoints', 0);
        $this->assertDatabaseCount('file_versions', 0);
    }

    public function test_upload_rejects_a_foreign_folder_without_writing_a_checkpoint_blob_or_activity(): void
    {
        Storage::fake('local');
        Notification::fake();
        $owner = $this->account('owner');
        $project = $this->project($owner, 'Current project');
        $other = $this->project($this->account('other'), 'Other project');
        $foreign = $other->folders()->create(['name' => 'foreign']);

        $response = $this->actingAs($owner)->postJson(route('projects.checkpoints.store', $project), [
            'title' => 'Invalid upload', 'folder_id' => $foreign->id,
            'files' => [UploadedFile::fake()->createWithContent('notes.txt', 'draft')],
        ]);

        $response->assertUnprocessable()->assertJsonValidationErrors('folder_id');
        $this->assertDatabaseCount('checkpoints', 0);
        $this->assertDatabaseCount('project_files', 0);
        $this->assertDatabaseCount('file_versions', 0);
        $this->assertDatabaseCount('activities', 0);
        $this->assertSame(0, $owner->refresh()->storage_used_bytes);
        $this->assertSame([], Storage::disk('local')->allFiles());
        Notification::assertNothingSent();
    }

    public function test_owner_can_create_a_child_folder_in_the_current_project(): void
    {
        $owner = $this->account('owner');
        $project = $this->project($owner, 'Current project');
        $parent = $project->folders()->create(['name' => 'parent']);

        $this->actingAs($owner)->post(route('projects.folders.store', $project), ['name' => 'child', 'parent_id' => $parent->id])
            ->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('project_folders', ['project_id' => $project->id, 'name' => 'child', 'parent_id' => $parent->id]);
    }

    public function test_editor_can_move_a_file_into_a_folder_of_the_current_project(): void
    {
        $owner = $this->account('owner');
        $editor = $this->account('editor');
        $project = $this->project($owner, 'Current project');
        $project->collaborators()->attach($editor, ['role' => 'editor']);
        $folder = $project->folders()->create(['name' => 'destination']);
        $file = $project->files()->create(['original_name' => 'notes.txt', 'mime_type' => 'text/plain']);

        $this->actingAs($editor)->put(route('projects.files.update', [$project, $file]), ['original_name' => 'notes.txt', 'folder_id' => $folder->id])
            ->assertSessionHasNoErrors()->assertRedirect();

        $this->assertSame($folder->id, $file->refresh()->folder_id);
    }

    public function test_editor_can_upload_to_a_folder_of_the_current_project(): void
    {
        Storage::fake('local');
        Notification::fake();
        $owner = $this->account('owner');
        $editor = $this->account('editor');
        $project = $this->project($owner, 'Current project');
        $project->collaborators()->attach($editor, ['role' => 'editor']);
        $folder = $project->folders()->create(['name' => 'destination']);

        $this->actingAs($editor)->post(route('projects.checkpoints.store', $project), [
            'title' => 'Shared upload', 'folder_id' => $folder->id,
            'files' => [UploadedFile::fake()->createWithContent('notes.txt', 'draft')],
        ])->assertSessionHasNoErrors()->assertRedirect(route('projects.show', $project));

        $this->assertDatabaseHas('project_files', ['project_id' => $project->id, 'folder_id' => $folder->id, 'original_name' => 'notes.txt']);
        $this->assertDatabaseHas('checkpoints', ['project_id' => $project->id, 'user_id' => $editor->id, 'title' => 'Shared upload']);
        $file = $project->files()->first();
        Storage::disk('local')->assertExists($file->latestVersion->storage_path);
        $this->assertSame(5, $editor->refresh()->storage_used_bytes);
        Notification::assertNothingSent();
    }

    public function test_viewer_cannot_move_or_upload_files_or_create_folders(): void
    {
        Storage::fake('local');
        $owner = $this->account('owner');
        $viewer = $this->account('viewer');
        $project = $this->project($owner, 'Current project');
        $project->collaborators()->attach($viewer, ['role' => 'viewer']);
        $folder = $project->folders()->create(['name' => 'destination']);
        $file = $project->files()->create(['original_name' => 'notes.txt', 'mime_type' => 'text/plain']);

        $this->actingAs($viewer)->putJson(route('projects.files.update', [$project, $file]), ['original_name' => 'changed.txt', 'folder_id' => $folder->id])->assertForbidden();
        $this->postJson(route('projects.folders.store', $project), ['name' => 'forbidden'])->assertForbidden();
        $this->postJson(route('projects.checkpoints.store', $project), [
            'title' => 'Forbidden upload', 'folder_id' => $folder->id,
            'files' => [UploadedFile::fake()->createWithContent('notes.txt', 'draft')],
        ])->assertForbidden();

        $this->assertSame('notes.txt', $file->refresh()->original_name);
        $this->assertDatabaseCount('project_folders', 1);
        $this->assertDatabaseCount('checkpoints', 0);
        $this->assertSame([], Storage::disk('local')->allFiles());
    }

    public function test_deleting_a_folder_with_a_foreign_child_is_refused_without_cascade_damage(): void
    {
        $owner = $this->account('owner');
        $project = $this->project($owner, 'Current project');
        $other = $this->project($this->account('other'), 'Other project');
        $folder = $project->folders()->create(['name' => 'parent']);
        $foreign = $other->folders()->create(['name' => 'foreign', 'parent_id' => $folder->id]);

        $this->actingAs($owner)->deleteJson(route('projects.folders.destroy', [$project, $folder]))->assertConflict();

        $this->assertModelExists($folder);
        $this->assertModelExists($foreign);
        $this->assertSame($folder->id, $foreign->refresh()->parent_id);
    }

    public function test_deleting_a_folder_with_a_foreign_file_is_refused_without_data_loss(): void
    {
        $owner = $this->account('owner');
        $project = $this->project($owner, 'Current project');
        $other = $this->project($this->account('other'), 'Other project');
        $folder = $project->folders()->create(['name' => 'parent']);
        $foreign = $other->files()->create(['original_name' => 'foreign.txt', 'folder_id' => $folder->id]);

        $this->actingAs($owner)->deleteJson(route('projects.folders.destroy', [$project, $folder]))->assertConflict();

        $this->assertModelExists($folder);
        $this->assertModelExists($foreign);
        $this->assertSame($folder->id, $foreign->refresh()->folder_id);
    }

    #[TestWith(['project'])]
    #[TestWith(['account'])]
    public function test_project_or_account_deletion_cannot_cascade_through_legacy_foreign_folders(string $target): void
    {
        $owner = $this->account('owner');
        $project = $this->project($owner, 'Current project');
        $other = $this->project($this->account('other'), 'Other project');
        $folder = $project->folders()->create(['name' => 'parent']);
        $foreign = $other->folders()->create(['name' => 'foreign', 'parent_id' => $folder->id]);
        $this->actingAs($owner);

        $response = $target === 'project'
            ? $this->deleteJson(route('projects.destroy', $project))
            : $this->deleteJson(route('profile.destroy'), ['password' => 'password']);

        $response->assertConflict();
        $this->assertAuthenticatedAs($owner);
        $this->assertModelExists($owner);
        $this->assertModelExists($project);
        $this->assertModelExists($folder);
        $this->assertModelExists($foreign);
    }

    public function test_clean_folder_deletion_removes_only_its_own_tree(): void
    {
        $owner = $this->account('owner');
        $project = $this->project($owner, 'Current project');
        $other = $this->project($this->account('other'), 'Other project');
        $folder = $project->folders()->create(['name' => 'parent']);
        $child = $project->folders()->create(['name' => 'child', 'parent_id' => $folder->id]);
        $file = $project->files()->create(['original_name' => 'own.txt', 'folder_id' => $child->id]);
        $foreign = $other->folders()->create(['name' => 'unrelated']);

        $this->actingAs($owner)->delete(route('projects.folders.destroy', [$project, $folder]))->assertRedirect();

        $this->assertModelMissing($folder);
        $this->assertModelMissing($child);
        $this->assertSoftDeleted('project_files', ['id' => $file->id]);
        $this->assertModelExists($foreign);
    }

    public function test_breadcrumbs_never_expose_a_foreign_parent_from_legacy_data(): void
    {
        $owner = $this->account('owner');
        $project = $this->project($owner, 'Current project');
        $other = $this->project($this->account('other'), 'Other project');
        $foreign = $other->folders()->create(['name' => 'secret-folder-name']);
        $folder = $project->folders()->create(['name' => 'local', 'parent_id' => $foreign->id]);

        $response = $this->actingAs($owner)->get(route('projects.show', ['project' => $project, 'folder' => $folder->id]));

        $response->assertSee('local')->assertDontSee('secret-folder-name');
        $this->assertSame([$folder->id], array_map(fn ($item) => $item->id, $response->viewData('breadcrumbs')));
    }

    #[TestWith(['GET', 'projects.checkpoints.show'])]
    #[TestWith(['POST', 'projects.checkpoints.restore'])]
    #[TestWith(['DELETE', 'projects.checkpoints.destroy'])]
    #[TestWith(['POST', 'projects.checkpoints.comments.store'])]
    public function test_foreign_checkpoint_ids_are_not_found_under_another_project(string $method, string $routeName): void
    {
        $owner = $this->account('owner');
        $project = $this->project($owner, 'Current project');
        $other = $this->project($owner, 'Other project');
        $checkpoint = $other->checkpoints()->create(['user_id' => $owner->id, 'title' => 'Foreign checkpoint']);

        $this->actingAs($owner)->json($method, route($routeName, [$project, $checkpoint]), ['body' => 'Wrong project'])->assertNotFound();

        $this->assertModelExists($checkpoint);
        $this->assertDatabaseCount('checkpoints', 1);
        $this->assertDatabaseCount('comments', 0);
        $this->assertDatabaseCount('activities', 0);
    }

    #[TestWith(['GET', 'projects.files.show'])]
    #[TestWith(['GET', 'projects.files.download'])]
    #[TestWith(['GET', 'projects.files.diff'])]
    #[TestWith(['PUT', 'projects.files.update'])]
    #[TestWith(['DELETE', 'projects.files.destroy'])]
    public function test_foreign_file_ids_are_not_found_under_another_project(string $method, string $routeName): void
    {
        $owner = $this->account('owner');
        $project = $this->project($owner, 'Current project');
        $other = $this->project($owner, 'Other project');
        $file = $other->files()->create(['original_name' => 'foreign.txt']);

        $this->actingAs($owner)->json($method, route($routeName, [$project, $file]), ['original_name' => 'changed.txt'])->assertNotFound();

        $this->assertModelExists($file);
        $this->assertSame('foreign.txt', $file->refresh()->original_name);
    }

    public function test_foreign_folder_id_is_not_found_under_another_project(): void
    {
        $owner = $this->account('owner');
        $project = $this->project($owner, 'Current project');
        $other = $this->project($owner, 'Other project');
        $folder = $other->folders()->create(['name' => 'foreign']);

        $this->actingAs($owner)->deleteJson(route('projects.folders.destroy', [$project, $folder]))->assertNotFound();

        $this->assertModelExists($folder);
    }

    public function test_foreign_comment_id_is_not_found_under_another_project(): void
    {
        $owner = $this->account('owner');
        $project = $this->project($owner, 'Current project');
        $other = $this->project($owner, 'Other project');
        $checkpoint = $other->checkpoints()->create(['user_id' => $owner->id, 'title' => 'Foreign checkpoint']);
        $comment = $checkpoint->comments()->create(['user_id' => $owner->id, 'body' => 'Foreign comment']);

        $this->actingAs($owner)->deleteJson(route('projects.comments.destroy', [$project, $comment]))->assertNotFound();

        $this->assertModelExists($comment);
    }

    #[TestWith(['editor'])]
    #[TestWith(['viewer'])]
    public function test_current_collaborators_can_comment_on_the_correct_private_checkpoint(string $role): void
    {
        $owner = $this->account('owner');
        $member = $this->account('member');
        $project = $this->project($owner, 'Current project');
        $project->collaborators()->attach($member, ['role' => $role]);
        $checkpoint = $project->checkpoints()->create(['user_id' => $owner->id, 'title' => 'Draft']);

        $this->actingAs($member)->post(route('projects.checkpoints.comments.store', [$project, $checkpoint]), ['body' => 'Collaborative comment'])
            ->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('comments', ['checkpoint_id' => $checkpoint->id, 'user_id' => $member->id, 'body' => 'Collaborative comment']);
        $this->assertDatabaseHas('activities', ['type' => 'comment_created', 'user_id' => $member->id]);
    }

    public function test_unrelated_user_cannot_comment_on_a_private_checkpoint(): void
    {
        $owner = $this->account('owner');
        $viewer = $this->account('viewer');
        $project = $this->project($owner, 'Current project');
        $checkpoint = $project->checkpoints()->create(['user_id' => $owner->id, 'title' => 'Draft']);

        $this->actingAs($viewer)->postJson(route('projects.checkpoints.comments.store', [$project, $checkpoint]), ['body' => 'Forbidden'])->assertForbidden();

        $this->assertDatabaseCount('comments', 0);
        $this->assertDatabaseCount('activities', 0);
    }

    public function test_private_checkpoint_page_renders_comment_actions_with_scoped_routes(): void
    {
        $owner = $this->account('owner');
        $project = $this->project($owner, 'Current project');
        $checkpoint = $project->checkpoints()->create(['user_id' => $owner->id, 'title' => 'Draft']);
        $comment = $checkpoint->comments()->create(['user_id' => $owner->id, 'body' => 'Own comment']);

        $this->actingAs($owner)->get(route('projects.checkpoints.show', [$project, $checkpoint]))
            ->assertSee('Own comment')->assertSee(route('projects.comments.destroy', [$project, $comment]), false);
    }

    public function test_comment_author_can_delete_their_comment_inside_the_project(): void
    {
        $owner = $this->account('owner');
        $member = $this->account('member');
        $project = $this->project($owner, 'Current project');
        $project->collaborators()->attach($member, ['role' => 'viewer']);
        $checkpoint = $project->checkpoints()->create(['user_id' => $owner->id, 'title' => 'Draft']);
        $comment = $checkpoint->comments()->create(['user_id' => $member->id, 'body' => 'Own comment']);

        $this->actingAs($member)->delete(route('projects.comments.destroy', [$project, $comment]))->assertRedirect();

        $this->assertModelMissing($comment);
    }

    public function test_collaborator_cannot_delete_someone_elses_comment(): void
    {
        $owner = $this->account('owner');
        $member = $this->account('member');
        $project = $this->project($owner, 'Current project');
        $project->collaborators()->attach($member, ['role' => 'editor']);
        $checkpoint = $project->checkpoints()->create(['user_id' => $owner->id, 'title' => 'Draft']);
        $comment = $checkpoint->comments()->create(['user_id' => $owner->id, 'body' => 'Owners comment']);

        $this->actingAs($member)->deleteJson(route('projects.comments.destroy', [$project, $comment]))->assertForbidden();

        $this->assertModelExists($comment);
    }

    public function test_revoked_comment_author_cannot_delete_a_private_comment(): void
    {
        $owner = $this->account('owner');
        $member = $this->account('member');
        $project = $this->project($owner, 'Current project');
        $checkpoint = $project->checkpoints()->create(['user_id' => $owner->id, 'title' => 'Draft']);
        $comment = $checkpoint->comments()->create(['user_id' => $member->id, 'body' => 'Old comment']);

        $this->actingAs($member)->deleteJson(route('projects.comments.destroy', [$project, $comment]))->assertForbidden();

        $this->assertModelExists($comment);
    }

    public function test_project_owner_can_delete_a_collaborators_comment(): void
    {
        $owner = $this->account('owner');
        $member = $this->account('member');
        $project = $this->project($owner, 'Current project');
        $checkpoint = $project->checkpoints()->create(['user_id' => $owner->id, 'title' => 'Draft']);
        $comment = $checkpoint->comments()->create(['user_id' => $member->id, 'body' => 'Member comment']);

        $this->actingAs($owner)->delete(route('projects.comments.destroy', [$project, $comment]))->assertRedirect();

        $this->assertModelMissing($comment);
    }

    #[TestWith(['PUT', 'projects.collaborators.update'])]
    #[TestWith(['DELETE', 'projects.collaborators.destroy'])]
    public function test_collaborator_management_does_not_resolve_a_member_of_another_project(string $method, string $routeName): void
    {
        $owner = $this->account('owner');
        $member = $this->account('member');
        $project = $this->project($owner, 'Current project');
        $other = $this->project($owner, 'Other project');
        $other->collaborators()->attach($member, ['role' => 'viewer']);

        $this->actingAs($owner)->json($method, route($routeName, [$project, $member]), ['role' => 'editor'])->assertNotFound();

        $this->assertDatabaseHas('project_collaborators', ['project_id' => $other->id, 'user_id' => $member->id, 'role' => 'viewer']);
        $this->assertDatabaseCount('project_collaborators', 1);
    }

    public function test_owner_can_change_and_remove_a_current_project_collaborator(): void
    {
        $owner = $this->account('owner');
        $member = $this->account('member');
        $project = $this->project($owner, 'Current project');
        $project->collaborators()->attach($member, ['role' => 'viewer']);

        $this->actingAs($owner)->put(route('projects.collaborators.update', [$project, $member]), ['role' => 'editor'])->assertRedirect();

        $this->assertDatabaseHas('project_collaborators', ['project_id' => $project->id, 'user_id' => $member->id, 'role' => 'editor']);

        $this->delete(route('projects.collaborators.destroy', [$project, $member]))->assertRedirect();

        $this->assertDatabaseMissing('project_collaborators', ['project_id' => $project->id, 'user_id' => $member->id]);
    }

    public function test_project_settings_forms_use_member_ids_for_numeric_usernames(): void
    {
        $owner = $this->account('owner');
        $member = $this->account('12345');
        $project = $this->project($owner, 'Current project');
        $project->collaborators()->attach($member, ['role' => 'viewer']);

        $response = $this->actingAs($owner)->get(route('projects.edit', $project));

        $response->assertSee('action="'.route('projects.collaborators.update', [$project, $member]).'"', false)
            ->assertDontSee('/collaborators/12345');
        $this->assertSame(2, substr_count($response->getContent(), 'action="'.route('projects.collaborators.update', [$project, $member]).'"'));
    }

    public function test_viewer_can_read_the_correct_private_checkpoint_with_files(): void
    {
        $owner = $this->account('owner');
        $member = $this->account('member');
        $project = $this->project($owner, 'Current project');
        $project->collaborators()->attach($member, ['role' => 'viewer']);
        $checkpoint = $project->checkpoints()->create(['user_id' => $owner->id, 'title' => 'Draft']);
        $file = $project->files()->create(['original_name' => 'shared-notes.txt', 'mime_type' => 'text/plain']);
        $file->versions()->create(['checkpoint_id' => $checkpoint->id, 'storage_path' => 'checkpoint-tests/notes.txt', 'size_bytes' => 5]);

        $this->actingAs($member)->get(route('projects.checkpoints.show', [$project, $checkpoint]))
            ->assertSee('Draft')->assertSee('shared-notes.txt');
    }

    private function account(string $username): User
    {
        return User::factory()->create(['username' => $username])->refresh();
    }

    private function project(User $owner, string $name): Project
    {
        return $owner->projects()->create(['name' => $name, 'visibility' => 'private']);
    }
}
