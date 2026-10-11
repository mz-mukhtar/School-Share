<?php

namespace Tests\Feature;

use App\CpanelThemeUpdateBuilder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use PHPUnit\Framework\Attributes\TestWith;
use RuntimeException;
use Symfony\Component\Process\Process;
use Tests\TestCase;
use ZipArchive;

class CpanelThemeUpdateBuilderTest extends TestCase
{
    private string $root;

    protected function setUp(): void
    {
        parent::setUp();
        $this->root = sys_get_temp_dir().'/schoolshare-theme-package-'.Str::uuid();
    }

    protected function tearDown(): void
    {
        if (is_dir($this->root)) {
            File::deleteDirectory($this->root);
        }
        parent::tearDown();
    }

    public function test_archive_contains_only_update_files_and_valid_checksums(): void
    {
        File::ensureDirectoryExists($this->root, 0700);
        $builder = app(CpanelThemeUpdateBuilder::class);

        $digest = $builder->build(base_path(), $this->root.'/update.zip');

        $archive = new ZipArchive;
        $this->assertTrue($archive->open($this->root.'/update.zip'));
        $this->assertSame(hash_file('sha256', $this->root.'/update.zip'), $digest);
        $this->assertSame(36, $archive->numFiles);
        $checksums = [];
        foreach (explode("\n", trim($archive->getFromName('SHA256SUMS'))) as $line) {
            [$checksum, $entry] = explode('  ', $line, 2);
            $checksums[$entry] = $checksum;
        }
        $this->assertCount(34, $checksums);
        for ($index = 0; $index < $archive->numFiles; $index++) {
            $entry = $archive->getNameIndex($index);
            if (in_array($entry, ['UPDATE.md', 'SHA256SUMS'], true)) {
                continue;
            }
            $this->assertMatchesRegularExpression('~\A(?:schoolshare/resources/views/[a-z/-]+\.blade\.php|schoolshare\.ethionext\.com\.et/theme\.(?:css|js))\z~', $entry);
            $this->assertSame(hash('sha256', $archive->getFromName($entry)), $checksums[$entry]);
        }
        $this->assertSame(File::get(public_path('theme.css')), $archive->getFromName('schoolshare.ethionext.com.et/theme.css'));
        $this->assertSame(File::get(public_path('theme.js')), $archive->getFromName('schoolshare.ethionext.com.et/theme.js'));
        $this->assertSame(File::get(resource_path('views/components/theme-switcher.blade.php')), $archive->getFromName('schoolshare/resources/views/components/theme-switcher.blade.php'));
        $this->assertFalse($archive->locateName('schoolshare/resources/views/installation.blade.php'));
        $this->assertStringContainsString('update.zip.sha256', $archive->getFromName('UPDATE.md'));
        $archive->close();
    }

    #[TestWith(['missing'])]
    #[TestWith(['symlink'])]
    public function test_missing_or_symlinked_payload_is_refused_without_a_partial_archive(string $condition): void
    {
        File::ensureDirectoryExists($this->root.'/public', 0700);
        File::copyDirectory(resource_path('views'), $this->root.'/resources/views');
        File::copy(public_path('theme.css'), $this->root.'/public/theme.css');
        if ($condition === 'symlink') {
            symlink(public_path('theme.js'), $this->root.'/public/theme.js');
        }

        try {
            app(CpanelThemeUpdateBuilder::class)->build($this->root, $this->root.'/refused.zip');
            $this->fail('Incomplete or linked payloads must not be distributed.');
        } catch (RuntimeException $exception) {
            $this->assertStringContainsString('unsafe theme-update source', $exception->getMessage());
            $this->assertFileDoesNotExist($this->root.'/refused.zip');
        }
    }

    public function test_existing_output_is_not_overwritten(): void
    {
        File::ensureDirectoryExists($this->root, 0700);
        File::put($this->root.'/preserve.zip', 'previous-artifact');

        try {
            app(CpanelThemeUpdateBuilder::class)->build(base_path(), $this->root.'/preserve.zip');
            $this->fail('Existing update archives must be preserved.');
        } catch (RuntimeException) {
            $this->assertSame('previous-artifact', File::get($this->root.'/preserve.zip'));
        }
    }

    public function test_command_creates_zip_and_matching_checksum_companion(): void
    {
        File::ensureDirectoryExists($this->root, 0700);

        $this->artisan('schoolshare:package-theme-update', ['--output' => $this->root.'/command.zip'])->assertSuccessful();

        $this->assertSame(hash_file('sha256', $this->root.'/command.zip')."  command.zip\n", File::get($this->root.'/command.zip.sha256'));
    }

