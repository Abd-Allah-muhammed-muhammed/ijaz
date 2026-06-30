<?php

namespace Modules\Payment\DTOs;

use Modules\Payment\Models\Payment;

final readonly class PaymentDebugData
{
    public function __construct(
        public string $driver,
        public string $status,
        public ?array $request,
        public ?array $response,
        public array $meta,
    ) {}

    public static function fromPayment(Payment $payment): self
    {
        $driver = $payment->driver;

        return new self(
            driver: $driver,
            status: $payment->status->value,
            request: self::mask($payment->request, $driver),
            response: self::mask($payment->response, $driver),
            meta: [
                'id' => $payment->id,
                'transaction_id' => $payment->transaction_id,
                'amount' => $payment->amount,
                'message' => $payment->message,
                'url' => $payment->url,
                'created_at' => $payment->created_at?->toIso8601String(),
                'updated_at' => $payment->updated_at?->toIso8601String(),
            ],
        );
    }

    private static function mask(?array $data, string $driver): ?array
    {
        if ($data === null) {
            return null;
        }

        $sensitiveKeys = self::sensitiveKeysFor($driver);

        return self::maskRecursive($data, $sensitiveKeys);
    }

    private static function maskRecursive(array $data, array $sensitiveKeys): array
    {
        $result = [];

        foreach ($data as $key => $value) {
            $lowerKey = is_string($key) ? strtolower($key) : $key;

            if (is_array($value)) {
                $result[$key] = self::maskRecursive($value, $sensitiveKeys);

                continue;
            }

            if (in_array($lowerKey, $sensitiveKeys, true)) {
                $result[$key] = self::maskValue($value);

                continue;
            }

            $result[$key] = $value;
        }

        return $result;
    }

    private static function maskValue(mixed $value): string
    {
        if (! is_string($value) || $value === '') {
            return '***';
        }

        $visible = min(4, (int) floor(strlen($value) / 4));

        return $visible > 0
            ? substr($value, 0, $visible).str_repeat('*', max(3, strlen($value) - $visible))
            : '***';
    }

    /**
     * @return list<string>
     */
    private static function sensitiveKeysFor(string $driver): array
    {
        $common = ['password', 'secret', 'token', 'authorization'];

        return match ($driver) {
            'rajhi' => [...$common, 'password', 'id', 'trandata', 'card', 'cvv2', 'resource_key', 'tranportal_password'],
            'paytabs' => [...$common, 'server_key', 'client_key', 'profile_id', 'card_number', 'cvv'],
            default => $common,
        };
    }
}
