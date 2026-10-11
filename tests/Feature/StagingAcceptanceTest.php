<?php

namespace Tests\Feature;

use App\Models\User;
use GuzzleHttp\Cookie\CookieJar;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Pool;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;
use ZipArchive;

#[Group('staging')]
class StagingAcceptanceTest extends TestCase
{
    private string $baseUrl;

    protected function setUp(): void
    {
        parent::setUp();

        if (getenv('STAGING_ACCEPTANCE') !== '1') {
            $this->markTestSkipped('Opt-in real HTTP/SMTP acceptance requires the isolated staging stack.');
        }

        $this->baseUrl = rtrim((string) getenv('STAGING_BASE_URL'), '/');
        $this->assertContains($this->baseUrl, ['http://app', 'http://app-mariadb', 'http://app-cpanel', 'http://127.0.0.1:18080', 'http://127.0.0.1:18081', 'http://127.0.0.1:18082']);
        $connection = config('database.default');
        $this->assertContains($connection, ['mysql', 'mariadb']);
        $this->assertSame('schoolshare_testing', DB::connection()->getDatabaseName());
        config(['database.connections.'.$connection.'.database' => 'schoolshare_staging']);
        config(['database.connections.'.$connection.'.url' => null]);
        DB::purge($connection);
    }

    public function test_apache_runtime_verifies_otp_delivered_over_smtp_and_consumes_the_verifier(): void
    {
        $cookies = new CookieJar;
        $email = 'stage-otp-'.Str::lower(Str::random(12)).'@schoolshare.example';
        $csrf = $this->csrf($this->client($cookies)->get($this->baseUrl.'/register'));

        $registered = $this->client($cookies)->asForm()->post($this->baseUrl.'/register', [
            '_token' => $csrf, 'name' => 'Staging OTP account', 'email' => $email,
            'password' => 'Staging-password-123!', 'password_confirmation' => 'Staging-password-123!',
        ]);

        $this->assertSame(302, $registered->status());
        $this->assertStringEndsWith('/otp/verify', $registered->header('Location'));
        $user = User::where('email', $email)->firstOrFail();
        $this->assertNull($user->email_verified_at);
        $this->assertStringEndsWith('/otp/verify', $this->client($cookies)->get($this->baseUrl.'/dashboard')->header('Location'));
        $mail = $this->capturedMail($email);
        $this->assertSame(1, preg_match('/\b([0-9]{3})\s+([0-9]{3})\b/', $mail['Text'], $matches));
        $this->assertSame(64, strlen(DB::table('email_otp_tokens')->where('user_id', $user->id)->value('otp_hash')));
        $csrf = $this->csrf($this->client($cookies)->get($this->baseUrl.'/otp/verify'));

        $verified = $this->client($cookies)->asForm()->post($this->baseUrl.'/otp/verify', ['_token' => $csrf, 'otp' => $matches[1].$matches[2]]);

        $this->assertSame(302, $verified->status());
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertSame(0, DB::table('email_otp_tokens')->where('user_id', $user->id)->count());
        $this->assertSame(200, $this->client($cookies)->get($this->baseUrl.'/dashboard')->status());
    }

