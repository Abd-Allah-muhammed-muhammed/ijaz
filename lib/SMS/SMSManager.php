<?php

namespace Lib\SMS;

use Illuminate\Support\Manager;
use Lib\SMS\Contracts\ISMSGate;
use Lib\SMS\Gates\AuthenticaGate;
use Lib\SMS\Gates\TestingGate;

class SMSManager extends Manager
{
    /**
     * {@inheritDoc}
     */
    public function getDefaultDriver()
    {
        $driver = config('sms.driver', default: 'testing');

        return filled($driver) ? $driver : 'testing';
    }

    /**
     * Create a driver instance.
     */
    public function createTestingDriver(): ISMSGate
    {
        return new TestingGate;
    }

    public function createAuthenticaDriver(): ISMSGate
    {
        return app(AuthenticaGate::class);
    }
}
