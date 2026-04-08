<?php

namespace Lib\WhatsApp\Gates;

use Lib\WhatsApp\Contracts\IWhatsAppGate;
use Lib\WhatsApp\DTOs\WhatsAppMessage;
use Lib\WhatsApp\DTOs\WhatsAppResponse;

class TestingGate implements IWhatsAppGate
{
    public function send(WhatsAppMessage $message, string $number): WhatsAppResponse
    {
        return new WhatsAppResponse(
            status: 'success',
            driver: 'testing',
            data: [
                'number' => $number,
                'message' => 'This is a test message sent via TestingGate.',
            ]
        );
    }

    public function sendMany(WhatsAppMessage $message, string ...$numbers): WhatsAppResponse
    {
        return new WhatsAppResponse(
            status: 'success',
            driver: 'testing',
            data: [
                'numbers' => $numbers,
                'message' => 'This is a test message sent via TestingGate to multiple numbers.',
            ]
        );
    }
}
