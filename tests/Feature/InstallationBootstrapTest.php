<?php

namespace Tests\Feature;

use App\InstallationBootstrap;
use App\InstallationEnvironment;
use Dotenv\Dotenv;
use Illuminate\Support\Env;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class InstallationBootstrapTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/schoolshare-bootstrap-'.Str::uuid();
        File::ensureDirectoryExists($this->root.'/storage/app', 0700);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_bootstrap_generates_private_unique_credentials_once_for_a_packaged_copy(): void
    {
        File::put($this->root.'/.cpanel-layout', 'packaged');

        InstallationBootstrap::prepare($this->root);

        $values = Dotenv::createArrayBacked($this->root)->load();
        $token = trim(File::get($this->root.'/setup-token.txt'));
        $this->assertSame(64, strlen($token));
        $this->assertSame(hash('sha256', $token), $values['INSTALL_TOKEN_HASH']);
        $this->assertSame(32, strlen(base64_decode(substr($values['APP_KEY'], 7), true)));
        $this->assertSame('false', $values['APP_DEBUG']);
        $this->assertSame('file', $values['SESSION_DRIVER']);
        $this->assertSame(0600, fileperms($this->root.'/.env') & 0777);
        $this->assertSame(0600, fileperms($this->root.'/setup-token.txt') & 0777);
        InstallationBootstrap::prepare($this->root);
        $this->assertSame($values, Dotenv::createArrayBacked($this->root)->load());
        $this->assertSame($token, trim(File::get($this->root.'/setup-token.txt')));
    }

    public function test_bootstrap_never_changes_an_existing_environment_or_enables_an_unpackaged_copy(): void
    {
        InstallationBootstrap::prepare($this->root);
        $this->assertFileDoesNotExist($this->root.'/.env');
        File::put($this->root.'/.cpanel-layout', 'packaged');
        File::put($this->root.'/.env', 'APP_KEY=existing-key');

        InstallationBootstrap::prepare($this->root);

        $this->assertSame('APP_KEY=existing-key', File::get($this->root.'/.env'));
        $this->assertFileDoesNotExist($this->root.'/setup-token.txt');
    }

    #[TestWith(['installed.lock'])]
    #[TestWith(['installation-started.lock'])]
    public function test_missing_environment_cannot_reopen_completed_or_interrupted_setup(string $marker): void
    {
        File::put($this->root.'/.cpanel-layout', 'packaged');
        File::put($this->root.'/storage/app/'.$marker, 'preserve');

        try {
            InstallationBootstrap::prepare($this->root);
            $this->fail('Setup must remain closed.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('cannot be reopened', $exception->getMessage());
            $this->assertFileDoesNotExist($this->root.'/.env');
            $this->assertFileDoesNotExist($this->root.'/setup-token.txt');
        }
    }

    #[TestWith(['p$a${APP_KEY}#"quote\\slash\' space'])]
    #[TestWith(['null'])]
    #[TestWith(['true'])]
    #[TestWith(['"quoted"'])]
    public function test_saved_passwords_round_trip_without_interpolation_or_type_conversion(string $password): void
    {
        (new InstallationEnvironment)->write($this->root.'/.env', ['INSTALL_TEST_PASSWORD' => $password]);

        Dotenv::createMutable($this->root)->load();

        try {
            $this->assertSame($password, Env::get('INSTALL_TEST_PASSWORD'));
        } finally {
            unset($_ENV['INSTALL_TEST_PASSWORD'], $_SERVER['INSTALL_TEST_PASSWORD']);
        }
    }

    public function test_newlines_cannot_inject_configuration_and_existing_file_is_preserved(): void
    {
        File::put($this->root.'/.env', 'preserve');

        try {
            (new InstallationEnvironment)->write($this->root.'/.env', ['APP_NAME' => "SchoolShare\nAPP_DEBUG=true"]);
            $this->fail('Injected configuration must be refused.');
        } catch (\RuntimeException) {
            $this->assertSame('preserve', File::get($this->root.'/.env'));
        }
    }
}
