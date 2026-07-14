<?php

namespace Modules\Sms\Gateways;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Sms\Contracts\SmsGatewayInterface;
use Modules\Sms\DTOs\SmsMessage;
use Modules\Sms\DTOs\SmsResult;

class AuthenticaGateway implements SmsGatewayInterface
{
    /**
     * @throws ConnectionException
     */
    public function send(SmsMessage $message, string $number): SmsResult
    {
        $config = config('sms.drivers.authentica', []);

        $response = Http::withHeaders([
            'Accept' => 'application/json',
            'X-Authorization' => $config['api_key'] ?? null,
            'Content-Type' => 'application/json',
        ])
            ->post($config['endpoint'] ?? 'https://api.authentica.sa/api/v2/send-otp', [
                'template_id' => $config['template_id'] ?? null,
                'phone' => $number,
                'message' => $message->body,
                'method' => 'sms',
                'otp' => $message->body,
                'app_name' => $config['app_name'] ?? null,
            ]);
        $response = $response->json();

        return new SmsResult(
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
