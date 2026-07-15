<?php

namespace App\Traits;

use App\Services\Sms\Phone;
use Random\RandomException;

trait OTPGeneration
{
    /**
     * @throws RandomException
     */
    protected function generateOtpForPhone(Phone|string $phone): string
    {
        if (is_string($phone)) {
            $phone = Phone::make($phone);
        }

        if ($phone->toString() === config('sms.test_number') || config('sms.verification_code_all_numbers') == true) {
            return config('sms.verification_code') ?? 1111;
        }

        return random_int(1000, 9999);
    }
}
