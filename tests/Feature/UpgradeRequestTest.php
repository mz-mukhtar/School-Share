<?php

namespace Tests\Feature;

use App\Mail\AdminUpgradeReminderMail;
use App\Mail\UpgradeInstructionsMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class UpgradeRequestTest extends TestCase
{
    use RefreshDatabase;

    public function test_repeated_submissions_reuse_the_pending_request_and_original_billing_cycle(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('upgrade.store'), ['plan' => 'pro', 'billing_cycle' => 'monthly'])
            ->assertRedirect(route('pricing'))->assertSessionHas('success');
        $request = $user->upgradeRequests()->sole();
        $this->post(route('upgrade.store'), ['plan' => 'pro', 'billing_cycle' => 'yearly'])
            ->assertRedirect(route('pricing'))->assertSessionHas('success');

        $this->assertDatabaseCount('upgrade_requests', 1);
        $this->assertDatabaseHas('upgrade_requests', [
            'id' => $request->id, 'user_id' => $user->id, 'requested_plan' => 'pro', 'billing_cycle' => 'monthly',
            'status' => 'pending', 'mail_delivery_status' => 'sent', 'mail_delivery_error' => null,
        ]);
        Mail::assertSent(UpgradeInstructionsMail::class, 2);
        Mail::assertSent(AdminUpgradeReminderMail::class, 2);
    }

    public function test_processed_requests_do_not_prevent_a_new_pending_request(): void
    {
        Mail::fake();
        $user = User::factory()->create();
        $user->upgradeRequests()->create(['requested_plan' => 'pro', 'billing_cycle' => 'monthly', 'status' => 'rejected']);

        $this->actingAs($user)->post(route('upgrade.store'), ['plan' => 'pro', 'billing_cycle' => 'yearly'])
            ->assertRedirect(route('pricing'))->assertSessionHas('success');

        $this->assertDatabaseCount('upgrade_requests', 2);
        $this->assertDatabaseHas('upgrade_requests', ['user_id' => $user->id, 'status' => 'pending', 'billing_cycle' => 'yearly']);
        Mail::assertSent(UpgradeInstructionsMail::class);
        Mail::assertSent(AdminUpgradeReminderMail::class);
    }

    public function test_invalid_request_does_not_create_an_upgrade_or_send_mail(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('upgrade.store'), ['plan' => 'invalid', 'billing_cycle' => 'invalid'])
            ->assertSessionHasErrors(['plan', 'billing_cycle']);

        $this->assertDatabaseCount('upgrade_requests', 0);
        Mail::assertNothingSent();
    }

    public function test_unverified_account_cannot_submit_an_upgrade(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('upgrade.store'), ['plan' => 'pro', 'billing_cycle' => 'monthly'])
            ->assertRedirect(route('otp.verify.show'));

        $this->assertDatabaseCount('upgrade_requests', 0);
        Mail::assertNothingSent();
    }
}
