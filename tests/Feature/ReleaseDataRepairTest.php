<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ReleaseDataRepairTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith([null])]
    #[TestWith([''])]
    #[TestWith(['   '])]
    public function test_username_backfill_handles_collisions_and_reruns_without_changing_existing_identities(?string $username): void
    {
        $legacy = User::factory()->create();
        $existing = User::factory()->create(['username' => 'user_'.$legacy->id]);
        DB::table('users')->where('id', $legacy->id)->update(['username' => $username]);
        $migration = require database_path('migrations/2026_10_11_104126_backfill_missing_usernames.php');

        $migration->up();
        $migration->up();
        $migration->down();

        $this->assertSame('user_'.$legacy->id.'_1', $legacy->fresh()->username);
        $this->assertSame('user_'.$legacy->id, $existing->fresh()->username);
        $project = $legacy->projects()->create(['name' => 'Legacy project', 'visibility' => 'public']);
        $this->actingAs($legacy->fresh())->get(route('projects.show', $project))->assertOk();
        $this->get(route('profile.public', $legacy->fresh()->username))->assertOk();
        $this->get(route('explore'))->assertOk();
        $this->get(route('feed'))->assertOk();
    }

    public function test_username_backfill_repairs_rows_beyond_the_first_chunk(): void
    {
        $users = User::factory()->count(101)->create();
        DB::table('users')->whereIn('id', $users->modelKeys())->update(['username' => null]);
        $migration = require database_path('migrations/2026_10_11_104126_backfill_missing_usernames.php');

        $migration->up();

        $this->assertSame(0, DB::table('users')->whereNull('username')->count());
        $this->assertSame(101, DB::table('users')->distinct()->count('username'));
        $this->assertSame('user_'.$users->last()->id, $users->last()->fresh()->username);
    }

    public function test_legacy_delivery_errors_are_redacted_without_losing_request_or_retry_state(): void
    {
        $user = User::factory()->create();
        $failed = $user->upgradeRequests()->create([
            'requested_plan' => 'pro', 'status' => 'pending', 'mail_delivery_status' => 'failed',
            'mail_delivery_error' => 'smtp://user:secret-password@host private-body',
        ]);
        $sent = $user->upgradeRequests()->create([
            'requested_plan' => 'custom', 'status' => 'approved', 'mail_delivery_status' => 'sent', 'mail_delivery_error' => null,
        ]);
        $migration = require database_path('migrations/2026_10_11_104127_redact_upgrade_mail_delivery_errors.php');

        $migration->up();
        $migration->down();

        $this->assertDatabaseHas('upgrade_requests', [
            'id' => $failed->id, 'status' => 'pending', 'mail_delivery_status' => 'failed',
            'mail_delivery_error' => 'Mail delivery failed. Retry delivery after checking the mail configuration.',
        ]);
        $this->assertDatabaseHas('upgrade_requests', ['id' => $sent->id, 'status' => 'approved', 'mail_delivery_error' => null]);
    }
}
