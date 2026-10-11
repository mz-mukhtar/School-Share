<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_populated_admin_pages_render_storage_and_plan_edit_forms(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['storage_used_bytes' => 1572864]);

        $this->actingAs($admin)->get(route('admin.users.index'))
            ->assertOk()->assertSee('1.5 MB')->assertSee(route('admin.users.updatePlan', $user));
        $this->get(route('admin.users.show', $user))->assertOk()->assertSee('1.5 MB');
        $this->get(route('admin.dashboard'))->assertOk()->assertSee('1.5 MB');
    }

    public function test_search_by_name_or_email_respects_the_plan_filter(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        User::factory()->create(['name' => 'Search Person', 'email' => 'free@example.com', 'plan' => 'free']);
        $byName = User::factory()->create(['name' => 'Search Person', 'email' => 'pro@example.com', 'plan' => 'pro']);
        $byEmail = User::factory()->create(['name' => 'Email Match', 'email' => 'search@example.com', 'plan' => 'pro']);
        User::factory()->create(['name' => 'Unrelated', 'email' => 'other@example.com', 'plan' => 'pro']);

        $this->actingAs($admin)->get(route('admin.users.index', ['search' => 'search', 'plan' => 'pro']))
            ->assertOk()->assertViewHas('users', fn (LengthAwarePaginator $users): bool => $users->getCollection()->pluck('id')->sort()->values()->all() === [$byName->id, $byEmail->id])
            ->assertDontSee('free@example.com')->assertDontSee('other@example.com');
    }

    #[TestWith(['free'])]
    #[TestWith(['pro'])]
    #[TestWith(['custom'])]
    public function test_admin_can_change_to_a_supported_plan(string $plan): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create();

        $this->actingAs($admin)->patch(route('admin.users.updatePlan', $user), ['plan' => $plan])
            ->assertSessionHasNoErrors()->assertSessionHas('success')->assertRedirect();

        $this->assertSame($plan, $user->fresh()->plan);
    }

    public function test_invalid_plan_is_rejected_without_changing_the_account(): void
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $user = User::factory()->create(['plan' => 'free']);

        $this->actingAs($admin)->patch(route('admin.users.updatePlan', $user), ['plan' => 'unknown'])
            ->assertSessionHasErrors('plan');

        $this->assertSame('free', $user->fresh()->plan);
    }

    public function test_non_admin_cannot_view_users_or_change_a_plan(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['plan' => 'free']);

        $this->actingAs($user)->get(route('admin.users.index'))->assertForbidden();
        $this->patch(route('admin.users.updatePlan', $other), ['plan' => 'pro'])->assertForbidden();

        $this->assertSame('free', $other->fresh()->plan);
    }
}
