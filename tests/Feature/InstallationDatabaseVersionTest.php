<?php

namespace Tests\Feature;

use App\InstallationDatabase;
use PHPUnit\Framework\Attributes\TestWith;
use Tests\TestCase;

class InstallationDatabaseVersionTest extends TestCase
{
    #[TestWith(['mariadb', '10.11.19-MariaDB-cll-lve', true])]
    #[TestWith(['mariadb', '10.11.19-MariaDB-ubu2204', true])]
    #[TestWith(['mariadb', '5.5.5-10.11.19-MariaDB', true])]
    #[TestWith(['mariadb', '10.11.20-MariaDB', true])]
    #[TestWith(['mariadb', '10.11.18-MariaDB', false])]
    #[TestWith(['mariadb', '10.6.24-MariaDB', false])]
    #[TestWith(['mariadb', '11.4.13-MariaDB', true])]
    #[TestWith(['mariadb', '5.5.5-11.4.13-MariaDB', true])]
    #[TestWith(['mariadb', '11.8.4-MariaDB', false])]
    #[TestWith(['mysql', '8.4.11', true])]
    #[TestWith(['mysql', '8.4.11-commercial', true])]
    #[TestWith(['mysql', '8.0.44', false])]
    #[TestWith(['mysql', '10.11.19-MariaDB', false])]
    #[TestWith(['mariadb', '8.4.11', false])]
    #[TestWith(['pgsql', '8.4.11', false])]
    #[TestWith(['mariadb', '10.11.19invalid-MariaDB', false])]
    public function test_only_verified_database_release_lines_and_matching_drivers_are_supported(string $driver, string $version, bool $expected): void
    {
        $supported = app(InstallationDatabase::class)->supportsVersion($driver, $version);

        $this->assertSame($expected, $supported);
    }
}
