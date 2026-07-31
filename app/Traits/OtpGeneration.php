<?php

namespace App\Traits;

use App\Support\Phone;
use Random\RandomException;

trait OtpGeneration
{
    /**
     * @throws RandomException
     */
    protected function generateOtpForPhone(Phone|string $phone): string
    {
        if (is_string($phone)) {
            $phone = Phone::make($phone);
        }

        $normalizedPhone = $phone->toString();
        $isWhitelistedTestNumber = filled(config('sms.test_number'))
            && $normalizedPhone === config('sms.test_number');

        // SMS_VERIFICATION_CODE_ALL_NUMBERS must never apply in production,
        // even if the env var is accidentally left true on a live server.
        $allNumbersFixed = config('sms.verification_code_all_numbers') == true
            && ! app()->isProduction();

        if ($isWhitelistedTestNumber || $allNumbersFixed) {
            // Use filled()/?: — `??` does not catch empty-string env values.
            $code = config('sms.verification_code');

            return filled($code) ? (string) $code : '1111';
        }

        return (string) random_int(1000, 9999);
    }
}
