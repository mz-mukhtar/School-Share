<?php

namespace App\Console\Commands;

use App\CpanelReleaseBuilder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('schoolshare:package {--vendor= : No-dev vendor directory matching composer.lock} {--output= : New ZIP filename outside the public directory} {--public-directory=public_html : Public folder beside the private schoolshare folder}')]
#[Description('Build a secret-free, fresh-install cPanel package with production dependencies')]
class BuildCpanelRelease extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(CpanelReleaseBuilder $builder): int
    {
        $vendor = (string) $this->option('vendor');
        $output = (string) $this->option('output');
        $public = realpath(public_path());
        $outputDirectory = realpath(dirname($output));
        if ($vendor === '' || $output === '' || $outputDirectory === false || ($public !== false && str_starts_with($outputDirectory.'/', $public.'/'))) {
            $this->error('Provide --vendor and a new --output ZIP path outside the public directory.');

            return self::FAILURE;
        }
        try {
            $digest = $builder->build(base_path(), $vendor, $output, (string) $this->option('public-directory'));
        } catch (Throwable $exception) {
            $this->error('Packaging failed: '.$exception->getMessage());

            return self::FAILURE;
        }
        $this->info('Created '.$output);
        $this->line('SHA-256: '.$digest);

        return self::SUCCESS;
    }
}
