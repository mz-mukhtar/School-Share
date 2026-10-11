<?php

namespace App;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Throwable;

class InstallationDatabase
{
    /** @param array<string, mixed> $settings */
    public function connect(array $settings): void
    {
        $driver = $settings['db_connection'];
        $connection = array_replace(config('database.connections.'.$driver), [
            'url' => null, 'host' => $settings['db_host'], 'port' => $settings['db_port'],
            'database' => $settings['db_database'], 'username' => $settings['db_username'],
            'password' => $settings['db_password'], 'unix_socket' => '',
        ]);
        config(['database.default' => $driver, 'database.connections.'.$driver => $connection]);
        DB::purge($driver);
        try {
            $version = (string) DB::selectOne('SELECT VERSION() AS version')->version;
            if (! $this->supportsVersion($driver, $version)) {
                throw ValidationException::withMessages(['db_connection' => 'Select the actual database engine: MySQL 8.4, MariaDB 11.4, or MariaDB 10.11.19+ within the 10.11 release line is required.']);
            }
        } catch (ValidationException $exception) {
            throw $exception;
        } catch (Throwable) {
            throw ValidationException::withMessages(['db_database' => 'Cannot connect. Check the full cPanel database/user names, password, host, and privileges.']);
        }
    }

    public function supportsVersion(string $driver, string $version): bool
    {
        $isMariaDb = str_contains(strtolower($version), 'mariadb');
        if ($driver === 'mysql') {
            return ! $isMariaDb && preg_match('/\A8\.4\.[0-9]+(?:-|$)/', $version) === 1;
        }
        if ($driver !== 'mariadb' || ! $isMariaDb || preg_match('/\A(?:5\.5\.5-)?(10\.11\.[0-9]+|11\.4\.[0-9]+)(?:-|$)/', $version, $matches) !== 1) {
            return false;
        }

        return str_starts_with($matches[1], '11.4.') || version_compare($matches[1], '10.11.19', '>=');
    }

    public function requireEmptyDatabase(): void
    {
        try {
            $schemas = Schema::getCurrentSchemaListing();
            $empty = Schema::getTables($schemas) === [] && Schema::getViews($schemas) === [];
        } catch (Throwable) {
            throw ValidationException::withMessages(['db_database' => 'Cannot inspect this database. Check its privileges in cPanel.']);
        }
        if (! $empty) {
            throw ValidationException::withMessages(['db_database' => 'Fresh installation requires an empty database. No existing tables or views will be overwritten.']);
        }
    }
}
