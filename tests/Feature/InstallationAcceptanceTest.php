<?php

namespace Tests\Feature;

use App\InstallationBootstrap;
use App\Mail\OtpVerificationMail;
use App\MailDeliveryFailure;
use App\Models\User;
use Dotenv\Dotenv;
use Illuminate\Foundation\Testing\RefreshDatabaseState;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Exceptions;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

#[Group('installation')]
class InstallationAcceptanceTest extends TestCase
{
    private ?string $root = null;

    private bool $canResetDatabase = false;

    private array $connection = [];

    private string $driver = '';

    protected function setUp(): void
    {
        parent::setUp();
        if (getenv('STAGING_INSTALLATION') !== '1') {
            $this->markTestSkipped('Opt-in installer acceptance requires the isolated engine stack.');
        }
        $this->assertTrue(app()->environment('testing'));
        $this->assertSame('schoolshare_testing', DB::connection()->getDatabaseName());
        $this->driver = config('database.default');
        $this->assertContains($this->driver, ['mysql', 'mariadb']);
        $this->canResetDatabase = true;
        $this->connection = config('database.connections.'.$this->driver);
        Schema::dropAllViews();
        Schema::dropAllTables();
        $this->root = sys_get_temp_dir().'/schoolshare-install-'.Str::uuid();
        foreach (['app/storage/app/private', 'app/storage/app/public', 'app/storage/framework/cache/data', 'app/storage/framework/sessions', 'app/storage/framework/views', 'app/storage/logs', 'app/bootstrap/cache', 'public_html'] as $directory) {
            File::ensureDirectoryExists($this->root.'/'.$directory, 0700);
        }
        File::put($this->root.'/app/.cpanel-layout', 'packaged');
        InstallationBootstrap::prepare($this->root.'/app');
        $values = Dotenv::createArrayBacked($this->root.'/app')->load();
        config(['installation.enabled' => true, 'installation.token_hash' => $values['INSTALL_TOKEN_HASH'], 'installation.root' => $this->root.'/app', 'installation.public_path' => $this->root.'/public_html', 'app.key' => $values['APP_KEY']]);
    }

    protected function tearDown(): void
    {
        try {
            if ($this->canResetDatabase) {
                config(['database.default' => $this->driver, 'database.connections.'.$this->driver => $this->connection]);
                DB::purge($this->driver);
                Schema::dropAllViews();
                Schema::dropAllTables();
                RefreshDatabaseState::$migrated = false;
            }
            if ($this->root !== null) {
                File::deleteDirectory($this->root);
            }
        } finally {
            parent::tearDown();
        }
    }

    public function test_fresh_http_installation_creates_one_unverified_admin_and_permanently_locks_setup(): void
    {
        Mail::fake();
        $key = config('app.key');
        $payload = $this->payload();
        $payload['APP_DEBUG'] = true;
        $payload['is_admin'] = false;

        $this->authorized()->post('https://schoolshare.example/install', $payload)->assertRedirect(route('login'))->assertSessionHas('status');

        $this->assertDatabaseCount('users', 1);
        $admin = User::sole();
        $this->assertSame('admin@example.com', $admin->email);
        $this->assertTrue((bool) $admin->is_admin);
        $this->assertNull($admin->email_verified_at);
        $this->assertTrue(Hash::check('Admin#Password123', $admin->password));
        $this->assertSame('free', $admin->plan);
        $this->assertDatabaseHas('email_otp_tokens', ['user_id' => $admin->id, 'delivery_status' => 'sent']);
        Mail::assertSent(OtpVerificationMail::class, fn (OtpVerificationMail $mail): bool => $mail->hasTo('admin@example.com'));
        $saved = Dotenv::createArrayBacked($this->root.'/app')->load();
        $this->assertSame($key, $saved['APP_KEY']);
        $this->assertSame('false', $saved['APP_DEBUG']);
        $this->assertSame('false', $saved['INSTALL_ENABLED']);
        $this->assertSame('', $saved['INSTALL_TOKEN_HASH']);
        $this->assertSame('true', $saved['MAIL_REQUIRE_TLS']);
        $this->assertSame('admin@example.com', $saved['SCHOOLSHARE_ADMIN_EMAIL']);
        $this->assertSame($payload['db_password'], $saved['DB_PASSWORD']);
        $this->assertArrayNotHasKey('ADMIN_PASSWORD', $saved);
        $this->assertFileExists($this->root.'/app/storage/app/installed.lock');
        $this->assertFileDoesNotExist($this->root.'/app/setup-token.txt');
        $this->assertSame(realpath($this->root.'/app/storage/app/public'), realpath($this->root.'/public_html/storage'));
        $this->post('https://schoolshare.example/install', $payload)->assertNotFound();
        $this->assertDatabaseCount('users', 1);
        $this->actingAs($admin)->get('https://schoolshare.example/admin')->assertRedirect(route('otp.verify.show'));
    }

    public function test_bad_database_password_is_redacted_and_does_not_begin_installation(): void
    {
        $original = File::get($this->root.'/app/.env');
        $payload = $this->payload();
        $payload['db_password'] = 'do-not-print-this-invalid-secret';

        $response = $this->authorized()->post('https://schoolshare.example/install', $payload);

        $response->assertSessionHasErrors(['db_database' => 'Cannot connect. Check the full cPanel database/user names, password, host, and privileges.'])
            ->assertSessionMissing('_old_input.db_password');
        $this->assertSame($original, File::get($this->root.'/app/.env'));
        $this->assertFileDoesNotExist($this->root.'/app/storage/app/installation-started.lock');
    }

