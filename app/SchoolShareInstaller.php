<?php

namespace App;

use App\Models\User;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class SchoolShareInstaller
{
    public function __construct(private InstallationDatabase $database, private InstallationEnvironment $environment, private EmailOtpService $otp) {}

    public function root(): string
    {
        return (string) config('installation.root');
    }

    public function locked(): bool
    {
        return is_file($this->root().'/storage/app/installed.lock');
    }

    public function started(): bool
    {
        return is_file($this->root().'/storage/app/installation-started.lock');
    }

    /** @return array<string, bool> */
    public function requirements(): array
    {
        $root = $this->root();
        $public = (string) config('installation.public_path');
        $checks = ['PHP 8.5' => PHP_VERSION_ID >= 80500 && PHP_VERSION_ID < 80600];
        foreach (['intl', 'pdo_mysql', 'zip', 'fileinfo', 'mbstring', 'openssl'] as $extension) {
            $checks[$extension] = extension_loaded($extension);
        }
        $checks['Private application directory outside the web root'] = realpath($root) !== false && realpath($public) !== false
            && ! str_starts_with(realpath($root).'/', realpath($public).'/');
        foreach (['', '/storage/app', '/storage/app/private', '/storage/app/public', '/storage/framework/cache/data', '/storage/framework/sessions', '/storage/framework/views', '/storage/logs', '/bootstrap/cache'] as $directory) {
            $checks['Writable '.($directory === '' ? 'private configuration directory' : ltrim($directory, '/'))] = is_dir($root.$directory) && is_writable($root.$directory);
        }
        $checks['Public directory writable for avatar storage link'] = is_dir($public) && is_writable($public);
        $checks['Symbolic links available'] = function_exists('symlink');

        return $checks;
    }

    /** @param array<string, mixed> $settings */
    public function install(array $settings): bool
    {
        abort_unless(config('installation.enabled') && ! $this->locked(), 404);
        $lock = fopen($this->root().'/storage/app/installation-operation.lock', 'c');
        abort_if($lock === false, 503, 'Cannot lock the private installation directory.');
        try {
            abort_unless(flock($lock, LOCK_EX | LOCK_NB), 409, 'Installation is already running.');
            abort_if($this->started() || $this->locked(), 409, 'Installation already started. Do not retry against a partially installed database.');
            if (in_array(false, $this->requirements(), true)) {
                throw ValidationException::withMessages(['requirements' => 'Fix all failed hosting requirements before installing.']);
            }
            $this->database->connect($settings);
            $this->database->requireEmptyDatabase();
            $public = (string) config('installation.public_path');
            $link = $public.'/storage';
            $target = $this->root().'/storage/app/public';
            if ((file_exists($link) || is_link($link)) && (! is_link($link) || realpath($link) !== realpath($target))) {
                throw ValidationException::withMessages(['requirements' => 'The public storage path is already occupied. Existing files will not be replaced.']);
            }
            if (! is_link($link) && ! @symlink($target, $link)) {
                throw ValidationException::withMessages(['requirements' => 'The host could not create the public avatar storage link. Ask the host to allow it.']);
            }
            $values = array_replace($this->environment->defaults((string) config('app.key'), (string) config('installation.token_hash')), [
                'APP_NAME' => $settings['site_name'], 'APP_URL' => rtrim($settings['site_url'], '/'),
                'DB_CONNECTION' => $settings['db_connection'], 'DB_HOST' => $settings['db_host'],
                'DB_PORT' => $settings['db_port'], 'DB_DATABASE' => $settings['db_database'],
                'DB_USERNAME' => $settings['db_username'], 'DB_PASSWORD' => $settings['db_password'],
                'MAIL_SCHEME' => $settings['mail_scheme'], 'MAIL_HOST' => $settings['mail_host'],
                'MAIL_PORT' => $settings['mail_port'], 'MAIL_USERNAME' => $settings['mail_username'] ?? '',
                'MAIL_PASSWORD' => $settings['mail_password'] ?? '', 'MAIL_FROM_ADDRESS' => $settings['mail_from_address'],
                'MAIL_FROM_NAME' => $settings['site_name'], 'MAIL_TIMEOUT' => 15, 'MAIL_REQUIRE_TLS' => true,
                'SCHOOLSHARE_ADMIN_EMAIL' => $settings['admin_email'],
            ]);
            $this->environment->writePrivateFile($this->root().'/storage/app/installation-started.lock', "Installation started. Preserve this marker if interrupted.\n");
            try {
                $this->environment->write($this->root().'/.env', $values);
                if (Artisan::call('migrate', ['--force' => true, '--no-interaction' => true]) !== 0) {
                    throw new \RuntimeException('Migration failed.');
                }
                $admin = DB::transaction(function () use ($settings): User {
                    $user = new User;
                    $user->name = $settings['admin_name'];
                    $user->email = $settings['admin_email'];
                    $user->password = $settings['admin_password'];
                    $user->is_admin = true;
                    $user->save();

                    return $user;
                });
                $this->environment->writePrivateFile($this->root().'/storage/app/installed.lock', "SchoolShare installed. Never delete this marker to reinstall.\n");
                $values['INSTALL_ENABLED'] = false;
                $values['INSTALL_TOKEN_HASH'] = '';
                $this->environment->write($this->root().'/.env', $values);
                if (is_file($this->root().'/setup-token.txt')) {
                    unlink($this->root().'/setup-token.txt');
                }
                config([
                    'app.name' => $settings['site_name'], 'app.url' => rtrim($settings['site_url'], '/'),
                    'mail.default' => 'smtp', 'mail.mailers.smtp.url' => null,
                    'mail.mailers.smtp.scheme' => $settings['mail_scheme'], 'mail.mailers.smtp.host' => $settings['mail_host'],
                    'mail.mailers.smtp.port' => $settings['mail_port'], 'mail.mailers.smtp.username' => $settings['mail_username'] ?? null,
                    'mail.mailers.smtp.password' => $settings['mail_password'] ?? null, 'mail.mailers.smtp.timeout' => 15, 'mail.mailers.smtp.require_tls' => true,
                    'mail.from.address' => $settings['mail_from_address'], 'mail.from.name' => $settings['site_name'],
                ]);
                Mail::purge('smtp');

                return $this->otp->send($admin);
            } catch (Throwable $exception) {
                Log::error('Installation stopped; operator recovery required.', ['exception_class' => $exception::class]);
                if ($this->locked()) {
                    return false;
                }
                throw ValidationException::withMessages(['installation' => 'Installation stopped. Keep the private markers and configuration. An operator must inspect or restore this dedicated database before proceeding; do not delete tables or rerun setup.']);
            }
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
