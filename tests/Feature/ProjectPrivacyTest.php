<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProjectPrivacyTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['marker'])]
    #[TestWith(['%'])]
    #[TestWith(['_'])]
    public function test_search_alternatives_never_escape_the_owner_constraint(string $search): void
    {
        $viewer = User::factory()->create(['username' => 'viewer'])->refresh();
        $owner = User::factory()->create(['username' => 'owner'])->refresh();
        $byName = $viewer->projects()->create(['name' => 'Marker in name', 'visibility' => 'private']);
        $byDescription = $viewer->projects()->create(['name' => 'Owned description match', 'description' => 'marker', 'visibility' => 'private']);
        $private = $owner->projects()->create(['name' => 'Secret project', 'description' => 'marker', 'visibility' => 'private']);
        $owner->projects()->create(['name' => 'Someone elses public project', 'description' => 'marker', 'visibility' => 'public']);

        $response = $this->actingAs($viewer)->get(route('projects.index', ['q' => $search]));

        $response->assertSee($byName->name)->assertSee($byDescription->name)->assertDontSee($private->name);
        $this->assertEqualsCanonicalizing([$byName->id, $byDescription->id], $response->viewData('projects')->modelKeys());
        $this->assertSame(2, $response->viewData('projects')->total());
    }

    public function test_unrelated_user_cannot_star_a_private_project_or_create_activity(): void
    {
        $owner = User::factory()->create()->refresh();
        $viewer = User::factory()->create()->refresh();
        $project = $owner->projects()->create(['name' => 'Private project', 'visibility' => 'private']);

        $response = $this->actingAs($viewer)->postJson(route('projects.star', $project));

        $response->assertForbidden();
        $this->assertSame(0, $project->refresh()->star_count);
        $this->assertDatabaseCount('starred_projects', 0);
        $this->assertDatabaseCount('activities', 0);
    }

    #[TestWith(['editor'])]
    #[TestWith(['viewer'])]
    public function test_authorized_collaborator_can_star_and_unstar_a_private_project(string $role): void
    {
        $owner = User::factory()->create()->refresh();
        $viewer = User::factory()->create()->refresh();
        $project = $owner->projects()->create(['name' => 'Private project', 'visibility' => 'private']);
        $project->collaborators()->attach($viewer, ['role' => $role]);

        $this->actingAs($viewer)->postJson(route('projects.star', $project))
            ->assertExactJson(['status' => 'starred', 'star_count' => 1]);

        $this->assertDatabaseHas('starred_projects', ['user_id' => $viewer->id, 'project_id' => $project->id]);
        $this->assertDatabaseHas('activities', ['user_id' => $viewer->id, 'type' => 'project_starred', 'subject_id' => $project->id]);

        $this->postJson(route('projects.star', $project))
            ->assertExactJson(['status' => 'unstarred', 'star_count' => 0]);

        $this->assertDatabaseMissing('starred_projects', ['user_id' => $viewer->id, 'project_id' => $project->id]);
        $this->assertDatabaseCount('activities', 1);
    }

    public function test_owner_can_star_a_private_project(): void
    {
        $owner = User::factory()->create()->refresh();
        $project = $owner->projects()->create(['name' => 'Private project', 'visibility' => 'private']);

        $this->actingAs($owner)->postJson(route('projects.star', $project))
            ->assertExactJson(['status' => 'starred', 'star_count' => 1]);

        $this->assertDatabaseHas('starred_projects', ['user_id' => $owner->id, 'project_id' => $project->id]);
        $this->assertDatabaseCount('activities', 1);
    }

    public function test_signed_in_nonmember_can_star_a_public_project(): void
    {
        $owner = User::factory()->create()->refresh();
        $viewer = User::factory()->create()->refresh();
        $project = $owner->projects()->create(['name' => 'Public project', 'visibility' => 'public']);

        $this->actingAs($viewer)->postJson(route('projects.star', $project))
            ->assertExactJson(['status' => 'starred', 'star_count' => 1]);

        $this->assertDatabaseHas('starred_projects', ['user_id' => $viewer->id, 'project_id' => $project->id]);
        $this->assertDatabaseCount('activities', 1);
    }

    public function test_revoked_collaborator_cannot_modify_an_existing_private_star(): void
    {
        $owner = User::factory()->create()->refresh();
        $viewer = User::factory()->create()->refresh();
        $project = $owner->projects()->create(['name' => 'Private project', 'visibility' => 'private', 'star_count' => 1]);
        $viewer->starredProjects()->attach($project);
        Activity::log('project_starred', $viewer, $project);

        $this->actingAs($viewer)->postJson(route('projects.star', $project))->assertForbidden();

        $this->assertDatabaseHas('starred_projects', ['user_id' => $viewer->id, 'project_id' => $project->id]);
        $this->assertSame(1, $project->refresh()->star_count);
        $this->assertDatabaseCount('activities', 1);
    }

    public function test_guest_cannot_star_a_project(): void
    {
        $owner = User::factory()->create()->refresh();
        $project = $owner->projects()->create(['name' => 'Public project', 'visibility' => 'public']);

        $this->post(route('projects.star', $project))->assertRedirect(route('login'));

        $this->assertDatabaseCount('starred_projects', 0);
        $this->assertDatabaseCount('activities', 0);
    }
}
