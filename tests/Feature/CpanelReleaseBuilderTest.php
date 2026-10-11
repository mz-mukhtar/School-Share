<?php

namespace Tests\Feature;

use App\CpanelReleaseBuilder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;
use ZipArchive;

class CpanelReleaseBuilderTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/schoolshare-package-'.Str::uuid();
        File::ensureDirectoryExists($this->root.'/vendor/composer', 0700);
    }

    protected function tearDown(): void
    {
        File::deleteDirectory($this->root);
        parent::tearDown();
    }

    public function test_package_contains_private_runtime_and_public_assets_but_no_environment_or_user_data(): void
    {
        $this->productionVendor();
        $source = $this->sourceFixture();

        $checksum = app(CpanelReleaseBuilder::class)->build($source, $this->root.'/vendor', $this->root.'/release.zip');

        $archive = new ZipArchive;
        $this->assertTrue($archive->open($this->root.'/release.zip'));
        $this->assertSame(hash_file('sha256', $this->root.'/release.zip'), $checksum);
        $this->assertStringContainsString("dirname(__DIR__).'/schoolshare'", $archive->getFromName('public_html/index.php'));
        $this->assertNotFalse($archive->locateName('schoolshare/.cpanel-layout'));
        $this->assertNotFalse($archive->locateName('schoolshare/vendor/autoload.php'));
        $this->assertNotFalse($archive->locateName('schoolshare/docs/INSTALLATION_CPANEL.md'));
        $this->assertNotFalse($archive->locateName('public_html/.htaccess'));
        $this->assertNotFalse($archive->locateName('public_html/build/manifest.json'));
        $this->assertSame(File::get(public_path('theme.css')), $archive->getFromName('public_html/theme.css'));
        $this->assertSame(File::get(public_path('theme.js')), $archive->getFromName('public_html/theme.js'));
        foreach (['schoolshare/.env', 'schoolshare/setup-token.txt', 'schoolshare/database/database.sqlite', 'schoolshare/bootstrap/cache/config.php', 'schoolshare/.git/config', 'public_html/storage', 'public_html/hot'] as $entry) {
            $this->assertFalse($archive->locateName($entry));
        }
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $name = $archive->getNameIndex($index);
            $this->assertFalse(str_starts_with($name, 'schoolshare/storage/') && ! str_ends_with($name, '/'));
            $this->assertFalse(str_starts_with($name, 'public_html/storage/'));
        }
        $archive->close();
    }

    public function test_packaging_refuses_development_dependencies_and_preserves_existing_output(): void
    {
        $this->productionVendor(true);
        try {
            app(CpanelReleaseBuilder::class)->build(base_path(), $this->root.'/vendor', $this->root.'/release.zip');
            $this->fail('Development vendor must not be packaged.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('no-dev', $exception->getMessage());
            $this->assertFileDoesNotExist($this->root.'/release.zip');
        }
        File::put($this->root.'/release.zip', 'preserve');
        try {
            app(CpanelReleaseBuilder::class)->build(base_path(), $this->root.'/vendor', $this->root.'/release.zip');
            $this->fail('Existing artifacts must not be overwritten.');
        } catch (\RuntimeException) {
            $this->assertSame('preserve', File::get($this->root.'/release.zip'));
        }
    }

    public function test_subdomain_package_keeps_main_website_outside_the_archive_and_sets_both_public_paths(): void
    {
        $this->productionVendor();
        $source = $this->sourceFixture();

        app(CpanelReleaseBuilder::class)->build($source, $this->root.'/vendor', $this->root.'/subdomain.zip', 'schoolshare.ethionext.com.et');

        $archive = new ZipArchive;
        $this->assertTrue($archive->open($this->root.'/subdomain.zip'));
        $this->assertStringContainsString("dirname(__DIR__).'/schoolshare'", $archive->getFromName('schoolshare.ethionext.com.et/index.php'));
        $this->assertStringContainsString("dirname(dirname(__DIR__)).'/schoolshare.ethionext.com.et'", $archive->getFromName('schoolshare/bootstrap/app.php'));
        $this->assertNotFalse($archive->locateName('schoolshare.ethionext.com.et/.htaccess'));
        $this->assertNotFalse($archive->locateName('schoolshare.ethionext.com.et/build/manifest.json'));
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $this->assertFalse(str_starts_with($archive->getNameIndex($index), 'public_html/'));
        }
        $archive->close();
    }

    #[TestWith(['../public_html'])]
    #[TestWith(['/public_html'])]
    #[TestWith(['nested/public'])]
    #[TestWith(['schoolshare'])]
    #[TestWith([''])]
    #[TestWith(["public'html"])]
    public function test_unsafe_or_private_public_directory_names_are_refused_without_creating_an_archive(string $directory): void
    {
        try {
            app(CpanelReleaseBuilder::class)->build(base_path(), $this->root.'/vendor', $this->root.'/invalid.zip', $directory);
            $this->fail('Unsafe public paths must not be packaged.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('single safe folder name', $exception->getMessage());
            $this->assertFileDoesNotExist($this->root.'/invalid.zip');
        }
    }

    public function test_packaging_requires_built_assets_and_matching_locked_versions(): void
    {
        $this->productionVendor();
        File::copy(base_path('composer.lock'), $this->root.'/composer.lock');
        try {
            app(CpanelReleaseBuilder::class)->build($this->root, $this->root.'/vendor', $this->root.'/release.zip');
            $this->fail('Missing built assets must be reported.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Build frontend assets', $exception->getMessage());
        }
        File::put($this->root.'/vendor/composer/installed.json', json_encode(['dev' => false, 'packages' => []], JSON_THROW_ON_ERROR));
        try {
            app(CpanelReleaseBuilder::class)->build(base_path(), $this->root.'/vendor', $this->root.'/release.zip');
            $this->fail('Unmatched lockfiles must be reported.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('composer.lock', $exception->getMessage());
        }
    }

    public function test_changed_bootstrap_layout_is_refused_without_leaving_a_partial_archive(): void
    {
        $this->productionVendor();
        $source = $this->sourceFixture();
        File::put($source.'/bootstrap/app.php', str_replace("'/public_html'", "'/different_public'", File::get($source.'/bootstrap/app.php')));

        try {
            app(CpanelReleaseBuilder::class)->build($source, $this->root.'/vendor', $this->root.'/invalid-layout.zip', 'schoolshare.ethionext.com.et');
            $this->fail('Unexpected bootstrap layouts must be reviewed before packaging.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Unexpected application public path', $exception->getMessage());
            $this->assertFileDoesNotExist($this->root.'/invalid-layout.zip');
        }
    }

    private function productionVendor(bool $development = false): void
    {
        $lock = json_decode(File::get(base_path('composer.lock')), true, flags: JSON_THROW_ON_ERROR);
        File::put($this->root.'/vendor/autoload.php', '<?php');
        File::put($this->root.'/vendor/composer/installed.json', json_encode(['dev' => $development, 'packages' => $lock['packages']], JSON_THROW_ON_ERROR));
    }

    private function sourceFixture(): string
    {
        $source = $this->root.'/source';
        foreach (['app', 'config', 'resources', 'routes', 'database/migrations'] as $directory) {
            File::copyDirectory(base_path($directory), $source.'/'.$directory);
        }
        foreach (['artisan', 'composer.json', 'composer.lock', 'bootstrap/app.php', 'bootstrap/providers.php', 'README.md', 'SELF_HOSTING.md', 'LICENSE.md', 'SECURITY.md', 'docs/INSTALLATION_CPANEL.md', 'public/index.php', 'public/.htaccess', 'public/theme.css', 'public/theme.js'] as $file) {
            File::ensureDirectoryExists(dirname($source.'/'.$file));
            File::copy(base_path($file), $source.'/'.$file);
        }
        foreach (['public/build/manifest.json', 'public/storage/private.txt', 'public/hot', 'public/.env', '.env', 'setup-token.txt', 'database/database.sqlite', 'storage/app/private/secret.txt', 'bootstrap/cache/config.php'] as $file) {
            File::ensureDirectoryExists(dirname($source.'/'.$file));
            File::put($source.'/'.$file, 'not-public-test-fixture');
        }

        return $source;
    }
}
