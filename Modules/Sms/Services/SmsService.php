<?php

namespace Modules\Sms\Services;

use Modules\Sms\Contracts\SmsGatewayInterface;
use Modules\Sms\DTOs\SmsMessage;
use Modules\Sms\DTOs\SmsResult;
use RuntimeException;

class SmsService
{
    /**
     * Send a message to a single number using the given driver
     * (or the configured default if none is specified).
     */
    public function send(SmsMessage $message, string $number, ?string $driver = null): SmsResult
    {
        $driver = $driver ?? $this->getDefaultDriver();
        $gateway = $this->resolveGateway($driver);

        return $gateway->send($message, $number);
    }

    /**
     * Send an OTP code to a single number using the given driver
     * (or the configured default if none is specified).
     *
     * Convenience wrapper around send() for the common OTP case — callers
     * don't need to know about SmsMessage's construction.
     */
    public function sendOtp(string $code, string $number, ?string $driver = null): SmsResult
    {
        return $this->send(SmsMessage::otp($code), $number, $driver);
    }

    /**
     * Send a message to multiple numbers using the given driver
     * (or the configured default if none is specified).
     */
    public function sendMany(SmsMessage $message, array $numbers, ?string $driver = null): SmsResult
    {
        $driver = $driver ?? $this->getDefaultDriver();
        $gateway = $this->resolveGateway($driver);

        return $gateway->sendMany($message, ...$numbers);
    }

    /**
     * Resolve the gateway instance by driver name.
     */
    public function resolveGateway(string $driver): SmsGatewayInterface
    {
        $gateways = config('sms.gateways', []);

        if (! array_key_exists($driver, $gateways)) {
            throw new RuntimeException("Unsupported SMS driver: [{$driver}]");
        }

        return app($gateways[$driver]);
    }

    /**
     * Return the default driver from config.
     */
    public function getDefaultDriver(): string
    {
        return config('sms.default', 'testing');
    }
}
