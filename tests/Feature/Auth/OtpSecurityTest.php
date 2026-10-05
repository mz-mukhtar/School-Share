<?php

namespace Tests\Feature\Auth;

use App\EmailOtpService;
use App\Mail\OtpVerificationMail;
use App\Models\EmailOtpToken;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\URL;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class OtpSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_code_verifies_only_its_account_and_is_consumed(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        $other = User::factory()->unverified()->create();
        $code = $this->sendCode($user);
        Event::fake([Verified::class]);

        $this->actingAs($other)->post(route('otp.verify'), ['otp' => $code])->assertSessionHasErrors('otp');
        $this->assertNull($other->fresh()->email_verified_at);
        $this->assertDatabaseCount('email_otp_tokens', 1);

        $this->actingAs($user)->post(route('otp.verify'), ['otp' => $code])->assertRedirect(route('dashboard'));

        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertDatabaseCount('email_otp_tokens', 0);
        Event::assertDispatched(Verified::class, fn (Verified $event): bool => $event->user->id === $user->id);
        Mail::assertSentCount(1);
    }

    public function test_otp_storage_does_not_disclose_the_code_and_digests_are_email_specific(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        $code = $this->sendCode($user);
        $token = EmailOtpToken::where('user_id', $user->id)->firstOrFail();

        $this->assertSame($user->email, $token->email);
        $this->assertSame(64, strlen($token->otp_hash));
        $this->assertNotSame(hash('sha256', $code), $token->otp_hash);
        $this->assertArrayNotHasKey('otp', $token->getAttributes());
        $this->assertArrayNotHasKey('otp_hash', $token->toArray());

        User::whereKey($user->id)->update(['email' => 'different@example.com']);
        $this->actingAs($user->fresh())->post(route('otp.verify'), ['otp' => $code])->assertSessionHasErrors('otp');
        $this->assertNull($user->fresh()->email_verified_at);
        Mail::assertSentCount(1);
    }

    public function test_a_code_is_expired_at_its_exact_deadline(): void
    {
        $this->freezeTime();
        Mail::fake();
        $user = User::factory()->unverified()->create();
        $code = $this->sendCode($user);
        $this->travel(15)->minutes();

        $this->actingAs($user)->post(route('otp.verify'), ['otp' => $code])
            ->assertSessionHasErrors(['otp' => 'This code has expired. Please request a new one.']);

        $this->assertNull($user->fresh()->email_verified_at);
        Mail::assertSentCount(1);
    }

    public function test_five_wrong_guesses_disable_the_code_even_if_the_next_guess_is_correct(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        $code = $this->sendCode($user);
        $wrongCode = $code === '000000' ? '111111' : '000000';
        $this->actingAs($user);
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post(route('otp.verify'), ['otp' => $wrongCode])->assertSessionHasErrors('otp');
        }

        $this->post(route('otp.verify'), ['otp' => $code])
            ->assertSessionHasErrors(['otp' => 'Too many incorrect codes. Please request a new code.']);

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('email_otp_tokens', ['user_id' => $user->id, 'failed_attempts' => 5]);
        Mail::assertSentCount(1);
    }

    public function test_account_guess_limits_survive_source_changes(): void
    {
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);
        for ($attempt = 0; $attempt < 10; $attempt++) {
            $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.'.($attempt + 1)])
                ->post(route('otp.verify'), ['otp' => '000000'])->assertSessionHasErrors('otp');
        }

        $this->withServerVariables(['REMOTE_ADDR' => '192.0.2.100'])
            ->post(route('otp.verify'), ['otp' => '000000'])
            ->assertTooManyRequests()->assertHeader('Retry-After');
        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_source_guess_limit_applies_across_accounts(): void
    {
        for ($attempt = 0; $attempt < 30; $attempt++) {
            $user = User::factory()->unverified()->create();
            $this->actingAs($user)->post(route('otp.verify'), ['otp' => '000000'])->assertSessionHasErrors('otp');
        }

        $this->actingAs(User::factory()->unverified()->create())
            ->post(route('otp.verify'), ['otp' => '000000'])->assertTooManyRequests();
    }

    public function test_resend_rotates_the_code_without_resetting_the_persisted_send_budget(): void
    {
        $this->freezeTime();
        Mail::fake();
        $user = User::factory()->unverified()->create();
        $this->sendCode($user);
        $oldHash = EmailOtpToken::where('user_id', $user->id)->value('otp_hash');

        $this->actingAs($user)->post(route('otp.resend'))->assertRedirect();
        $this->assertNotSame($oldHash, EmailOtpToken::where('user_id', $user->id)->value('otp_hash'));
        $this->post(route('verification.send'))->assertRedirect(route('otp.verify.show'));
        Cache::flush();
        $this->post(route('otp.resend'))->assertTooManyRequests()->assertHeader('Retry-After');

        $this->assertDatabaseCount('email_otp_tokens', 1);
        $this->assertDatabaseHas('email_otp_tokens', ['user_id' => $user->id, 'send_count' => 3]);
        Mail::assertSentCount(3);

        $this->travel(10)->minutes();
        $this->post(route('otp.resend'))->assertRedirect();
        $this->assertDatabaseHas('email_otp_tokens', ['user_id' => $user->id, 'send_count' => 1]);
        Mail::assertSentCount(4);
    }

    public function test_resend_rate_limit_history_is_independent_of_token_rows_and_shared_by_legacy_route(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        $this->actingAs($user);
        foreach (['otp.resend', 'verification.send', 'otp.resend'] as $route) {
            $this->post(route($route))->assertRedirect();
            EmailOtpToken::where('user_id', $user->id)->delete();
        }

        $this->post(route('verification.send'))->assertTooManyRequests();
        Mail::assertSentCount(3);
    }

    public function test_email_change_invalidates_the_old_code_and_sends_a_new_recipient_bound_code(): void
    {
        Mail::fake();
        $user = User::factory()->unverified()->create();
        $this->sendCode($user);
        $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
            'id' => $user->id, 'hash' => sha1($user->email),
        ]);
        $this->actingAs($user)->get($url)->assertRedirect();
        $this->assertDatabaseCount('email_otp_tokens', 0);

        $this->patch(route('profile.update'), ['name' => $user->name, 'email' => 'new-address@example.com'])
            ->assertRedirect(route('otp.verify.show'))->assertSessionHas('success');
        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('email_otp_tokens', ['user_id' => $user->id, 'email' => 'new-address@example.com']);
        $newMail = Mail::sent(OtpVerificationMail::class)->last();
        $this->assertTrue($newMail->hasTo('new-address@example.com'));
        $newCode = $newMail->otp;
        $this->get($url)->assertForbidden();
        $this->post(route('otp.verify'), ['otp' => $newCode])->assertRedirect(route('dashboard'));
        $this->assertNotNull($user->fresh()->email_verified_at);
        Mail::assertSentCount(2);
    }

    public function test_failed_registration_mail_preserves_an_unverified_account_and_allows_retry(): void
    {
        Exceptions::fake();
        Mail::shouldReceive('getDefaultDriver')->andReturn('array');
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('Synthetic SMTP failure'));

        $this->post(route('register'), [
            'name' => 'New account', 'email' => 'retry@example.com',
            'password' => 'password', 'password_confirmation' => 'password',
        ])->assertRedirect(route('otp.verify.show'))->assertSessionHas('warning');

        $user = User::where('email', 'retry@example.com')->firstOrFail();
        $this->assertAuthenticatedAs($user);
        $this->assertNull($user->email_verified_at);
        $this->assertDatabaseHas('email_otp_tokens', ['user_id' => $user->id, 'delivery_status' => 'failed']);
        Exceptions::assertReported(fn (\RuntimeException $exception): bool => $exception->getMessage() === 'OTP mail delivery failed (RuntimeException).');
        Mail::fake();

        $this->post(route('otp.resend'))->assertRedirect()->assertSessionHas('success');
        Mail::assertSent(OtpVerificationMail::class);
        $this->assertDatabaseHas('email_otp_tokens', ['user_id' => $user->id, 'delivery_status' => 'sent']);
    }

    #[TestWith(['otp.verify'])]
    #[TestWith(['otp.resend'])]
    public function test_guests_cannot_submit_verification_actions(string $route): void
    {
        $this->post(route($route), ['otp' => '123456'])->assertRedirect(route('login'));
        $this->assertDatabaseCount('email_otp_tokens', 0);
    }

    public function test_verified_accounts_cannot_receive_another_code(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('otp.resend'))->assertRedirect(route('dashboard'));

        Mail::assertNothingSent();
        $this->assertDatabaseCount('email_otp_tokens', 0);
    }

    public function test_invalid_code_input_is_not_retained_in_the_session(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)->post(route('otp.verify'), ['otp' => '12345'])
            ->assertSessionHasErrors('otp')
            ->assertSessionMissing('_old_input.otp');

        $this->assertNull($user->fresh()->email_verified_at);
    }

    public function test_email_change_mail_failure_leaves_a_recoverable_unverified_account(): void
    {
        $user = User::factory()->create();
        Mail::shouldReceive('to')->once()->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('Synthetic SMTP failure'));

        $this->actingAs($user)->patch(route('profile.update'), ['name' => $user->name, 'email' => 'changed@example.com'])
            ->assertRedirect(route('otp.verify.show'))->assertSessionHas('warning');

        $this->assertNull($user->fresh()->email_verified_at);
        $this->assertDatabaseHas('email_otp_tokens', ['user_id' => $user->id, 'email' => 'changed@example.com', 'delivery_status' => 'failed']);
        $this->get(route('dashboard'))->assertRedirect(route('otp.verify.show'));
    }

    public function test_the_security_migration_invalidates_legacy_plaintext_codes_without_deleting_accounts(): void
    {
        $user = User::factory()->unverified()->create();
        $migration = require database_path('migrations/2026_10_05_000004_protect_email_otp_verifiers.php');
        $migration->down();
        DB::table('email_otp_tokens')->insert([
            'user_id' => $user->id, 'otp' => '123456', 'expires_at' => now()->addMinutes(15),
            'created_at' => now(), 'updated_at' => now(),
        ]);

        $migration->up();

        $this->assertModelExists($user);
        $this->assertDatabaseCount('email_otp_tokens', 0);
        $this->actingAs($user)->post(route('otp.verify'), ['otp' => '123456'])->assertSessionHasErrors('otp');
        Mail::fake();
        $this->post(route('otp.resend'))->assertRedirect()->assertSessionHas('success');
        Mail::assertSent(OtpVerificationMail::class);
        $this->assertDatabaseHas('email_otp_tokens', ['user_id' => $user->id, 'email' => $user->email, 'delivery_status' => 'sent']);
    }

    private function sendCode(User $user): string
    {
        $this->assertTrue(app(EmailOtpService::class)->send($user));

        return Mail::sent(OtpVerificationMail::class)->last()->otp;
    }
}