    public function test_real_private_upload_edit_zip_and_checkpoint_restore_respect_owner_editor_and_viewer_permissions(): void
    {
        $owner = $this->account('owner');
        $editor = $this->account('editor');
        $viewer = $this->account('viewer');
        $outsider = $this->account('outsider');
        [$ownerCookies, $ownerCsrf] = $this->login($owner);
        $avatar = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mP8/x8AAusB9Wl6e8kAAAAASUVORK5CYII=');
        $avatarUploaded = $this->client($ownerCookies)->attach('avatar', $avatar, 'avatar.png', ['Content-Type' => 'image/png'])
            ->post($this->baseUrl.'/profile', ['_method' => 'PATCH', '_token' => $ownerCsrf, 'name' => $owner->name, 'email' => $owner->email]);
        $this->assertSame(302, $avatarUploaded->status());
        $this->assertNotNull($owner->fresh()->avatar_path);
        $this->assertSame($avatar, $this->client(new CookieJar)->get($this->baseUrl.'/storage/'.$owner->fresh()->avatar_path)->body());
        $created = $this->client($ownerCookies)->asForm()->post($this->baseUrl.'/projects', [
            '_token' => $ownerCsrf, 'name' => 'Staging recovery '.Str::random(8), 'visibility' => 'private',
        ]);
        $this->assertSame(302, $created->status());
        $project = $owner->projects()->firstOrFail();
        $prefix = '/projects/'.$project->slug;
        $this->assertSame(302, $this->client($ownerCookies)->asForm()->post($this->baseUrl.$prefix.'/folders', ['_token' => $ownerCsrf, 'name' => 'sources'])->status());
        $folder = $project->folders()->firstOrFail();

        $uploaded = $this->client($ownerCookies)->attach('files[]', 'draft', 'notes.txt', ['Content-Type' => 'text/plain'])
            ->post($this->baseUrl.$prefix.'/checkpoints', ['_token' => $ownerCsrf, 'title' => 'Original tree', 'folder_id' => (string) $folder->id]);

        $this->assertSame(302, $uploaded->status());
        $checkpoint = $project->checkpoints()->where('title', 'Original tree')->firstOrFail();
        $file = $project->files()->with('latestVersion')->firstOrFail();
        $this->assertSame(1, $checkpoint->fileSnapshots()->count());
        $this->assertSame(5, $owner->fresh()->storage_used_bytes);
        $downloadPath = $prefix.'/files/'.$file->id.'/download';
        $this->assertSame('draft', $this->client($ownerCookies)->get($this->baseUrl.$downloadPath)->body());
        $this->assertSame(302, $this->client(new CookieJar)->get($this->baseUrl.$downloadPath)->status());
        [$outsiderCookies] = $this->login($outsider);
        $this->assertSame(403, $this->client($outsiderCookies)->get($this->baseUrl.$downloadPath)->status());
        $this->assertSame(404, $this->client(new CookieJar)->get($this->baseUrl.'/storage/'.$file->latestVersion->storage_path)->status());
        foreach ([$editor, $viewer] as $collaborator) {
            $this->assertSame(302, $this->client($ownerCookies)->asForm()->post($this->baseUrl.$prefix.'/collaborators', [
                '_token' => $ownerCsrf, 'username' => $collaborator->username,
                'role' => $collaborator->id === $editor->id ? 'editor' : 'viewer',
            ])->status());
            $this->assertStringContainsString($project->name, $this->capturedMail($collaborator->email)['Text']);
        }
        [$viewerCookies, $viewerCsrf] = $this->login($viewer);
        $this->assertSame('draft', $this->client($viewerCookies)->get($this->baseUrl.$downloadPath)->body());
        $this->assertSame(403, $this->client($viewerCookies)->asForm()->put($this->baseUrl.$prefix.'/files/'.$file->id, ['_token' => $viewerCsrf, 'content' => 'forbidden'])->status());
        [$editorCookies, $editorCsrf] = $this->login($editor);
        $edited = $this->client($editorCookies)->asForm()->put($this->baseUrl.$prefix.'/files/'.$file->id, ['_token' => $editorCsrf, 'content' => 'edited']);
        $this->assertSame(200, $edited->status());
        $this->assertSame('edited', $this->client($ownerCookies)->get($this->baseUrl.$downloadPath)->body());
        $this->assertSame(2, $file->fresh()->version_count);
        $this->assertSame(6, $editor->fresh()->storage_used_bytes);
        $zipResponse = $this->client($ownerCookies)->get($this->baseUrl.$prefix.'/download-zip');
        $this->assertSame(200, $zipResponse->status());
        $zipPath = tempnam(sys_get_temp_dir(), 'staging-zip-');
        try {
            file_put_contents($zipPath, $zipResponse->body());
            $zip = new ZipArchive;
            $this->assertTrue($zip->open($zipPath));
            $this->assertSame('edited', $zip->getFromName('sources/notes.txt'));
            $zip->close();
        } finally {
            unlink($zipPath);
        }
        $this->assertSame(302, $this->client($ownerCookies)->asForm()->put($this->baseUrl.$prefix.'/files/'.$file->id, ['_token' => $ownerCsrf, 'original_name' => 'renamed.txt', 'folder_id' => ''])->status());
        $this->assertSame(302, $this->client($ownerCookies)->asForm()->delete($this->baseUrl.$prefix.'/folders/'.$folder->id, ['_token' => $ownerCsrf])->status());
        $this->assertSame(302, $this->client($ownerCookies)->asForm()->delete($this->baseUrl.$prefix.'/files/'.$file->id, ['_token' => $ownerCsrf])->status());

        $restored = $this->client($ownerCookies)->asForm()->post($this->baseUrl.$prefix.'/checkpoints/'.$checkpoint->id.'/restore', ['_token' => $ownerCsrf]);

        $this->assertSame(302, $restored->status());
        $file->refresh()->load('folder');
        $this->assertSame('notes.txt', $file->original_name);
        $this->assertSame('sources', $file->folder->name);
        $this->assertSame('draft', $this->client($ownerCookies)->get($this->baseUrl.$downloadPath)->body());
    }