    public function test_smtp_failure_preserves_the_admin_and_keeps_installation_locked(): void
    {
        Exceptions::fake();
        Mail::shouldReceive('purge')->once()->with('smtp');
        Mail::shouldReceive('to')->once()->with('admin@example.com')->andReturnSelf();
        Mail::shouldReceive('send')->once()->andThrow(new \RuntimeException('sensitive SMTP transport details'));

        $this->authorized()->post('https://schoolshare.example/install', $this->payload())->assertRedirect(route('login'))
            ->assertSessionHas('status', fn (string $status): bool => str_contains($status, 'email delivery failed'));

        $this->assertDatabaseCount('users', 1);
        $this->assertTrue((bool) User::sole()->is_admin);
        $this->assertDatabaseHas('email_otp_tokens', ['user_id' => User::sole()->id, 'delivery_status' => 'failed']);
        $this->assertFileExists($this->root.'/app/storage/app/installed.lock');
        $this->post('https://schoolshare.example/install', $this->payload())->assertNotFound();
        Exceptions::assertReported(MailDeliveryFailure::class);
    }

    public function test_failed_migration_preserves_recovery_markers_and_cannot_be_repeated(): void
    {
        Artisan::shouldReceive('call')->once()->with('migrate', ['--force' => true, '--no-interaction' => true])->andReturn(1);

        $this->authorized()->post('https://schoolshare.example/install', $this->payload())->assertSessionHasErrors('installation');

        $this->assertFileExists($this->root.'/app/storage/app/installation-started.lock');
        $this->assertFileDoesNotExist($this->root.'/app/storage/app/installed.lock');
        $this->assertSame([], Schema::getTables(Schema::getCurrentSchemaListing()));
        $this->authorized()->post('https://schoolshare.example/install', $this->payload())->assertStatus(409);
    }

    public function test_existing_database_view_and_public_storage_files_are_not_overwritten(): void
    {
        DB::statement('CREATE VIEW installer_existing_view AS SELECT 1 AS id');
        $this->authorized()->post('https://schoolshare.example/install', $this->payload())->assertSessionHasErrors('db_database');
        $this->assertCount(1, Schema::getViews(Schema::getCurrentSchemaListing()));
        Schema::dropAllViews();
        File::ensureDirectoryExists($this->root.'/public_html/storage');
        File::put($this->root.'/public_html/storage/preserve.txt', 'existing public file');

        $this->authorized()->post('https://schoolshare.example/install', $this->payload())->assertSessionHasErrors('requirements');

        $this->assertSame('existing public file', File::get($this->root.'/public_html/storage/preserve.txt'));
        $this->assertFileDoesNotExist($this->root.'/app/storage/app/installation-started.lock');
    }

    public function test_exposed_private_application_directory_blocks_installation(): void
    {
        config(['installation.public_path' => $this->root.'/app']);

        $this->authorized()->post('https://schoolshare.example/install', $this->payload())->assertSessionHasErrors('requirements');

        $this->assertSame([], Schema::getTables(Schema::getCurrentSchemaListing()));
        $this->assertFileDoesNotExist($this->root.'/app/storage/app/installation-started.lock');
    }

    public function test_existing_tables_are_preserved_and_wrong_engine_is_rejected(): void
    {
        $this->artisan('migrate', ['--force' => true])->assertSuccessful();
        $account = User::factory()->create();
        $original = File::get($this->root.'/app/.env');

        $this->authorized()->post('https://schoolshare.example/install', $this->payload())->assertSessionHasErrors('db_database');

        $this->assertModelExists($account);
        $this->assertSame($original, File::get($this->root.'/app/.env'));
        $payload = $this->payload();
        $payload['db_connection'] = $this->driver === 'mysql' ? 'mariadb' : 'mysql';
        $this->authorized()->post('https://schoolshare.example/install', $payload)->assertSessionHasErrors('db_connection');
        $this->assertFileDoesNotExist($this->root.'/app/storage/app/installation-started.lock');
    }

    #[TestWith(['marker'])]
    #[TestWith(['concurrent'])]
    public function test_second_or_interrupted_installation_cannot_create_tables_or_admins(string $condition): void
    {
        $lock = null;
        if ($condition === 'marker') {
            File::put($this->root.'/app/storage/app/installation-started.lock', 'started');
        } else {
            $lock = fopen($this->root.'/app/storage/app/installation-operation.lock', 'c');
            flock($lock, LOCK_EX);
        }
        try {
            $this->authorized()->post('https://schoolshare.example/install', $this->payload())->assertStatus(409);
            $this->assertSame([], Schema::getTables(Schema::getCurrentSchemaListing()));
        } finally {
            if ($lock !== null) {
                flock($lock, LOCK_UN);
                fclose($lock);
            }
        }
    }

    private function authorized(): static
    {
        return $this->withSession(['installation.authorization' => config('installation.token_hash'), 'installation.expires_at' => time() + 1800]);
    }

    /** @return array<string, string|int> */
    private function payload(): array
    {
        return [
            'site_name' => 'SchoolShare', 'site_url' => 'https://schoolshare.example',
            'db_connection' => $this->driver, 'db_host' => $this->connection['host'], 'db_port' => $this->connection['port'],
            'db_database' => 'schoolshare_testing', 'db_username' => $this->connection['username'], 'db_password' => $this->connection['password'],
            'mail_scheme' => 'smtps', 'mail_host' => 'smtp.example.com', 'mail_port' => 465,
            'mail_username' => 'sender@example.com', 'mail_password' => 'SMTP#password', 'mail_from_address' => 'sender@example.com',
            'admin_name' => 'Administrator', 'admin_email' => 'admin@example.com',
            'admin_password' => 'Admin#Password123', 'admin_password_confirmation' => 'Admin#Password123',
        ];
    }
}
