<?php

use Modules\Sms\Gateways\AuthenticaGateway;
use Modules\Sms\Gateways\OrbitGateway;
use Modules\Sms\Gateways\TestingGateway;

return [

    'default' => env('SMS_DRIVER', 'testing'),

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

        // Orbit gateway config block — filled with real keys in a later step
        // when we build OrbitGateway itself. Leave placeholders here for now
        // so the shape is complete, but don't wire real credentials/logic yet.
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
