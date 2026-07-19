<?php

namespace App\Exceptions\Auth;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class OtpCooldownException extends ValidationException
{
    public static function forSeconds(int $secondsRemaining, string $field = 'phone'): self
    {
        $validator = Validator::make([], []);

        $validator->errors()->add(
            $field,
            __('auth.otp_throttle', [
                'seconds' => $secondsRemaining,
            ])
        );

        return new self($validator);
    }
}
