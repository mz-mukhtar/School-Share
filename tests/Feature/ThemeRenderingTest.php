<?php

namespace Tests\Feature;

use App\Models\Activity;
use App\Models\Checkpoint;
use App\Models\FileVersion;
use App\Models\Project;
use App\Models\ProjectFile;
use App\Models\UpgradeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class ThemeRenderingTest extends TestCase
{
    use RefreshDatabase;

    #[TestWith(['/', 'home'])]
    #[TestWith(['/terms', 'terms'])]
    #[TestWith(['/login', 'login'])]
    #[TestWith(['/register', 'register'])]
    #[TestWith(['/forgot-password', 'forgot-password'])]
    #[TestWith(['/reset-password/fixture-token?email=student@example.test', 'reset-password'])]
    public function test_public_layouts_load_the_shared_theme_before_content(string $path, string $name): void
    {
        $this->assertTheme($this->get($path), $name);
        if (in_array($name, ['forgot-password', 'reset-password'], true)) {
            $this->get($path)->assertSee('name="email"', false)->assertSee('name="_token"', false);
        }
    }

    public function test_authenticated_empty_and_populated_pages_use_the_shared_theme(): void
    {
        Storage::fake('local');
        $user = User::factory()->create(['name' => 'Theme Student', 'username' => 'theme-student']);
        $this->actingAs($user);
        foreach (['dashboard', 'explore', 'projects', 'feed', 'notifications'] as $path) {
            $this->assertTheme($this->get('/'.$path), $path.'-empty');
        }

        $project = Project::factory()->create([
            'user_id' => $user->id,
            'name' => 'Science notes',
            'description' => 'Research, class notes, and drafts for our science project.',
            'visibility' => 'public',
        ]);
        Project::factory()->count(12)->create(['user_id' => $user->id, 'visibility' => 'public']);
        User::factory()->count(12)->sequence(fn ($sequence) => ['username' => 'classmate-'.$sequence->index])->create();
        Activity::log('project_created', $user, $project);
        $checkpoint = Checkpoint::factory()->create([
            'user_id' => $user->id,
            'project_id' => $project->id,
            'title' => 'First draft',
            'message' => 'Added introduction and class notes.',
        ]);
        $file = ProjectFile::factory()->create(['project_id' => $project->id, 'original_name' => 'README.md', 'version_count' => 2]);
        $versions = [];
        foreach (["# Class notes\n\nFirst draft.", "# Class notes\n\nRevised draft."] as $index => $content) {
            $version = FileVersion::factory()->create([
                'project_file_id' => $file->id,
                'checkpoint_id' => $checkpoint->id,
                'version_number' => $index + 1,
                'size_bytes' => strlen($content),
            ]);
            Storage::disk('local')->put($version->storage_path, $content);
            $versions[] = $version;
        }
        $file->update(['latest_version_id' => $versions[1]->id]);
        $user->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => 'ThemeNotificationFixture',
            'data' => ['title' => 'New project activity', 'message' => 'A classmate shared notes with you.'],
        ]);

        foreach (['dashboard', 'explore', 'projects', 'feed', 'notifications', 'profile', 'pricing', 'about', 'guide', 'projects/create'] as $path) {
            $this->assertTheme($this->get('/'.$path), str_replace('/', '-', $path).'-populated');
        }
        $this->assertTheme($this->get('/explore?type=users'), 'explore-users');
        $this->assertTheme($this->get('/explore?page=2'), 'explore-pagination');
        $this->assertTheme($this->get('/u/'.$user->username), 'public-profile');
        $this->assertTheme($this->get('/confirm-password'), 'confirm-password');
        $this->assertTheme($this->get('/upgrades/pro'), 'upgrade-pro');
        $this->assertTheme($this->get('/upgrades/custom'), 'upgrade-custom');
        foreach (['projects.show', 'projects.edit', 'projects.checkpoints.index', 'projects.checkpoints.create'] as $route) {
            $this->assertTheme($this->get(route($route, $project)), str_replace('.', '-', $route));
        }
        $this->assertTheme($this->get(route('projects.checkpoints.show', [$project, $checkpoint])), 'checkpoint-show');
        $this->assertTheme($this->get(route('projects.files.show', [$project, $file])), 'file-preview');
        $this->assertTheme($this->get(route('projects.files.diff', [$project, $file, 'from' => $versions[0]->id, 'to' => $versions[1]->id])), 'file-diff');
        $pdf = ProjectFile::factory()->create(['project_id' => $project->id, 'original_name' => 'notes.pdf', 'mime_type' => 'application/pdf', 'version_count' => 1]);
        $pdfVersion = FileVersion::factory()->create(['project_file_id' => $pdf->id, 'checkpoint_id' => $checkpoint->id, 'mime_type' => 'application/pdf']);
        $pdf->update(['latest_version_id' => $pdfVersion->id]);
        $this->assertTheme($this->get(route('projects.files.show', [$project, $pdf])), 'pdf-preview');
    }

    public function test_admin_pages_use_the_shared_theme_and_keep_mobile_navigation(): void
    {
        $admin = User::factory()->create(['name' => 'Theme Administrator', 'username' => 'theme-admin', 'is_admin' => true]);
        $this->actingAs($admin);
        UpgradeRequest::create(['user_id' => $admin->id, 'requested_plan' => 'pro', 'billing_cycle' => 'monthly', 'status' => 'pending']);
        foreach (['admin', 'admin/users', 'admin/upgrades'] as $path) {
            $response = $this->get('/'.$path);
            $this->assertTheme($response, str_replace('/', '-', $path));
            $response->assertSee('ss-admin-mobile-nav', false);
        }
        $this->assertTheme($this->get(route('admin.users.show', $admin)), 'admin-user-show');
    }

    public function test_otp_and_validation_feedback_keep_the_theme_controls(): void
    {
        $user = User::factory()->unverified()->create(['username' => 'theme-unverified']);
        $this->actingAs($user)->withSession(['warning' => 'Email delivery failed. Please try again.']);
        $this->assertTheme($this->get('/otp/verify'), 'otp-warning');
        $this->app['auth']->forgetGuards();
        $errors = (new ViewErrorBag)->put('default', new MessageBag(['email' => 'Please enter a valid email address.']));
        $this->withSession(['errors' => $errors, 'status' => 'Your password has been reset.']);
        $this->assertTheme($this->get('/login'), 'login-feedback');
    }

    public function test_installer_uses_the_shared_theme_without_changing_installation_state(): void
    {
        $directory = sys_get_temp_dir().'/schoolshare-theme-install-'.Str::uuid();
        File::ensureDirectoryExists($directory.'/storage/app', 0700);
        $hash = hash('sha256', Str::random(64));
        config(['installation.enabled' => true, 'installation.token_hash' => $hash, 'installation.root' => $directory]);
        try {
            $this->assertTheme($this->get('https://localhost/install'), 'install-locked');
            $this->withSession(['installation.authorization' => $hash, 'installation.expires_at' => time() + 600]);
            $this->assertTheme($this->get('https://localhost/install'), 'install-unlocked');
            $this->assertFileDoesNotExist($directory.'/storage/app/installed.lock');
            $this->assertDatabaseCount('users', 0);
        } finally {
            File::deleteDirectory($directory);
        }
    }

    private function assertTheme(TestResponse $response, string $name): void
    {
        $response->assertOk()
            ->assertSee('data-bs-theme="dark"', false)
            ->assertSee('theme.js', false)
            ->assertSee('theme.css', false)
            ->assertSee('Color theme')
            ->assertSee('value="system"', false)
            ->assertSee('value="light"', false)
            ->assertSee('value="dark"', false);
        $this->assertSame(1, substr_count($response->getContent(), 'data-theme-control'));
        $this->assertLessThan(strpos($response->getContent(), '</head>'), strpos($response->getContent(), 'theme.js'));

        /** Opt-in browser fixtures contain factory data only; no application database or mail is used. */
        $directory = getenv('THEME_BROWSER_FIXTURES');
        if ($directory !== false && $directory !== '') {
            $resolved = realpath($directory);
            $this->assertNotFalse($resolved);
            $this->assertStringStartsWith(sys_get_temp_dir().'/schoolshare-theme-browser-', $resolved);
            $html = preg_replace('/https?:\/\/(?:localhost|127\.0\.0\.1)(?::\d+)?/', '', $response->getContent());
            file_put_contents($resolved.'/'.$name.'.html', $html);
        }
    }
}
