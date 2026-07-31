<?php

use App\Support\Phone;
use App\Traits\OtpGeneration;

function otpGenerationHarness(): object
{
    return new class
    {
        use OtpGeneration;

        public function generate(Phone|string $phone): string
        {
            return $this->generateOtpForPhone($phone);
        }
    };
}

test('verification_code_all_numbers is force-disabled when app is in production, even if env var is true', function () {
    config([
        'sms.test_number' => '966500000000',
        'sms.verification_code' => '1111',
        'sms.verification_code_all_numbers' => true,
    ]);

    $this->app['env'] = 'production';

    $harness = otpGenerationHarness();
    $realPhone = Phone::make('512345678')->toString();

    expect($realPhone)->not->toBe(config('sms.test_number'));

    $code = $harness->generate($realPhone);

    expect($code)->not->toBe('1111')
        ->and($code)->toMatch('/^\d{4}$/');
});

test('verification_code_all_numbers still applies outside production', function () {
    config([
        'sms.test_number' => '966500000000',
        'sms.verification_code' => '4829',
        'sms.verification_code_all_numbers' => true,
    ]);

    $this->app['env'] = 'local';

    $code = otpGenerationHarness()->generate(Phone::make('512345678'));

    expect($code)->toBe('4829');
});

test('whitelisted test number still receives fixed code in production', function () {
    config([
        'sms.test_number' => '966500000000',
        'sms.verification_code' => '1111',
        'sms.verification_code_all_numbers' => false,
    ]);

    $this->app['env'] = 'production';

    expect(otpGenerationHarness()->generate('966500000000'))->toBe('1111');
});

test('empty SMS_VERIFICATION_CODE falls back to 1111 instead of storing blank token', function () {
    config([
        'sms.test_number' => '966500000000',
        'sms.verification_code' => '',
        'sms.verification_code_all_numbers' => false,
    ]);

    expect(otpGenerationHarness()->generate('966500000000'))->toBe('1111');
});
