<?php

namespace Modules\Sms\Gateways;

use Modules\Sms\Contracts\SmsGatewayInterface;
use Modules\Sms\DTOs\SmsMessage;
use Modules\Sms\DTOs\SmsResult;

class TestingGateway implements SmsGatewayInterface
{
    public function send(SmsMessage $message, string $number): SmsResult
    {
        return new SmsResult(
            status: 'success',
            driver: 'testing',
            data: [
                'number' => $number,
                'message' => 'This is a test message sent via TestingGate.',
            ]
        );
    }

    public function sendMany(SmsMessage $message, string ...$numbers): SmsResult
    {
        return new SmsResult(
            status: 'success',
            driver: 'testing',
            data: [
                'numbers' => $numbers,
                'message' => 'This is a test message sent via TestingGate to multiple numbers.',
            ]
        );
    }
}
