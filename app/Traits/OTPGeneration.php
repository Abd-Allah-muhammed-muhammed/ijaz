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
        if ($phone->toString() === config('sms.test_number')) {
            return '4444';
        }

        return $this->generateOtp();
    }

    /**
     * @throws RandomException
     */
    protected function generateOtp(): string
    {
        $codeConfig = config('verification_code.code');
        if ($codeConfig === 'random') {
            return random_int(1000, 9999);
        }

        return $codeConfig;
    }
}
