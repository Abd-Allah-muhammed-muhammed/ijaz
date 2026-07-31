<?php

use Modules\Sms\Gateways\AuthenticaGateway;
use Modules\Sms\Gateways\OrbitGateway;
use Modules\Sms\Gateways\TestingGateway;

return [

    'default' => env('SMS_DRIVER', 'testing'),

    /*
    | Fixed OTP for the configured test number (used by OtpGeneration).
    | SendOtpSmsAction skips the real gateway for this number in EVERY
    | environment (including production) to avoid burning SMS credits.
    */
    'test_number' => env('SMS_TEST_NUMBER', '966555338296'),

    // Empty env values are treated as unset by OtpGeneration (falls back to 1111).
    'verification_code' => env('SMS_VERIFICATION_CODE', 1111),

    /*
    | When true, every phone gets the fixed verification_code (local/testing only).
    | OtpGeneration hard-blocks this in production via app()->isProduction() even
    | if the env var is accidentally left true on a live server.
    */
    'verification_code_all_numbers' => env('SMS_VERIFICATION_CODE_ALL_NUMBERS', false),

    'gateways' => [
        'authentica' => AuthenticaGateway::class,
        'orbit' => OrbitGateway::class,
        'testing' => TestingGateway::class,
    ],

    'drivers' => [

        'authentica' => [
            'api_key' => env('SMS_AUTHENTICA_API_KEY'),
            'template_id' => env('SMS_AUTHENTICA_TEMPLATE_ID'),
            'app_name' => env('SMS_AUTHENTICA_APP_NAME'),
            'endpoint' => env('SMS_AUTHENTICA_ENDPOINT', 'https://api.authentica.sa/api/v2/send-otp'),
        ],

        'orbit' => [
            'api_token' => env('SMS_ORBIT_API_TOKEN'),
            'sender_name' => env('SMS_ORBIT_SENDER_NAME'),
            'endpoint' => env('SMS_ORBIT_ENDPOINT', 'https://app.mobile.net.sa'),
        ],

        'testing' => [
            'test_number' => env('SMS_TEST_NUMBER', '966555338296'),
        ],

    ],

];
