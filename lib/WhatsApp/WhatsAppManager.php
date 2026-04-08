<?php

namespace Lib\WhatsApp;

use Illuminate\Support\Manager;
use Lib\WhatsApp\Contracts\IWhatsAppGate;
use Lib\WhatsApp\Gates\TestingGate;

class WhatsAppManager extends Manager
{
    /**
     * {@inheritDoc}
     */
    public function getDefaultDriver()
    {
        return config('app.whatsapp.driver', default: 'testing');
    }

    /**
     * Create a driver instance.
     */
    public function createTestingDriver(): IWhatsAppGate
    {
        return new TestingGate;
    }
}