    public function test_command_refuses_public_output_and_preserves_existing_checksum(): void
    {
        File::ensureDirectoryExists($this->root, 0700);
        File::put($this->root.'/preserve.zip.sha256', 'previous-checksum');

        $this->artisan('schoolshare:package-theme-update', ['--output' => public_path('refused-theme-update.zip')])->assertFailed();
        $this->artisan('schoolshare:package-theme-update', ['--output' => $this->root.'/preserve.zip'])->assertFailed();
        $this->artisan('schoolshare:package-theme-update')->assertFailed();

        $this->assertFileDoesNotExist(public_path('refused-theme-update.zip'));
        $this->assertFileDoesNotExist($this->root.'/preserve.zip');
        $this->assertSame('previous-checksum', File::get($this->root.'/preserve.zip.sha256'));
    }

    public function test_documented_update_and_rollback_preserve_existing_configuration_and_data(): void
    {
        File::ensureDirectoryExists($this->root, 0700);
        $preserved = [
            'schoolshare/.env' => 'PRIVATE-ENVIRONMENT-FIXTURE',
            'schoolshare/storage/app/private/project.txt' => 'PRIVATE-UPLOAD-FIXTURE',
            'schoolshare/storage/app/installed.lock' => 'INSTALLATION-LOCK-FIXTURE',
            'schoolshare/resources/views/custom.blade.php' => 'CUSTOM-VIEW-FIXTURE',
            'schoolshare.ethionext.com.et/index.php' => 'FRONT-CONTROLLER-FIXTURE',
            'schoolshare.ethionext.com.et/build/existing.js' => 'EXISTING-ASSET-FIXTURE',
            'public_html/index.php' => 'COMPANY-WEBSITE-FIXTURE',
        ];
        foreach ($preserved as $path => $content) {
            File::ensureDirectoryExists(dirname($this->root.'/'.$path));
            File::put($this->root.'/'.$path, $content);
        }
        File::ensureDirectoryExists($this->root.'/schoolshare/resources/views/explore');
        File::put($this->root.'/schoolshare/resources/views/explore/index.blade.php', 'PREVIOUS-EXPLORE-TEMPLATE');
        File::put($this->root.'/schoolshare.ethionext.com.et/theme.css', 'PREVIOUS-CSS');
        File::put($this->root.'/schoolshare/artisan', <<<'PHP'
<?php
file_put_contents(__DIR__.'/command-log.txt', $argv[1]."\n", FILE_APPEND);
PHP);
        $output = $this->root.'/update.zip';
        $digest = app(CpanelThemeUpdateBuilder::class)->build(base_path(), $output);
        File::put($output.'.sha256', $digest."  update.zip\n");
        $archive = new ZipArchive;
        $archive->open($output);
        $guide = $archive->getFromName('UPDATE.md');
        $archive->close();
        preg_match_all('/```sh\n(.*?)\n```/s', $guide, $matches);
        $this->assertCount(5, $matches[1]);
        $commands = str_replace('/home/ethionrf', $this->root, implode("\n", array_slice($matches[1], 0, 4)));

        $update = new Process(['bash', '-e', '-c', $commands], $this->root);
        $update->run();

        $this->assertSame(0, $update->getExitCode(), $update->getOutput().$update->getErrorOutput());
        foreach ($preserved as $path => $content) {
            $this->assertSame($content, File::get($this->root.'/'.$path), $path);
        }
        $this->assertSame(File::get(resource_path('views/explore/index.blade.php')), File::get($this->root.'/schoolshare/resources/views/explore/index.blade.php'));
        $this->assertSame(File::get(public_path('theme.css')), File::get($this->root.'/schoolshare.ethionext.com.et/theme.css'));
        $this->assertSame("down\nview:cache\nup\n", File::get($this->root.'/schoolshare/command-log.txt'));

        $rollback = new Process(['bash', '-e', '-c', str_replace('/home/ethionrf', $this->root, $matches[1][4])], $this->root);
        $rollback->run();

        $this->assertSame(0, $rollback->getExitCode(), $rollback->getErrorOutput());
        $this->assertSame('PREVIOUS-EXPLORE-TEMPLATE', File::get($this->root.'/schoolshare/resources/views/explore/index.blade.php'));
        $this->assertSame('PREVIOUS-CSS', File::get($this->root.'/schoolshare.ethionext.com.et/theme.css'));
        foreach ($preserved as $path => $content) {
            $this->assertSame($content, File::get($this->root.'/'.$path), $path);
        }
    }

    public function test_changed_payload_is_detected_before_the_live_site_is_touched(): void
    {
        File::ensureDirectoryExists($this->root, 0700);
        app(CpanelThemeUpdateBuilder::class)->build(base_path(), $this->root.'/update.zip');
        $archive = new ZipArchive;
        $archive->open($this->root.'/update.zip');
        $archive->extractTo($this->root.'/staging');
        $archive->close();
        File::put($this->root.'/staging/schoolshare.ethionext.com.et/theme.css', 'DAMAGED-PAYLOAD');

        $verification = new Process(['sha256sum', '-c', 'SHA256SUMS'], $this->root.'/staging');
        $verification->run();

        $this->assertSame(1, $verification->getExitCode());
        $this->assertStringContainsString('theme.css: FAILED', $verification->getOutput());
    }
}
