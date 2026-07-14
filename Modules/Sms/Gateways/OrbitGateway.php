<?php

namespace Modules\Sms\Gateways;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Modules\Sms\Contracts\SmsGatewayInterface;
use Modules\Sms\DTOs\SmsMessage;
use Modules\Sms\DTOs\SmsResult;

class OrbitGateway implements SmsGatewayInterface
{
    public function send(SmsMessage $message, string $number): SmsResult
    {
        $response = $this->client()->post('/api/v1/send', $this->buildPayload($message, [
            'number' => $number,
        ]));

        return $this->toResult($response);
    }

    public function sendMany(SmsMessage $message, string ...$numbers): SmsResult
    {
        $response = $this->client()->post('/api/v1/send-bulk', $this->buildPayload($message, [
            'numbers' => $numbers,
        ]));

        return $this->toResult($response);
    }

    /**
     * Orbit-specific: check account balance. Not part of SmsGatewayInterface
     * since it's not a universal SMS-gateway concern, but useful to expose
     * on the gateway itself.
     */
    public function getBalance(): SmsResult
    {
        $response = $this->client()->post('/api/v1/get-balance');

        return $this->toResult($response);
    }

    private function client(): PendingRequest
    {
        $config = config('sms.drivers.orbit', []);

        return Http::baseUrl($config['endpoint'] ?? 'https://app.mobile.net.sa')
            ->withToken($config['api_token'] ?? '')
            ->acceptJson();
    }

    /**
     * @param  array<string, mixed>  $recipientFields
     * @return array<string, mixed>
     */
    private function buildPayload(SmsMessage $message, array $recipientFields): array
    {
        $config = config('sms.drivers.orbit', []);

        $payload = [
            ...$recipientFields,
            'senderName' => $message->senderName ?? ($config['sender_name'] ?? null),
            'sendAtOption' => $message->isScheduled() ? 'Later' : 'Now',
            'messageBody' => $message->body,
            'allow_duplicate' => true,
        ];

        if ($message->isScheduled()) {
            $payload['sendAt'] = $message->scheduledAt->format('Y-m-d h:i a');
        }

        return $payload;
    }

    private function toResult(Response $response): SmsResult
    {
        $body = $response->json() ?? [];

        if ($response->failed() || ($body['status'] ?? null) !== 'Success') {
            return new SmsResult(
                status: 'failed',
                driver: 'orbit',
                message: $body['message'] ?? 'Unknown Orbit error',
                data: $body,
            );
        }

        return new SmsResult(
            status: 'success',
            driver: 'orbit',
            message: $body['message'] ?? '',
            data: $body['data'] ?? [],
        );
    }
}
