<?php

namespace Tests\Feature;

use App\CpanelReleaseBuilder;
use Dotenv\Dotenv;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use ZipArchive;

#[Group('package')]
class CpanelPackageAcceptanceTest extends TestCase
{
    public function test_extracted_package_installs_over_https_and_delivers_admin_otp_over_authenticated_encrypted_smtp(): void
    {
        if (getenv('STAGING_PACKAGE') !== '1') {
            $this->markTestSkipped('Opt-in package acceptance starts isolated Apache/TLS/SMTP containers.');
        }
        $engine = getenv('STAGING_PACKAGE_ENGINE') ?: 'mysql';
        $this->assertContains($engine, ['mysql', 'mariadb', 'mariadb-cpanel']);
        $driver = $engine === 'mysql' ? 'mysql' : 'mariadb';
        $publicDirectory = getenv('STAGING_PACKAGE_PUBLIC_DIRECTORY') ?: 'public_html';
        $mailScheme = getenv('STAGING_PACKAGE_MAIL_SCHEME') ?: 'smtp';
        $this->assertContains($mailScheme, ['smtp', 'smtps']);
        $mailPort = $mailScheme === 'smtps' ? 465 : 1025;
        $releaseArchive = getenv('STAGING_PACKAGE_ARCHIVE');
        $suffix = bin2hex(random_bytes(6));
        $root = sys_get_temp_dir().'/schoolshare-cpanel-'.$suffix;
        $web = 'schoolshare-installer-web-'.$suffix;
        $mail = 'schoolshare-installer-mail-'.$suffix;
        $database = 'schoolshare_install_'.$suffix.'_testing';
        $databaseCreated = false;
        $webStarted = false;
        $mailStarted = false;
        File::ensureDirectoryExists($root, 0700);
        $secrets = Dotenv::createArrayBacked(base_path(), '.env.staging')->load();

        try {
            if ($releaseArchive !== false && $releaseArchive !== '') {
                $this->assertTrue(File::copy($releaseArchive, $root.'/release.zip'));
            } else {
                $this->runCommand(['docker', 'cp', 'schoolshare-staging-app-1:/var/www/html/vendor', $root.'/vendor']);
                app(CpanelReleaseBuilder::class)->build(base_path(), $root.'/vendor', $root.'/release.zip', $publicDirectory);
            }
            $archive = new ZipArchive;
            $this->assertTrue($archive->open($root.'/release.zip'));
            $this->assertTrue($archive->extractTo($root.'/home'));
            $archive->close();
            if ($publicDirectory !== 'public_html') {
                $this->assertDirectoryDoesNotExist($root.'/home/public_html');
                File::ensureDirectoryExists($root.'/home/public_html');
                File::put($root.'/home/public_html/existing-site.txt', 'Main website must be preserved.');
            }
            $this->runCommand(['openssl', 'req', '-x509', '-newkey', 'rsa:2048', '-nodes', '-sha256', '-days', '1',
                '-subj', '/CN=127.0.0.1', '-addext', 'subjectAltName=IP:127.0.0.1,DNS:installer-mail',
                '-keyout', $root.'/key.pem', '-out', $root.'/cert.pem']);
            File::put($root.'/apache.conf', '<VirtualHost *:443>
DocumentRoot /cpanel/'.$publicDirectory.'
SSLEngine on
SSLCertificateFile /fixture/cert.pem
SSLCertificateKeyFile /fixture/key.pem
<Directory /cpanel/'.$publicDirectory.'>
AllowOverride All
Require all granted
Options -Indexes
</Directory>
</VirtualHost>');
            $this->databaseCommand($engine, 'CREATE DATABASE `'.$database.'`; GRANT ALL ON `'.$database.'`.* TO schoolshare;');
            $databaseCreated = true;
            $mailImage = 'axllent/mailpit@sha256:7f33095f80e901f6ad08028f06ca284aa58fe84942be5496008d041d3b9f4d4d';
            $this->runCommand(['docker', 'run', '-d', '--name', $mail, '--network', 'schoolshare-staging_default', '--network-alias', 'installer-mail',
                '--mount', 'type=bind,source='.$root.',target=/fixture,readonly', '-p', '127.0.0.1:18026:8025',
                '-e', 'MP_SMTP_TLS_CERT=/fixture/cert.pem', '-e', 'MP_SMTP_TLS_KEY=/fixture/key.pem',
                '-e', 'MP_SMTP_BIND_ADDR=0.0.0.0:'.$mailPort,
                '-e', $mailScheme === 'smtps' ? 'MP_SMTP_REQUIRE_TLS=true' : 'MP_SMTP_REQUIRE_STARTTLS=true', '-e', 'MP_SMTP_AUTH=setup-user:setup-password', $mailImage]);
            $mailStarted = true;
            $this->runCommand(['docker', 'run', '-d', '--name', $web, '--network', 'schoolshare-staging_default',
                '--mount', 'type=bind,source='.$root.',target=/fixture', '--mount', 'type=bind,source='.$root.'/home,target=/cpanel',
                '-p', '127.0.0.1:18443:443', '--entrypoint', 'sh', 'schoolshare-staging-app:local', '-c',
                'a2enmod ssl >/dev/null && cp /fixture/apache.conf /etc/apache2/sites-available/000-default.conf && cp /fixture/cert.pem /usr/local/share/ca-certificates/installer.crt && update-ca-certificates >/dev/null && chown -R www-data:www-data /cpanel && exec apache2-foreground']);
            $webStarted = true;
            $cookies = new CookieJar;
            $client = Http::withOptions(['verify' => $root.'/cert.pem', 'cookies' => $cookies, 'allow_redirects' => false])->timeout(20);
            $page = retry(30, fn (): Response => $this->client($client)->get('https://127.0.0.1:18443/install')->throw(), 250);
            $this->assertSame(200, $page->status());
            $this->assertStringContainsString('Unlock private setup', $page->body());
            $this->assertSame(419, $this->client($client)->asForm()->post('https://127.0.0.1:18443/install/unlock', [])->status());
            $token = trim($this->runCommand(['docker', 'exec', $web, 'php', '-r', 'echo file_get_contents("/cpanel/schoolshare/setup-token.txt");']));
            $this->assertSame(64, strlen($token));
            $unlocked = $this->client($client)->asForm()->post('https://127.0.0.1:18443/install/unlock', ['_token' => $this->csrf($page), 'setup_token' => $token]);
            $this->assertSame(302, $unlocked->status());
            $page = $this->client($client)->get('https://127.0.0.1:18443/install');
            $this->assertStringContainsString('name="db_password"', $page->body());
            $this->assertStringNotContainsString('Fix required', $page->body());
            $installed = $this->client($client)->asForm()->post('https://127.0.0.1:18443/install', [
                '_token' => $this->csrf($page), 'site_name' => 'SchoolShare', 'site_url' => 'https://127.0.0.1:18443',
                'db_connection' => $driver, 'db_host' => $engine, 'db_port' => 3306, 'db_database' => $database,
                'db_username' => 'schoolshare', 'db_password' => $secrets['STAGING_DB_PASSWORD'],
                'mail_scheme' => $mailScheme, 'mail_host' => 'installer-mail', 'mail_port' => $mailPort,
                'mail_username' => 'setup-user', 'mail_password' => 'setup-password', 'mail_from_address' => 'staging@schoolshare.example',
                'admin_name' => 'Administrator', 'admin_email' => 'admin@schoolshare.example',
                'admin_password' => 'Admin#Password123', 'admin_password_confirmation' => 'Admin#Password123',
            ]);
            $this->assertSame(302, $installed->status());
            $this->assertStringEndsWith('/login', $installed->header('Location'));
            $login = $this->client($client)->get('https://127.0.0.1:18443/login');
            $this->assertStringContainsString('verification code sent', $login->body());
            $this->assertSame(404, $this->client($client)->get('https://127.0.0.1:18443/install')->status());
            foreach (['/.env', '/setup-token.txt', '/schoolshare/.env', '/vendor/autoload.php'] as $path) {
                $this->assertSame(404, $this->client($client)->get('https://127.0.0.1:18443'.$path)->status());
            }
            $this->assertSame(302, $this->client($client)->asForm()->post('https://127.0.0.1:18443/login', [
                '_token' => $this->csrf($login), 'email' => 'admin@schoolshare.example', 'password' => 'Admin#Password123',
            ])->status());
            $this->assertStringEndsWith('/otp/verify', $this->client($client)->get('https://127.0.0.1:18443/admin')->header('Location'));
            $messages = Http::timeout(10)->get('http://127.0.0.1:18026/api/v1/messages')->throw()->json('messages');
            $this->assertCount(1, $messages);
            $message = Http::timeout(10)->get('http://127.0.0.1:18026/api/v1/message/'.$messages[0]['ID'])->throw()->json();
            $this->assertSame(1, preg_match('/\b([0-9]{3})\s+([0-9]{3})\b/', strip_tags($message['HTML']), $matches));
            $verification = $this->client($client)->get('https://127.0.0.1:18443/otp/verify');
            $this->assertSame(302, $this->client($client)->asForm()->post('https://127.0.0.1:18443/otp/verify', [
                '_token' => $this->csrf($verification), 'otp' => $matches[1].$matches[2],
            ])->status());
            $this->assertSame(200, $this->client($client)->get('https://127.0.0.1:18443/admin')->status());
            $this->assertSame(200, $this->client($client)->get('https://127.0.0.1:18443/ready')->status());
            $this->assertStringContainsString('/cpanel/'.$publicDirectory, $this->runCommand(['docker', 'exec', '--user', 'www-data', '--workdir', '/cpanel/schoolshare', $web,
                'php', 'artisan', 'config:show', 'installation.public_path', '--no-interaction']));
            if ($publicDirectory !== 'public_html') {
                $this->assertSame('Main website must be preserved.', File::get($root.'/home/public_html/existing-site.txt'));
            }
            $health = $this->runCommand(['docker', 'exec', '--user', 'www-data', '--workdir', '/cpanel/schoolshare', $web, 'php', 'artisan', 'schoolshare:health', '--no-interaction']);
            $this->assertStringContainsString('checks passed', $health);
            $cached = $this->runCommand(['docker', 'exec', '--user', 'www-data', '--workdir', '/cpanel/schoolshare', $web, 'php', 'artisan', 'optimize', '--no-interaction']);
            $this->assertStringContainsString('Caching', $cached);
            $this->assertSame(404, $this->client($client)->get('https://127.0.0.1:18443/install')->status());
            $this->assertSame(200, $this->client($client)->get('https://127.0.0.1:18443/ready')->status());
        } finally {
            if ($webStarted) {
                $this->runCommand(['docker', 'exec', $web, 'chown', '-R', getmyuid().':'.getmygid(), '/cpanel']);
                $this->runCommand(['docker', 'rm', '-f', $web]);
            }
            if ($mailStarted) {
                $this->runCommand(['docker', 'rm', '-f', $mail]);
            }
            if ($databaseCreated) {
                $this->databaseCommand($engine, 'DROP DATABASE `'.$database.'`;');
            }
            File::deleteDirectory($root);
        }
    }

    private function client(PendingRequest $client): PendingRequest
    {
        return clone $client;
    }

    private function csrf(Response $response): string
    {
        $this->assertSame(1, preg_match('/name="_token"\s+value="([^"]+)"/', $response->body(), $matches));

        return $matches[1];
    }

    private function databaseCommand(string $engine, string $sql): void
    {
        $password = $engine === 'mysql' ? 'MYSQL_ROOT_PASSWORD' : 'MARIADB_ROOT_PASSWORD';
        $binary = $engine === 'mysql' ? 'mysql' : 'mariadb';
        $this->runCommand(['docker', 'compose', '--env-file', '.env.staging', '-f', 'compose.staging.yaml', 'exec', '-T', $engine,
            'sh', '-c', 'MYSQL_PWD="$'.$password.'" '.$binary.' -uroot -e "$1"', 'sh', $sql]);
    }

    /** @param list<string> $command */
    private function runCommand(array $command): string
    {
        $process = new Process($command, base_path());
        $process->setTimeout(120)->run();
        $this->assertSame(0, $process->getExitCode(), 'Isolated package test command failed: '.$command[0].' '.$command[1].' '.substr($process->getErrorOutput(), 0, 1000));

        return $process->getOutput();
    }
}
