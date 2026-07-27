<?php

namespace App\Enums\Auth;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\Stringable;

enum OtpPurposeEnum: string
{
    use Collectable, Stringable;

    case Login = 'login';
    case Phone = 'phone';
    case Email = 'email';
    case Password = 'password';
    case PasswordReset = 'password_reset';
    case ProviderRegistration = 'provider_registration';

    public function ttlMinutes(): int
    {
        $ttl = config('otp.ttl_minutes.'.$this->value);

        if ($ttl !== null) {
            return (int) $ttl;
        }

        return (int) config('otp.ttl_minutes.default', 30);
    }

    /**
     * Purposes accepted by the authenticated user OTP send/verify API.
     *
     * @return list<string>
     */
    public static function userApiValues(): array
    {
        return [
            self::Email->value,
            self::Password->value,
            self::Login->value,
            self::PasswordReset->value,
            self::Phone->value,
        ];
    }
}
