<?php

namespace App;

class InstallationEnvironment
{
    /** @param array<string, string|int|bool> $values */
    public function write(string $path, array $values): void
    {
        $lines = [];
        foreach ($values as $name => $value) {
            if (! preg_match('/\A[A-Z][A-Z0-9_]*\z/', $name) || preg_match('/[\x00-\x1f\x7f]/', (string) $value)) {
                throw new \RuntimeException('Invalid installation configuration.');
            }
            if (is_string($value) && (in_array(strtolower($value), ['true', '(true)', 'false', '(false)', 'empty', '(empty)', 'null', '(null)'], true)
                || preg_match('/\A([\'"])(.*)\1\z/', $value))) {
                $value = '"'.$value.'"';
            }
            $encoded = is_bool($value) ? ($value ? 'true' : 'false') : '"'.str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], (string) $value).'"';
            $lines[] = $name.'='.$encoded;
        }
        $this->writePrivateFile($path, implode("\n", $lines)."\n");
    }

    public function writePrivateFile(string $path, string $contents): void
    {
        $temporaryPath = tempnam(dirname($path), '.setup-');
        if ($temporaryPath === false) {
            throw new \RuntimeException('Cannot prepare installation configuration.');
        }
        try {
            if (! chmod($temporaryPath, 0600) || file_put_contents($temporaryPath, $contents) !== strlen($contents) || ! rename($temporaryPath, $path)) {
                throw new \RuntimeException('Cannot save installation configuration.');
            }
        } finally {
            if (is_file($temporaryPath)) {
                unlink($temporaryPath);
            }
        }
    }

    /** @return array<string, string|int|bool> */
    public function defaults(string $key, string $tokenHash): array
    {
        return [
            'APP_NAME' => 'SchoolShare', 'APP_ENV' => 'production', 'APP_KEY' => $key,
            'APP_DEBUG' => false, 'APP_URL' => 'https://schoolshare.example',
            'LOG_CHANNEL' => 'stack', 'LOG_LEVEL' => 'warning',
            'DB_CONNECTION' => 'mysql', 'DB_HOST' => '127.0.0.1', 'DB_PORT' => 3306,
            'DB_DATABASE' => '', 'DB_USERNAME' => '', 'DB_PASSWORD' => '',
            'CACHE_STORE' => 'file', 'SESSION_DRIVER' => 'file', 'SESSION_SECURE_COOKIE' => true,
            'QUEUE_CONNECTION' => 'sync', 'FILESYSTEM_DISK' => 'local',
            'MAIL_MAILER' => 'smtp', 'MAIL_SCHEME' => 'smtps', 'MAIL_HOST' => '',
            'MAIL_PORT' => 465, 'MAIL_USERNAME' => '', 'MAIL_PASSWORD' => '',
            'MAIL_FROM_ADDRESS' => '', 'MAIL_FROM_NAME' => 'SchoolShare',
            'SCHOOLSHARE_MINIMUM_FREE_MB' => 100,
            'INSTALL_ENABLED' => true, 'INSTALL_TOKEN_HASH' => $tokenHash,
        ];
    }
}
