<?php

namespace Lib\SMS\Gates;

use Lib\SMS\Contracts\ISMSGate;
use Lib\SMS\DTOs\SMSMessage;
use Lib\SMS\DTOs\SMSResponse;

class TestingGate implements ISMSGate
{
    public function send(SMSMessage $message, string $number): SMSResponse
    {
        return new SMSResponse(
            status: 'success',
            driver: 'testing',
            data: [
                'number' => $number,
                'message' => 'This is a test message sent via TestingGate.',
            ]
        );
    }

    public function sendMany(SMSMessage $message, string ...$numbers): SMSResponse
    {
        return new SMSResponse(
            status: 'success',
            driver: 'testing',
            data: [
                'numbers' => $numbers,
                'message' => 'This is a test message sent via TestingGate to multiple numbers.',
            ]
        );
    }
}
