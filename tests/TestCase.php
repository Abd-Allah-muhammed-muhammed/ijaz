<?php

namespace Tests;

use App\Support\LookupCache;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Telescope\Telescope;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

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
