<?php

namespace App\Console\Commands;

use App\CpanelThemeUpdateBuilder;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Throwable;

#[Signature('schoolshare:package-theme-update {--output= : New ZIP filename outside the public directory}')]
#[Description('Build the October 12 appearance-only update for the existing EthioNext cPanel layout')]
class BuildCpanelThemeUpdate extends Command
{
    public function handle(CpanelThemeUpdateBuilder $builder): int
    {
        $output = (string) $this->option('output');
        $directory = realpath(dirname($output));
        $public = realpath(public_path());
        if ($output === '' || $directory === false || ! preg_match('/\A[a-zA-Z0-9][a-zA-Z0-9._-]*\.zip\z/', basename($output)) || ($public !== false && str_starts_with($directory.'/', $public.'/')) || file_exists($output.'.sha256') || is_link($output.'.sha256')) {
            $this->error('Provide an unused --output ZIP path and checksum path outside the public directory.');

            return self::FAILURE;
        }
        try {
            $digest = $builder->build(base_path(), $output);
            if (file_put_contents($output.'.sha256', $digest.'  '.basename($output)."\n") === false) {
                throw new \RuntimeException('Cannot write the ZIP checksum companion.');
            }
        } catch (Throwable $exception) {
            $this->error('Theme packaging failed: '.$exception->getMessage());

            return self::FAILURE;
        }
        $this->info('Created '.$output);
        $this->line('SHA-256: '.$digest);
        $this->line('Update instructions: UPDATE.md inside the ZIP.');

        return self::SUCCESS;
    }
}
