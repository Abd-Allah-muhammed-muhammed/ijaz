<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Laravel\Telescope\Telescope;

abstract class TestCase extends BaseTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        // Keep Telescope from recording during tests (avoids suite-wide memory growth
        // when TELESCOPE_ENABLED is true for route-level auth coverage).
        if (class_exists(Telescope::class)) {
            Telescope::stopRecording();
        }
    }
}
