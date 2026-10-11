<?php

namespace App;

use RuntimeException;
use ZipArchive;

class CpanelThemeUpdateBuilder
{
    public const PUBLIC_DIRECTORY = 'schoolshare.ethionext.com.et';

    /** @var list<string> */
    private const VIEWS = [
        'about', 'admin/layouts/admin', 'admin/upgrades/index', 'admin/users/index',
        'auth/login', 'auth/verify-otp', 'components/auth-session-status',
        'components/input-error', 'components/input-label', 'components/primary-button',
        'components/text-input', 'components/theme-switcher', 'explore/index', 'feed/index',
        'layouts/app', 'layouts/guest', 'pricing', 'profile/edit',
        'projects/checkpoints/create', 'projects/checkpoints/index', 'projects/checkpoints/show',
        'projects/create', 'projects/edit', 'projects/files/diff', 'projects/files/show',
        'projects/index', 'projects/show', 'terms', 'upgrade/checkout',
        'users/connections', 'users/show', 'welcome',
    ];

    /** @return array<string, string> Source paths mapped to archive paths. */
    public function files(): array
    {
        $files = [];
        foreach (self::VIEWS as $view) {
            $path = 'resources/views/'.$view.'.blade.php';
            $files[$path] = 'schoolshare/'.$path;
        }
        foreach (['theme.css', 'theme.js'] as $asset) {
            $files['public/'.$asset] = self::PUBLIC_DIRECTORY.'/'.$asset;
        }

        return $files;
    }

    public function build(string $source, string $output): string
    {
        $root = realpath($source);
        if (! extension_loaded('zip') || $root === false || file_exists($output) || is_link($output) || ! is_dir(dirname($output))) {
            throw new RuntimeException('ZIP support, a source directory, and an unused output filename in an existing directory are required.');
        }
        $contents = [];
        foreach ($this->files() as $path => $entry) {
            $resolved = realpath($root.'/'.$path);
            if ($resolved === false || ! is_file($resolved) || is_link($root.'/'.$path) || ! str_starts_with($resolved, $root.'/')) {
                throw new RuntimeException('Missing or unsafe theme-update source: '.$path);
            }
            $content = file_get_contents($resolved);
            if ($content === false) {
                throw new RuntimeException('Cannot read theme-update source: '.$path);
            }
            $contents[$entry] = $content;
        }
        $checksums = '';
        foreach ($contents as $entry => $content) {
            $checksums .= hash('sha256', $content).'  '.$entry."\n";
        }
        $contents['SHA256SUMS'] = $checksums;
        $contents['UPDATE.md'] = $this->instructions(basename($output));

        $archive = new ZipArchive;
        if ($archive->open($output, ZipArchive::CREATE | ZipArchive::EXCL) !== true) {
            throw new RuntimeException('Cannot create the theme-update archive.');
        }
        try {
            foreach ($contents as $entry => $content) {
                if (! $archive->addFromString($entry, $content) || ! $archive->setExternalAttributesName($entry, ZipArchive::OPSYS_UNIX, 0100644 << 16)) {
                    throw new RuntimeException('Cannot include theme-update file: '.$entry);
                }
            }
            if (! $archive->close()) {
                throw new RuntimeException('Cannot finish the theme-update archive.');
            }
        } catch (\Throwable $exception) {
            if ($archive->status === ZipArchive::ER_OK) {
                $archive->close();
            }
            if (is_file($output)) {
                unlink($output);
            }
            throw $exception;
        }

        return hash_file('sha256', $output);
    }

