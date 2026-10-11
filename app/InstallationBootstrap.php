<?php

namespace App;

class InstallationBootstrap
{
    /** Bootstrap only an explicitly packaged, never-installed copy. */
    public static function prepare(string $root): void
    {
        if (! is_file($root.'/.cpanel-layout') || is_file($root.'/.env')) {
            return;
        }
        $lock = fopen($root.'/storage/app/setup-bootstrap.lock', 'c');
        if ($lock === false) {
            throw new \RuntimeException('The private application directory must be writable.');
        }
        try {
            if (! flock($lock, LOCK_EX)) {
                throw new \RuntimeException('Cannot lock installation bootstrap.');
            }
            if (is_file($root.'/.env')) {
                return;
            }
            if (is_file($root.'/storage/app/installed.lock') || is_file($root.'/storage/app/installation-started.lock')) {
                throw new \RuntimeException('Restore the original environment configuration; installation cannot be reopened.');
            }
            $environment = new InstallationEnvironment;
            $token = bin2hex(random_bytes(32));
            $environment->writePrivateFile($root.'/setup-token.txt', $token."\n");
            $environment->write($root.'/.env', $environment->defaults('base64:'.base64_encode(random_bytes(32)), hash('sha256', $token)));
        } finally {
            flock($lock, LOCK_UN);
            fclose($lock);
        }
    }
}
