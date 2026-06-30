<?php

namespace Modules\Payment\Actions;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Payment\Models\Payment;
use Modules\Payment\Services\RajhiEncryptionService;

class InitiateRajhiPaymentAction
{
    public function __construct(
        private readonly RajhiEncryptionService $encryption,
    ) {}

    public function handle(Payment $payment): PaymentInitResult
    {
        $config = $this->getConfig();

        // Build plain request as per Neoleap docs
        $plain = [
            'id' => $config['tranportal_id'],
            'password' => $config['tranportal_password'],
            'action' => '1',  // 1 = Purchase
            'currencyCode' => $config['currency'],
            'amt' => number_format($payment->amount, 2, '.', ''),
            'trackId' => $payment->id,
            'responseURL' => route('payment.redirect', [
                'driver' => 'rajhi',
                'payment' => $payment->id,
            ]),
            'errorURL' => route('payment.failed', $payment),
        ];

        $payment->update(['request' => $plain]);

        // Encrypt plain request to trandata
        $trandata = $this->encryption->encrypt($plain);

        // Build encrypted request — Neoleap format: array wrapper [{}]
        $request = [
            [
                'id' => $config['tranportal_id'],
                'trandata' => $trandata,
                'responseURL' => $plain['responseURL'],
                'errorURL' => $plain['errorURL'],
            ],
        ];

        try {
            $customerIp = $this->resolveCustomerIp();

            $http = Http::timeout(30)
                ->withHeader('Content-Type', 'application/json')
                ->withHeader('X-FORWARDED-FOR', $customerIp);

            // Disable SSL verification in local/testing environments only
            if (app()->environment(['local', 'testing'])) {
                $http = $http->withoutVerifying();
            }

            $response = $http->post($config['endpoint'], $request);

            if (! $response->successful()) {
                return new PaymentInitResult(
                    status: 'failed',
                    driver: 'rajhi',
                    url: '',
                    payable: false,
                    message: 'Neoleap gateway error: '.$response->status(),
                );
            }

            // Response: [{"result": "PaymentID:URL", "status": "1"}]
            $body = $response->json();
            $first = is_array($body) ? ($body[0] ?? $body) : $body;

            if (($first['status'] ?? null) !== '1') {
                return new PaymentInitResult(
                    status: 'failed',
                    driver: 'rajhi',
                    url: '',
                    payable: false,
                    message: 'Neoleap rejected the request: '.($first['result'] ?? 'unknown'),
                );
            }

            // Extract PaymentID from result "PaymentID:URL"
            $result = $first['result'] ?? '';
            $parts = explode(':', $result, 2);
            $paymentId = $parts[0] ?? '';
            $paymentUrl = $parts[1] ?? '';

            $redirectUrl = trim($paymentUrl).'?PaymentID='.$paymentId;

            $payment->update([
                'url' => $redirectUrl,
                'transaction_id' => $paymentId,
            ]);

            return new PaymentInitResult(
                status: 'success',
                driver: 'rajhi',
                url: $redirectUrl,
                payable: true,
                transactionId: $paymentId,
            );
        } catch (ConnectionException $e) {
            return new PaymentInitResult(
                status: 'failed',
                driver: 'rajhi',
                url: '',
                payable: false,
                message: 'Connection error: '.$e->getMessage(),
            );
        }
    }

    private function getConfig(): array
    {
        $mode = config('payment.drivers.rajhi.mode', 'test');

        return config("payment.drivers.rajhi.{$mode}", []);
    }

    private function resolveCustomerIp(): string
    {
        $forwardedFor = request()->header('X-Forwarded-For');

        if (is_string($forwardedFor) && $forwardedFor !== '') {
            return trim(explode(',', $forwardedFor)[0]);
        }

        return request()->ip();
    }
}
