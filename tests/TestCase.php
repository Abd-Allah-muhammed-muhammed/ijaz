<?php

namespace Tests;

use App\Support\LookupCache;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Telescope\Telescope;
use Tests\Support\TestingDatabaseGuard;

abstract class TestCase extends BaseTestCase
{
    /**
     * Creates the application.
     *
     * Cached config is checked BEFORE bootstrap — once config is cached, phpunit.xml
     * DB_* force overrides are ignored and tests can hit the real MySQL database.
     */
    public function createApplication(): Application
    {
        TestingDatabaseGuard::assertNoCachedConfig(dirname(__DIR__));

        $app = require Application::inferBasePath().'/bootstrap/app.php';

        $app->make(Kernel::class)->bootstrap();

        return $app;
    }

    protected function setUp(): void
    {
        parent::setUp();

        TestingDatabaseGuard::assertIsolatedTestingDatabase(
            environment: (string) app()->environment(),
            defaultConnection: (string) config('database.default'),
            defaultDatabase: (string) config('database.connections.'.config('database.default').'.database'),
            monitoringDatabase: config('database.connections.monitoring.database'),
        );

        // Forever lookup caches must not leak across tests — LazilyRefreshDatabase
        // rolls back the DB but the array cache driver keeps entries for the process.
        LookupCache::flush();

        // Keep Telescope from recording during tests (avoids suite-wide memory growth
        // when TELESCOPE_ENABLED is true for route-level auth coverage).
        if (class_exists(Telescope::class)) {
            Telescope::stopRecording();
        }
    }
}
