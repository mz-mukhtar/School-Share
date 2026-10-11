<?php

namespace Tests\Feature;

use App\InstallationDatabase;
use App\Models\User;
use App\SchoolShareInstaller;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class InstallationTest extends TestCase
{
    use RefreshDatabase;

    private string $root;

    private string $token = '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef';

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/schoolshare-installer-'.Str::uuid();
        File::ensureDirectoryExists($this->root.'/storage/app', 0700);
        config(['installation.enabled' => true, 'installation.token_hash' => hash('sha256', $this->token), 'installation.root' => $this->root]);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_uninstalled_site_redirects_to_setup_and_does_not_report_ready(): void
    {
        $this->get('/')->assertRedirect('/install');
        $this->get('/ready')->assertServiceUnavailable();
        $this->post('/register', ['email' => 'outsider@example.com'])->assertServiceUnavailable();
        $this->assertDatabaseCount('users', 0);
    }

    public function test_installation_is_not_available_when_disabled_or_permanently_locked(): void
    {
        config(['installation.enabled' => false]);
        $this->get('https://schoolshare.example/install')->assertNotFound();
        config(['installation.enabled' => true]);
        File::put($this->root.'/storage/app/installed.lock', 'installed');
        $this->get('https://schoolshare.example/install')->assertNotFound();
        $this->post('https://schoolshare.example/install')->assertNotFound();
    }

    public function test_setup_requires_https_even_before_unlock(): void
    {
        $this->get('http://schoolshare.example/install')->assertForbidden()->assertSee('Enable HTTPS');
        $this->post('http://schoolshare.example/install/unlock', ['setup_token' => $this->token])->assertForbidden();
    }

    public function test_locked_wizard_does_not_disclose_configuration_or_render_database_fields(): void
    {
        $this->get('https://schoolshare.example/install')->assertSee('Unlock private setup')->assertDontSee('name="db_password"', false)
            ->assertDontSee($this->token)->assertHeader('Referrer-Policy', 'no-referrer')->assertHeader('X-Robots-Tag', 'noindex, nofollow');
    }

    public function test_correct_token_unlocks_only_the_current_session(): void
    {
        $this->post('https://schoolshare.example/install/unlock', ['setup_token' => $this->token])
            ->assertRedirect(route('installation.show'))->assertSessionHas('installation.authorization', hash('sha256', $this->token));
        $this->get('https://schoolshare.example/install')->assertSee('name="db_password"', false)->assertDontSee($this->token);
    }

    public function test_incorrect_token_is_not_saved_in_old_input(): void
    {
        $wrongToken = str_repeat('a', 64);
        $this->from('https://schoolshare.example/install')->post('https://schoolshare.example/install/unlock', ['setup_token' => $wrongToken])
            ->assertSessionHasErrors(['setup_token' => 'The setup token is incorrect. Read the private setup-token.txt file in cPanel.'])
            ->assertSessionMissing('_old_input.setup_token')->assertSessionMissing('installation.authorization');
    }

    public function test_token_attempts_are_rate_limited(): void
    {
        for ($attempt = 0; $attempt < 5; $attempt++) {
            $this->post('https://schoolshare.example/install/unlock', ['setup_token' => str_repeat('a', 64)])->assertRedirect();
        }
        $this->post('https://schoolshare.example/install/unlock', ['setup_token' => $this->token])->assertTooManyRequests();
    }

    public function test_direct_install_and_expired_setup_authorization_are_forbidden(): void
    {
        $this->post('https://schoolshare.example/install', [])->assertForbidden();
        $this->withSession(['installation.authorization' => hash('sha256', $this->token), 'installation.expires_at' => time() - 1])
            ->post('https://schoolshare.example/install', [])->assertForbidden();
        $this->assertDatabaseCount('users', 0);
        $this->assertFileDoesNotExist($this->root.'/.env');
    }

    #[TestWith(['site_url', 'http://schoolshare.example'])]
    #[TestWith(['site_url', 'https://schoolshare.example/path'])]
    #[TestWith(['site_url', ['invalid']])]
    #[TestWith(['site_name', "SchoolShare\nAPP_DEBUG=true"])]
    #[TestWith(['db_connection', 'sqlite'])]
    #[TestWith(['db_port', 0])]
    #[TestWith(['db_database', 'database; DROP TABLE users'])]
    #[TestWith(['mail_scheme', 'none'])]
    #[TestWith(['mail_from_address', 'invalid'])]
    #[TestWith(['admin_password', 'weak'])]
    public function test_invalid_settings_are_rejected_without_persisting_secrets(string $field, string|int|array $value): void
    {
        $payload = $this->payload();
        $payload[$field] = $value;

        $this->authorized()->post('https://schoolshare.example/install', $payload)->assertSessionHasErrors($field)
            ->assertSessionMissing('_old_input.db_password')->assertSessionMissing('_old_input.mail_password')
            ->assertSessionMissing('_old_input.admin_password')->assertSessionMissing('_old_input.admin_password_confirmation');

        $this->assertFileDoesNotExist($this->root.'/.env');
        $this->assertDatabaseCount('users', 0);
    }

    public function test_form_escapes_previous_site_name_and_keeps_password_fields_empty(): void
    {
        $this->authorized()->withSession(['_old_input' => ['site_name' => '<script>alert(1)</script>', 'db_password' => 'never-render-this']])
            ->get('https://schoolshare.example/install')->assertSee('&lt;script&gt;', false)->assertDontSee('<script>alert(1)</script>', false)->assertDontSee('never-render-this');
    }

    public function test_installation_preserves_leading_and_trailing_spaces_in_passwords(): void
    {
        $payload = $this->payload();
        foreach (['db_password', 'mail_password', 'admin_password', 'admin_password_confirmation'] as $field) {
            $payload[$field] = ' '.$payload[$field].' ';
        }
        $this->partialMock(SchoolShareInstaller::class)->shouldReceive('install')->once()
            ->withArgs(function (array $settings) use ($payload): bool {
                foreach (['db_password', 'mail_password', 'admin_password'] as $field) {
                    $this->assertSame($payload[$field], $settings[$field]);
                }

                return true;
            })->andReturn(false);

        $this->authorized()->post('https://schoolshare.example/install', $payload)->assertRedirect(route('login'));
        $this->assertDatabaseCount('users', 0);
        $this->assertFileDoesNotExist($this->root.'/.env');
    }

    public function test_existing_database_is_refused_without_touching_its_accounts(): void
    {
        $user = User::factory()->create();

        try {
            app(InstallationDatabase::class)->requireEmptyDatabase();
            $this->fail('Existing data must be preserved.');
        } catch (ValidationException $exception) {
            $this->assertSame('Fresh installation requires an empty database. No existing tables or views will be overwritten.', $exception->errors()['db_database'][0]);
            $this->assertModelExists($user);
            $this->assertDatabaseCount('users', 1);
        }
    }

    public function test_interrupted_setup_shows_recovery_warning_instead_of_install_form(): void
    {
        File::put($this->root.'/storage/app/installation-started.lock', 'started');

        $this->authorized()->get('https://schoolshare.example/install')->assertSee('Installation has already started')->assertDontSee('name="db_password"', false);
    }

    private function authorized(): static
    {
        return $this->withSession(['installation.authorization' => hash('sha256', $this->token), 'installation.expires_at' => time() + 1800]);
    }

    /** @return array<string, string|int> */
    private function payload(): array
    {
        return [
            'site_name' => 'SchoolShare', 'site_url' => 'https://schoolshare.example',
            'db_connection' => 'mysql', 'db_host' => 'localhost', 'db_port' => 3306,
            'db_database' => 'account_schoolshare', 'db_username' => 'account_schooluser', 'db_password' => 'Database#password',
            'mail_scheme' => 'smtps', 'mail_host' => 'smtp.example.com', 'mail_port' => 465,
            'mail_username' => 'smtp-user', 'mail_password' => 'SMTP#password', 'mail_from_address' => 'sender@example.com',
            'admin_name' => 'Administrator', 'admin_email' => 'admin@example.com',
            'admin_password' => 'Admin#Password123', 'admin_password_confirmation' => 'Admin#Password123',
        ];
    }
}
