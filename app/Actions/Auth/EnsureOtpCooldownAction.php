<?php

namespace App\Actions\Auth;

use App\Exceptions\Auth\OtpCooldownException;
use Illuminate\Support\Facades\RateLimiter;

class EnsureOtpCooldownAction
{
    /**
     * @throws OtpCooldownException
     */
    public function ensure(string $normalizedPhone): void
    {
        $key = $this->key($normalizedPhone);

        if (RateLimiter::tooManyAttempts($key, 1)) {
            throw OtpCooldownException::forSeconds(RateLimiter::availableIn($key));
        }
    }

    public function recordSent(string $normalizedPhone): void
    {
        RateLimiter::hit(
            $this->key($normalizedPhone),
            (int) config('otp.send_cooldown_seconds')
        );
    }

    /**
     * Seconds until a new OTP may be sent for this phone (0 = available now).
     */
    public function availableIn(string $normalizedPhone): int
    {
        return RateLimiter::availableIn($this->key($normalizedPhone));
    }

    private function key(string $normalizedPhone): string
    {
        return 'otp-send:'.$normalizedPhone;
    }
}