    private function instructions(string $filename): string
    {
        return str_replace('UPDATE_ZIP_FILENAME', $filename, <<<'MARKDOWN'
# SchoolShare appearance update — October 12, 2026

UPDATE ONLY: this is not an installer or a complete application upgrade.
Target: the existing PHP 8.5 split cPanel installation on ethionrf's account.
Includes 32 templates/components and two directly loaded theme assets.
Does not change quotas, configuration, dependencies, users, admin permissions,
passwords, SMTP settings, routes, or the database.

## Before starting

- Back up the database and private uploads with cPanel's backup tools.
- Confirm `/home/ethionrf/schoolshare/artisan` and
  `/home/ethionrf/schoolshare.ethionext.com.et/index.php` already exist.
- If you customized included templates, compare them before replacing them.
- Preserve `.env`, APP_KEY, storage, installation locks, and avatar symlinks.
- Do not run `/install`, migrations, key generation, or cache:clear.
- Never extract this ZIP directly into the live website or public_html.
- Run commands ONE AT A TIME. Stop at ANY failed command.

## 1. Upload and verify

Upload the ZIP and its `.sha256` companion into `/home/ethionrf` using File Manager,
NOT into the subdomain's public folder. In cPanel Terminal:

```sh
cd /home/ethionrf
test -f /home/ethionrf/schoolshare/artisan
test -f /home/ethionrf/schoolshare.ethionext.com.et/index.php
sha256sum -c UPDATE_ZIP_FILENAME.sha256
mkdir -m 700 /home/ethionrf/schoolshare-theme-update-2026-10-12
unzip /home/ethionrf/UPDATE_ZIP_FILENAME -d /home/ethionrf/schoolshare-theme-update-2026-10-12
cd /home/ethionrf/schoolshare-theme-update-2026-10-12
sha256sum -c SHA256SUMS
```

All 34 payload checksums must report OK. `mkdir` must create a NEW directory;
if it already exists, stop and choose a new name throughout these commands.
If unzip is unavailable, extract with File Manager into that new private folder.
Leave UPDATE.md and SHA256SUMS private; do not copy them into the public website.

## 2. Maintenance and rollback backup

```sh
cd /home/ethionrf/schoolshare
php artisan down --no-interaction
umask 077
test ! -e /home/ethionrf/schoolshare-theme-backup-2026-10-12.tar.gz
tar -czf /home/ethionrf/schoolshare-theme-backup-2026-10-12.tar.gz -C /home/ethionrf schoolshare/resources/views schoolshare.ethionext.com.et
tar -tzf /home/ethionrf/schoolshare-theme-backup-2026-10-12.tar.gz
```

Do not continue unless the backup succeeds and lists the old views and public
files. If the backup filename already exists, use a NEW filename everywhere;
do not overwrite an earlier backup. This does not replace the database/private-
upload backup. Keep the archive outside the public website.

## 3. Merge the files (do not delete or replace entire folders)

```sh
cp -R /home/ethionrf/schoolshare-theme-update-2026-10-12/schoolshare/resources/views/. /home/ethionrf/schoolshare/resources/views/
cp /home/ethionrf/schoolshare-theme-update-2026-10-12/schoolshare.ethionext.com.et/theme.css /home/ethionrf/schoolshare.ethionext.com.et/theme.css
cp /home/ethionrf/schoolshare-theme-update-2026-10-12/schoolshare.ethionext.com.et/theme.js /home/ethionrf/schoolshare.ethionext.com.et/theme.js
chmod 644 /home/ethionrf/schoolshare.ethionext.com.et/theme.css /home/ethionrf/schoolshare.ethionext.com.et/theme.js
chmod 644 /home/ethionrf/schoolshare/resources/views/components/theme-switcher.blade.php
cd /home/ethionrf
sha256sum -c /home/ethionrf/schoolshare-theme-update-2026-10-12/SHA256SUMS
cd /home/ethionrf/schoolshare
php artisan view:cache --no-interaction
```

The live-file check must report OK for ALL 34 application files. Stop if any
copy, checksum, or compilation fails. No npm or Composer commands are needed.
Only after all commands succeed, reopen the website:

```sh
php artisan up --no-interaction
```

## 4. Browser checks

Hard-refresh (Ctrl+Shift+R). Check Light, Dark, and System on Explore,
projects/files, profile/settings, password recovery, and admin pages at desktop
and mobile widths. Check dropdowns, mobile navigation, and persistence on reload.
Refresh any configured static-asset/CDN cache if it serves older CSS/JS.
Local testing cannot guarantee error-free operation on every hosting setup;
these live smoke checks are still required.

## Rollback

On failure, keep maintenance mode enabled. If already reopened, run
`php artisan down --no-interaction` again from the private app before restoring:

```sh
tar -xzf /home/ethionrf/schoolshare-theme-backup-2026-10-12.tar.gz -C /home/ethionrf
cd /home/ethionrf/schoolshare
php artisan view:cache --no-interaction
```

Only after successful compilation, run `php artisan up --no-interaction`.
New theme files may remain unused after restoring old templates; they are safe
to leave in place. Do not reinstall or reset the database.

Keep the package, extraction folder, and rollback backup private until the live
checks pass. Never put them in the public document root.
MARKDOWN);
    }
}
