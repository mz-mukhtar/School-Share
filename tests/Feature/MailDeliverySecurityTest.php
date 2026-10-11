<?php

namespace Tests\Feature;

use App\Mail\AdminUpgradeReminderMail;
use App\Mail\ProjectInvitationMail;
use App\Mail\UpgradeInstructionsMail;
use App\MailDeliveryFailure;
use App\Models\User;
use App\Notifications\ProjectInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Mockery;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Tests\TestCase;

class MailDeliverySecurityTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith([false])]
    #[TestWith([true])]
    public function test_upgrade_delivery_failure_is_redacted_and_retry_reuses_the_saved_request(bool $adminMailFails): void
    {
        Exceptions::fake();
        $user = User::factory()->create();
        $transportError = new RuntimeException('smtp://user:secret-password@host recipient-private-body', 0, new RuntimeException('nested-secret'));
        Mail::shouldReceive('getDefaultDriver')->andReturn('array');
        Mail::shouldReceive('to')->times($adminMailFails ? 2 : 1)->andReturnSelf();
        if ($adminMailFails) {
            Mail::shouldReceive('send')->with(Mockery::type(UpgradeInstructionsMail::class))->once()->andReturnNull();
        }
        Mail::shouldReceive('send')->with(Mockery::type($adminMailFails ? AdminUpgradeReminderMail::class : UpgradeInstructionsMail::class))
            ->once()->andThrow($transportError);

        $this->actingAs($user)->post(route('upgrade.store'), ['plan' => 'pro', 'billing_cycle' => 'monthly'])
            ->assertRedirect(route('pricing'))->assertSessionHas('warning')->assertSessionMissing('success');

        $request = $user->upgradeRequests()->sole();
        $this->assertSame('failed', $request->mail_delivery_status);
        $this->assertSame('Upgrade instructions mail delivery failed (RuntimeException).', $request->mail_delivery_error);
        Exceptions::assertReported(fn (MailDeliveryFailure $failure): bool => $failure->getMessage() === $request->mail_delivery_error && $failure->getPrevious() === null);
        Exceptions::assertReportedCount(1);
        Exceptions::assertNotReported(RuntimeException::class);

        Mail::fake();
        $this->post(route('upgrade.store'), ['plan' => 'pro', 'billing_cycle' => 'monthly'])
            ->assertRedirect(route('pricing'))->assertSessionHas('success');

        $this->assertDatabaseCount('upgrade_requests', 1);
        $this->assertDatabaseHas('upgrade_requests', ['id' => $request->id, 'mail_delivery_status' => 'sent', 'mail_delivery_error' => null]);
        Mail::assertSent(UpgradeInstructionsMail::class);
        Mail::assertSent(AdminUpgradeReminderMail::class);
    }

    public function test_invitation_failure_is_redacted_and_retry_keeps_a_single_collaborator(): void
    {
        Exceptions::fake();
        Notification::fake();
        $owner = User::factory()->create();
        $invitee = User::factory()->create();
        $project = $owner->projects()->create(['name' => 'Invitation project', 'visibility' => 'private']);
        Mail::shouldReceive('getDefaultDriver')->andReturn('array');
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new RuntimeException('smtp-secret private-message'));

        $this->actingAs($owner)->post(route('projects.collaborators.store', $project), ['username' => $invitee->username, 'role' => 'viewer'])
            ->assertRedirect()->assertSessionHas('warning')->assertSessionMissing('success');

        $this->assertDatabaseHas('project_collaborators', ['project_id' => $project->id, 'user_id' => $invitee->id, 'role' => 'viewer']);
        Exceptions::assertReported(fn (MailDeliveryFailure $failure): bool => $failure->getMessage() === 'Collaborator invitation mail delivery failed (RuntimeException).' && $failure->getPrevious() === null);
        Exceptions::assertReportedCount(1);
        Exceptions::assertNotReported(RuntimeException::class);
        Notification::assertNothingSent();

        Mail::fake();
        $this->post(route('projects.collaborators.store', $project), ['username' => $invitee->username, 'role' => 'viewer'])
            ->assertRedirect()->assertSessionHas('success', 'Collaborator invitation resent.');

        $this->assertDatabaseCount('project_collaborators', 1);
        Mail::assertSent(ProjectInvitationMail::class, fn (ProjectInvitationMail $mail): bool => $mail->hasTo($invitee->email));
        Notification::assertSentTo($invitee, ProjectInvitationNotification::class);
    }
}
