<?php

return [
    'driver' => env('SMS_DRIVER', 'testing'),
    'drivers' => [
        'authentica' => [
            'api_key' => env('SMS_AUTHENTICA_API_KEY', ''),
            'template_id' => env('SMS_AUTHENTICA_TEMPLATE_ID', ''),
            'app_name' => env('SMS_AUTHENTICA_APP_NAME', ''),
        ],
        'testing' => [
            // No configuration needed for testing driver
        ],
    ],
    'test_number' => env('SMS_TEST_NUMBER', '966555338296'),
];
