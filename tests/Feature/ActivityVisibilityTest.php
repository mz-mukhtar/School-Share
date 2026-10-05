<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Project;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ActivityVisibilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_feed_hides_private_project_checkpoint_and_comment_activity_from_followers(): void
    {
        $owner = $this->account('owner');
        $viewer = $this->account('viewer');
        $viewer->following()->attach($owner);
        $private = $owner->projects()->create(['name' => 'Secret research', 'visibility' => 'private']);
        $public = $owner->projects()->create(['name' => 'Public research', 'visibility' => 'public']);
        $this->projectActivities($private, $owner);
        $publicActivities = $this->projectActivities($public, $owner);

        $response = $this->actingAs($viewer)->get(route('feed'));

        $response->assertDontSee('Secret research')->assertDontSee('Secret research checkpoint')->assertSee('Public research');
        $this->assertEqualsCanonicalizing($publicActivities, $response->viewData('activities')->modelKeys());
        $this->assertSame(3, $response->viewData('activities')->total());
    }

    #[TestWith(['editor'])]
    #[TestWith(['viewer'])]
    public function test_feed_includes_private_activity_for_current_collaborators(string $role): void
    {
        $owner = $this->account('owner');
        $viewer = $this->account('viewer');
        $viewer->following()->attach($owner);
        $project = $owner->projects()->create(['name' => 'Shared research', 'visibility' => 'private']);
        $project->collaborators()->attach($viewer, ['role' => $role]);
        $ids = $this->projectActivities($project, $owner);

        $response = $this->actingAs($viewer)->get(route('feed'));

        $response->assertSee('Shared research')->assertSee('Shared research checkpoint');
        $this->assertEqualsCanonicalizing($ids, $response->viewData('activities')->modelKeys());
    }

    public function test_owner_can_see_own_private_activity(): void
    {
        $owner = $this->account('owner');
        $project = $owner->projects()->create(['name' => 'Own research', 'visibility' => 'private']);
        $ids = $this->projectActivities($project, $owner);

        $response = $this->actingAs($owner)->get(route('feed'));

        $response->assertSee('Own research');
        $this->assertEqualsCanonicalizing($ids, $response->viewData('activities')->modelKeys());
    }

    public function test_old_activity_disappears_when_a_public_project_becomes_private(): void
    {
        $owner = $this->account('owner');
        $viewer = $this->account('viewer');
        $viewer->following()->attach($owner);
        $project = $owner->projects()->create(['name' => 'Formerly public', 'visibility' => 'public']);
        $this->projectActivities($project, $owner);
        $project->update(['visibility' => 'private']);

        $response = $this->actingAs($viewer)->get(route('feed'));

        $response->assertDontSee('Formerly public');
        $this->assertSame(0, $response->viewData('activities')->total());
    }

    public function test_old_and_self_authored_activity_disappears_after_collaborator_removal(): void
    {
        $owner = $this->account('owner');
        $viewer = $this->account('viewer');
        $viewer->following()->attach($owner);
        $project = $owner->projects()->create(['name' => 'Revoked project', 'visibility' => 'private']);
        $project->collaborators()->attach($viewer, ['role' => 'editor']);
        $this->projectActivities($project, $owner);
        $this->projectActivities($project, $viewer);
        $project->collaborators()->detach($viewer);

        $response = $this->actingAs($viewer)->get(route('feed'));

        $response->assertDontSee('Revoked project');
        $this->assertSame(0, $response->viewData('activities')->total());
    }

    public function test_permission_filter_runs_before_feed_pagination(): void
    {
        $owner = $this->account('owner');
        $viewer = $this->account('viewer');
        $viewer->following()->attach($owner);
        $public = $owner->projects()->create(['name' => 'Public research', 'visibility' => 'public']);
        $activity = Activity::log('project_created', $owner, $public);
        $private = $owner->projects()->create(['name' => 'Private research', 'visibility' => 'private']);
        for ($index = 0; $index < 25; $index++) {
            Activity::log('project_created', $owner, $private);
        }

        $response = $this->actingAs($viewer)->get(route('feed'));

        $response->assertSee('Public research')->assertDontSee('Private research');
        $this->assertSame([$activity->id], $response->viewData('activities')->modelKeys());
        $this->assertSame(1, $response->viewData('activities')->total());
    }

    public function test_deleted_subjects_and_projects_cannot_leak_stored_activity_metadata(): void
    {
        $owner = $this->account('owner');
        $viewer = $this->account('viewer');
        $viewer->following()->attach($owner);
        $project = $owner->projects()->create(['name' => 'Deleted secret', 'visibility' => 'public']);
        $this->projectActivities($project, $owner);
        $project->delete();

        $response = $this->actingAs($viewer)->get(route('feed'));

        $response->assertDontSee('Deleted secret');
        $this->assertSame(0, $response->viewData('activities')->total());
    }

    public function test_public_follow_activity_is_visible_but_unfollowed_authors_are_excluded(): void
    {
        $owner = $this->account('owner');
        $viewer = $this->account('viewer');
        $other = $this->account('other');
        $viewer->following()->attach($owner);
        $follow = Activity::log('follow', $owner, $other);
        $unfollowed = $other->projects()->create(['name' => 'Unfollowed project', 'visibility' => 'public']);
        Activity::log('project_created', $other, $unfollowed);

        $response = $this->actingAs($viewer)->get(route('feed'));

        $response->assertSee('started following')->assertDontSee('Unfollowed project');
        $this->assertSame([$follow->id], $response->viewData('activities')->modelKeys());
    }

    public function test_public_profile_counts_only_public_activity_even_for_the_owner(): void
    {
        $this->freezeTime();
        $owner = $this->account('owner');
        $private = $owner->projects()->create(['name' => 'Private research', 'visibility' => 'private']);
        $public = $owner->projects()->create(['name' => 'Public research', 'visibility' => 'public']);
        $this->projectActivities($private, $owner);
        $publicIds = $this->projectActivities($public, $owner);

        $response = $this->get(route('profile.public', $owner->username));

        $response->assertSee('Public research')->assertDontSee('Private research');
        $this->assertSame(3, (int) array_sum($response->viewData('contributions')));
        $this->assertEqualsCanonicalizing($publicIds, $response->viewData('recentActivities')->modelKeys());

        $ownerResponse = $this->actingAs($owner)->get(route('profile.public', $owner->username));

        $this->assertSame(3, (int) array_sum($ownerResponse->viewData('contributions')));
    }

    public function test_public_profile_counts_change_with_project_visibility(): void
    {
        $this->freezeTime();
        $owner = $this->account('owner');
        $project = $owner->projects()->create(['name' => 'Now private', 'visibility' => 'public']);
        $this->projectActivities($project, $owner);
        $project->update(['visibility' => 'private']);

        $response = $this->get(route('profile.public', $owner->username));

        $response->assertDontSee('Now private');
        $this->assertSame([], $response->viewData('contributions'));
        $this->assertSame(0, $response->viewData('recentActivities')->count());
    }

    #[TestWith(['public', null, true])]
    #[TestWith(['private', null, false])]
    #[TestWith(['private', 'owner', true])]
    #[TestWith(['private', 'editor', true])]
    #[TestWith(['private', 'viewer', true])]
    #[TestWith(['private', 'stranger', false])]
    public function test_project_query_visibility_matches_instance_access(string $visibility, ?string $role, bool $allowed): void
    {
        $owner = $this->account('owner');
        $project = $owner->projects()->create(['name' => 'Permission project', 'visibility' => $visibility]);
        $viewer = $role === null ? null : ($role === 'owner' ? $owner : $this->account('viewer'));
        if (in_array($role, ['editor', 'viewer'], true)) {
            $project->collaborators()->attach($viewer, ['role' => $role]);
        }

        $queryAllows = Project::whereKey($project->id)->accessibleTo($viewer)->exists();

        $this->assertSame($allowed, $queryAllows);
        $this->assertSame($allowed, $project->hasAccess($viewer));
    }

    public function test_feed_does_not_use_stale_checkpoint_metadata_for_project_names_or_links(): void
    {
        $owner = $this->account('owner');
        $viewer = $this->account('viewer');
        $viewer->following()->attach($owner);
        $project = $owner->projects()->create(['name' => 'Current name', 'visibility' => 'public']);
        $checkpoint = $project->checkpoints()->create(['user_id' => $owner->id, 'title' => 'Draft']);
        Activity::log('checkpoint_created', $owner, $checkpoint, ['project_name' => 'Stale sensitive name', 'project_slug' => 'obsolete-slug']);

        $response = $this->actingAs($viewer)->get(route('feed'));

        $response->assertSee('Current name')->assertDontSee('Stale sensitive name')
            ->assertSee(route('projects.show', $project), false)->assertDontSee('obsolete-slug');
    }

    public function test_unknown_or_missing_activity_subjects_are_not_exposed_in_the_feed(): void
    {
        $viewer = $this->account('viewer');
        Activity::log('checkpoint_created', $viewer, null, ['project_name' => 'Orphaned private project']);
        Activity::create(['user_id' => $viewer->id, 'type' => 'future_type', 'subject_type' => 'Unknown', 'subject_id' => 1, 'created_at' => now()]);

        $response = $this->actingAs($viewer)->get(route('feed'));

        $response->assertOk()->assertDontSee('Orphaned private project');
        $this->assertSame(0, $response->viewData('activities')->total());
    }

    private function account(string $username): User
    {
        return User::factory()->create(['username' => $username])->refresh();
    }

    /** @return array<int, int> */
    private function projectActivities(Project $project, User $actor): array
    {
        $checkpoint = $project->checkpoints()->create(['user_id' => $actor->id, 'title' => $project->name.' checkpoint']);
        $comment = $checkpoint->comments()->create(['user_id' => $actor->id, 'body' => 'Discussion']);
        $meta = ['project_name' => $project->name, 'project_slug' => $project->slug];

        return [
            Activity::log('project_created', $actor, $project, $meta)->id,
            Activity::log('checkpoint_created', $actor, $checkpoint, $meta)->id,
            Activity::log('comment_created', $actor, $comment, $meta)->id,
        ];
    }
}