    public function test_simultaneous_upgrade_submissions_from_independent_sessions_create_one_pending_row(): void
    {
        $user = $this->account('upgrade');
        [$firstCookies, $firstCsrf] = $this->login($user);
        [$secondCookies, $secondCsrf] = $this->login($user);

        $responses = Http::pool(fn (Pool $pool): array => [
            $pool->as('first')->withOptions(['cookies' => $firstCookies, 'allow_redirects' => false])->timeout(30)->asForm()
                ->post($this->baseUrl.'/upgrades', ['_token' => $firstCsrf, 'plan' => 'pro', 'billing_cycle' => 'monthly']),
            $pool->as('second')->withOptions(['cookies' => $secondCookies, 'allow_redirects' => false])->timeout(30)->asForm()
                ->post($this->baseUrl.'/upgrades', ['_token' => $secondCsrf, 'plan' => 'pro', 'billing_cycle' => 'yearly']),
        ]);

        $this->assertSame(302, $responses['first']->status());
        $this->assertSame(302, $responses['second']->status());
        $this->assertSame(1, $user->upgradeRequests()->where('requested_plan', 'pro')->where('status', 'pending')->count());
        $this->assertSame('sent', $user->upgradeRequests()->firstOrFail()->mail_delivery_status);
        $this->assertNotEmpty($this->capturedMail($user->email)['Text']);
    }

