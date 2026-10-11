<?php

namespace App\Console\Commands;

use App\DeploymentHealth;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

#[Signature('schoolshare:health')]
#[Description('Check database, cache, private storage, runtime permissions, and free disk space')]
class CheckDeploymentHealth extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(DeploymentHealth $health): int
    {
        $failed = array_keys(array_filter($health->checks(), fn (bool $passed): bool => ! $passed));

        if ($failed !== []) {
            Log::error('Deployment health check failed.', ['checks' => $failed]);
            $this->error('Unavailable: '.implode(', ', $failed));

            return self::FAILURE;
        }

        $this->info('Deployment health checks passed.');

        return self::SUCCESS;
    }
}
