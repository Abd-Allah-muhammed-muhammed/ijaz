<?php

namespace Lib\SMS\Gates;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Lib\SMS\Contracts\ISMSGate;
use Lib\SMS\DTOs\SMSMessage;
use Lib\SMS\DTOs\SMSResponse;

class AuthenticaGate implements ISMSGate
{
    /**
     * @throws ConnectionException
     */
    public function send(SMSMessage $message, string $number): SMSResponse
    {
        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'X-Authorization' => config('sms.drivers.authentica.api_key'),
            'Content-Type' => 'application/json',
        ])
            ->post('https://api.authentica.sa/api/v2/send-otp', [
                'template_id' => config('sms.drivers.authentica.template_id'),
                'phone' => $number,
                'message' => $message->getText(),
                'method' => 'sms',
                'otp' => $message->getOtp(),
                'app_name' => config('sms.drivers.authentica.app_name'),
            ]);
        $response = $response->json();

        return new SMSResponse(
            status: $response['success'] ? 'success' : 'failed',
            driver: 'authentica',
            message: $response['message'],
            data: [
                'phone' => $number,
                'response' => $response,
                'message' => $message->toArray(),
            ],
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
