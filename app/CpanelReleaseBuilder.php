<?php

namespace App;

use RecursiveCallbackFilterIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;
use ZipArchive;

class CpanelReleaseBuilder
{
    public function build(string $source, string $vendor, string $output, string $publicDirectory = 'public_html'): string
    {
        if (! preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]{0,252}\z/', $publicDirectory) || $publicDirectory === 'schoolshare') {
            throw new RuntimeException('The public directory must be a single safe folder name, separate from schoolshare.');
        }
        if (! extension_loaded('zip') || is_file($output) || ! is_dir(dirname($output))) {
            throw new RuntimeException('ZIP support, an existing output directory, and an unused output filename are required.');
        }
        $installed = json_decode((string) file_get_contents($vendor.'/composer/installed.json'), true, flags: JSON_THROW_ON_ERROR);
        $lock = json_decode((string) file_get_contents($source.'/composer.lock'), true, flags: JSON_THROW_ON_ERROR);
        $expected = $this->versions($lock['packages']);
        if (($installed['dev'] ?? true) !== false || ! is_file($vendor.'/autoload.php') || $this->versions($installed['packages']) !== $expected) {
            throw new RuntimeException('Supply a complete no-dev vendor directory matching composer.lock.');
        }
        if (! is_file($source.'/public/build/manifest.json')) {
            throw new RuntimeException('Build frontend assets before packaging.');
        }
        $zip = new ZipArchive;
        if ($zip->open($output, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new RuntimeException('Cannot create the release archive.');
        }
        try {
            foreach (['app', 'config', 'resources', 'routes', 'database/migrations'] as $directory) {
                $this->addTree($zip, $source.'/'.$directory, 'schoolshare/'.$directory);
            }
            foreach (['artisan', 'composer.json', 'composer.lock', 'bootstrap/app.php', 'bootstrap/providers.php', 'README.md', 'SELF_HOSTING.md', 'LICENSE.md', 'SECURITY.md', 'docs/INSTALLATION_CPANEL.md'] as $file) {
                $this->addFile($zip, $source.'/'.$file, 'schoolshare/'.$file);
            }
            $this->addTree($zip, $vendor, 'schoolshare/vendor');
            $this->addTree($zip, $source.'/public', $publicDirectory, ['storage', 'hot']);
            $index = (string) file_get_contents($source.'/public/index.php');
            if (substr_count($index, '$basePath = dirname(__DIR__);') !== 1) {
                throw new RuntimeException('Unexpected front-controller layout; refusing to package.');
            }
            $zip->addFromString($publicDirectory.'/index.php', str_replace('$basePath = dirname(__DIR__);', '$basePath = dirname(__DIR__).\'/schoolshare\';', $index));
            $bootstrap = (string) file_get_contents($source.'/bootstrap/app.php');
            $publicPath = "dirname(dirname(__DIR__)).'/public_html'";
            if (substr_count($bootstrap, $publicPath) !== 1) {
                throw new RuntimeException('Unexpected application public path; refusing to package.');
            }
            $zip->addFromString('schoolshare/bootstrap/app.php', str_replace($publicPath, "dirname(dirname(__DIR__)).'/".$publicDirectory."'", $bootstrap));
            $zip->addFromString('schoolshare/.cpanel-layout', "SchoolShare fresh cPanel installation layout.\n");
            foreach (['bootstrap/cache', 'storage/app/private', 'storage/app/public', 'storage/framework/cache/data', 'storage/framework/sessions', 'storage/framework/views', 'storage/logs'] as $directory) {
                if (! $zip->addEmptyDir('schoolshare/'.$directory)) {
                    throw new RuntimeException('Cannot add runtime directories.');
                }
            }
            if (! $zip->close()) {
                throw new RuntimeException('Cannot finish the release archive.');
            }
        } catch (\Throwable $exception) {
            $zip->close();
            if (is_file($output)) {
                unlink($output);
            }
            throw $exception;
        }

        return hash_file('sha256', $output);
    }

    /** @param list<array<string, mixed>> $packages
     * @return array<string, string>
     */
    private function versions(array $packages): array
    {
        $versions = [];
        foreach ($packages as $package) {
            $versions[$package['name']] = $package['version'];
        }
        ksort($versions);

        return $versions;
    }

    /** @param list<string> $excluded */
    private function addTree(ZipArchive $zip, string $path, string $prefix, array $excluded = []): void
    {
        $directory = new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS);
        $iterator = new RecursiveIteratorIterator(new RecursiveCallbackFilterIterator($directory, function (\SplFileInfo $file) use ($path, $excluded): bool {
            $relative = substr($file->getPathname(), strlen($path) + 1);

            return ! $file->isLink() && ! in_array(explode('/', $relative)[0], $excluded, true)
                && ! str_starts_with($file->getFilename(), '.env')
                && ! in_array($file->getFilename(), ['auth.json', 'setup-token.txt', '.git'], true);
        }));
        foreach ($iterator as $file) {
            $relative = substr($file->getPathname(), strlen($path) + 1);
            if ($file->isLink() || in_array(explode('/', $relative)[0], $excluded, true)) {
                continue;
            }
            $this->addFile($zip, $file->getPathname(), $prefix.'/'.$relative);
        }
    }

    private function addFile(ZipArchive $zip, string $path, string $entry): void
    {
        if (! is_file($path) || ! $zip->addFile($path, $entry)) {
            throw new RuntimeException('Cannot include a required release file.');
        }
        $zip->setExternalAttributesName($entry, ZipArchive::OPSYS_UNIX, 0100644 << 16);
    }
}
