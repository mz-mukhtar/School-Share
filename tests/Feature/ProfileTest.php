<?php

namespace Tests\Feature;

use App\Mail\OtpVerificationMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk();
    }

    public function test_profile_information_can_be_updated(): void
    {
        Mail::fake();
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('otp.verify.show'));

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
        Mail::assertSent(OtpVerificationMail::class, fn (OtpVerificationMail $mail): bool => $mail->hasTo('test@example.com'));
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }

    #[TestWith([''])]
    #[TestWith([null])]
    #[TestWith(['   '])]
    public function test_blank_profile_username_preserves_identity_and_project_links(?string $username): void
    {
        $user = User::factory()->create(['username' => 'project_owner']);
        $project = $user->projects()->create(['name' => 'Identity project', 'visibility' => 'public']);

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name, 'email' => $user->email, 'username' => $username,
        ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        $this->assertSame('project_owner', $user->fresh()->username);
        $this->get(route('projects.show', $project))->assertOk()->assertSee(route('profile.public', 'project_owner'));
        $this->get(route('profile.public', 'project_owner'))->assertOk();
        $this->get(route('explore'))->assertOk();
        $this->get(route('feed'))->assertOk();
    }

    public function test_profile_can_change_to_an_available_username(): void
    {
        $user = User::factory()->create(['username' => 'before']);

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name, 'email' => $user->email, 'username' => 'after',
        ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        $this->assertSame('after', $user->fresh()->username);
    }

    public function test_generated_usernames_and_collision_suffixes_fit_profile_validation(): void
    {
        $name = str_repeat('a', 80);
        $first = User::factory()->create(['name' => $name]);
        $second = User::factory()->create(['name' => $name]);

        $this->actingAs($second)->patch(route('profile.update'), [
            'name' => $name, 'email' => $second->email, 'username' => $second->username,
        ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        $this->assertSame(50, strlen($first->username));
        $this->assertSame(str_repeat('a', 48).'_1', $second->fresh()->username);
    }

    public function test_profile_rejects_another_accounts_username(): void
    {
        User::factory()->create(['username' => 'taken']);
        $user = User::factory()->create(['username' => 'unchanged']);

        $this->actingAs($user)->patch(route('profile.update'), [
            'name' => $user->name, 'email' => $user->email, 'username' => 'taken',
        ])->assertSessionHasErrors('username');

        $this->assertSame('unchanged', $user->fresh()->username);
    }

    public function test_saving_a_legacy_account_generates_a_missing_username(): void
    {
        $user = User::factory()->create(['name' => 'Legacy Owner']);
        DB::table('users')->where('id', $user->id)->update(['username' => null]);

        $this->actingAs($user->fresh())->patch(route('profile.update'), [
            'name' => $user->name, 'email' => $user->email,
        ])->assertSessionHasNoErrors()->assertRedirect(route('profile.edit'));

        $this->assertSame('legacy_owner', $user->fresh()->username);
        $this->get(route('profile.public', 'legacy_owner'))->assertOk();
    }
}