    public function test_real_smtp_rejection_preserves_changes_and_successful_retries_deliver_mail(): void
    {
        $user = $this->account('mailfailure');
        $collaborator = $this->account('invited');
        $project = $user->projects()->create(['name' => 'SMTP recovery '.Str::random(8), 'visibility' => 'private']);
        [$cookies, $csrf] = $this->login($user);
        $invitationUrl = $this->baseUrl.'/projects/'.$project->slug.'/collaborators';
        $newEmail = 'stage-changed-'.Str::lower(Str::random(12)).'@schoolshare.example';
        $chaosUrl = $this->mailpitUrl().'/api/v1/chaos';
        Http::timeout(10)->put($chaosUrl, ['Sender' => ['ErrorCode' => 451, 'Probability' => 100]])->throw();

        try {
            $this->assertSame(302, $this->client($cookies)->asForm()->post($invitationUrl, ['_token' => $csrf, 'username' => $collaborator->username, 'role' => 'viewer'])->status());
            $this->assertSame(1, $project->collaborators()->whereKey($collaborator->id)->count());
            $this->assertSame(302, $this->client($cookies)->asForm()->post($this->baseUrl.'/upgrades', ['_token' => $csrf, 'plan' => 'pro', 'billing_cycle' => 'monthly'])->status());
            $request = $user->upgradeRequests()->firstOrFail();
            $this->assertSame('failed', $request->mail_delivery_status);
            $this->assertStringNotContainsString('451', $request->mail_delivery_error);
            $this->assertSame(302, $this->client($cookies)->asForm()->patch($this->baseUrl.'/profile', ['_token' => $csrf, 'name' => $user->name, 'email' => $newEmail])->status());
            $this->assertSame($newEmail, $user->fresh()->email);
            $this->assertNull($user->fresh()->email_verified_at);
            $this->assertSame('failed', DB::table('email_otp_tokens')->where('user_id', $user->id)->value('delivery_status'));
        } finally {
            Http::timeout(10)->withBody('{}', 'application/json')->put($chaosUrl)->throw();
        }

        $csrf = $this->csrf($this->client($cookies)->get($this->baseUrl.'/otp/verify'));
        $this->assertSame(302, $this->client($cookies)->asForm()->post($this->baseUrl.'/otp/resend', ['_token' => $csrf])->status());
        $mail = $this->capturedMail($newEmail);
        $this->assertSame(1, preg_match('/\b([0-9]{3})\s+([0-9]{3})\b/', $mail['Text'], $matches));
        $this->assertSame(302, $this->client($cookies)->asForm()->post($this->baseUrl.'/otp/verify', ['_token' => $csrf, 'otp' => $matches[1].$matches[2]])->status());
        $this->assertNotNull($user->fresh()->email_verified_at);
        $this->assertSame(302, $this->client($cookies)->asForm()->post($invitationUrl, ['_token' => $csrf, 'username' => $collaborator->username, 'role' => 'viewer'])->status());
        $this->assertStringContainsString($project->name, $this->capturedMail($collaborator->email)['Text']);
        $this->assertSame(302, $this->client($cookies)->asForm()->post($this->baseUrl.'/upgrades', ['_token' => $csrf, 'plan' => 'pro', 'billing_cycle' => 'yearly'])->status());
        $this->assertSame(1, $user->upgradeRequests()->count());
        $request->refresh();
        $this->assertSame('sent', $request->mail_delivery_status);
        $this->assertSame('monthly', $request->billing_cycle);
        $this->assertNull($request->mail_delivery_error);
        $this->assertNotEmpty($this->capturedMail($newEmail)['Text']);
    }

    private function client(CookieJar $cookies): PendingRequest
    {
        return Http::withOptions(['cookies' => $cookies, 'allow_redirects' => false])->timeout(30);
    }

    private function csrf(Response $response): string
    {
        $this->assertSame(200, $response->status());
        $matched = preg_match('/name="_token"[^>]*value="([^"]+)"|name="csrf-token"[^>]*content="([^"]+)"/', $response->body(), $matches);
        $this->assertSame(1, $matched, 'The real response must contain a CSRF token.');

        return html_entity_decode($matches[1] !== '' ? $matches[1] : $matches[2]);
    }

    private function account(string $role): User
    {
        $suffix = Str::lower(Str::random(12));

        return User::factory()->create(['name' => 'Staging '.$role, 'email' => 'stage-'.$role.'-'.$suffix.'@schoolshare.example', 'username' => 'stage_'.$role.'_'.$suffix]);
    }

    /** @return array{CookieJar, string} */
    private function login(User $user): array
    {
        $cookies = new CookieJar;
        $csrf = $this->csrf($this->client($cookies)->get($this->baseUrl.'/login'));
        $response = $this->client($cookies)->asForm()->post($this->baseUrl.'/login', ['_token' => $csrf, 'email' => $user->email, 'password' => 'password']);
        $this->assertSame(302, $response->status());

        return [$cookies, $this->csrf($this->client($cookies)->get($this->baseUrl.'/dashboard'))];
    }

    /** @return array<string, mixed> */
    private function capturedMail(string $email): array
    {
        $mailpitUrl = $this->mailpitUrl();
        $messages = Http::timeout(10)->get($mailpitUrl.'/api/v1/messages')->throw()->json('messages');
        $message = collect($messages)->first(fn (array $message): bool => collect($message['To'])->contains('Address', $email));
        $this->assertNotNull($message, 'The isolated SMTP server must receive a message for the test account.');

        return Http::timeout(10)->get($mailpitUrl.'/api/v1/message/'.$message['ID'])->throw()->json();
    }

    private function mailpitUrl(): string
    {
        $url = (string) (getenv('STAGING_MAILPIT_URL') ?: 'http://mailpit:8025');
        $this->assertContains($url, ['http://mailpit:8025', 'http://127.0.0.1:18025']);

        return $url;
    }
}
