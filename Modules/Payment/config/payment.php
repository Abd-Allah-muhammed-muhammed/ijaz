<?php

use Modules\Payment\Gateways\PayTabsGateway;
use Modules\Payment\Gateways\RajhiGateway;
use Modules\Payment\Gateways\TestingGateway;

return [

    'default' => env('PAYMENT_DRIVER', 'rajhi'),

    'gateways' => [
        'paytabs' => PayTabsGateway::class,
        'testing' => TestingGateway::class,
        'rajhi' => RajhiGateway::class,
    ],

    'drivers' => [

        'paytabs' => [
            'mode' => env('PAYTABS_MODE', 'test'),

            'test' => [
                'profile_id' => env('PAYTABS_TEST_PROFILE_ID'),
                'server_key' => env('PAYTABS_TEST_SERVER_KEY'),
                'client_key' => env('PAYTABS_TEST_CLIENT_KEY'),
                'currency' => env('PAYTABS_TEST_CURRENCY', 'SAR'),
                'region' => env('PAYTABS_TEST_REGION', 'SAU'),
                'endpoint' => 'https://secure-egypt.paytabs.com',
            ],

            'live' => [
                'profile_id' => env('PAYTABS_LIVE_PROFILE_ID'),
                'server_key' => env('PAYTABS_LIVE_SERVER_KEY'),
                'client_key' => env('PAYTABS_LIVE_CLIENT_KEY'),
                'currency' => env('PAYTABS_LIVE_CURRENCY', 'SAR'),
                'region' => env('PAYTABS_LIVE_REGION', 'SAU'),
                'endpoint' => 'https://secure.paytabs.com',
            ],
        ],

        'rajhi' => [
            'mode' => env('RAJHI_MODE', 'test'),

            'test' => [
                'tranportal_id' => env('RAJHI_TEST_TRANPORTAL_ID'),
                'tranportal_password' => env('RAJHI_TEST_TRANPORTAL_PASSWORD'),
                'resource_key' => env('RAJHI_TEST_RESOURCE_KEY'),
                'currency' => env('RAJHI_TEST_CURRENCY', '682'),
                'encryption_iv' => env('RAJHI_TEST_ENCRYPTION_IV', 'PGKEYENCDECIVSPC'),
                'endpoint' => 'https://securepayments.neoleap.com.sa/pg/payment/hosted.htm',
            ],

            'live' => [
                'tranportal_id' => env('RAJHI_LIVE_TRANPORTAL_ID'),
                'tranportal_password' => env('RAJHI_LIVE_TRANPORTAL_PASSWORD'),
                'resource_key' => env('RAJHI_LIVE_RESOURCE_KEY'),
                'currency' => env('RAJHI_LIVE_CURRENCY', '682'),
                'encryption_iv' => env('RAJHI_LIVE_ENCRYPTION_IV', 'PGKEYENCDECIVSPC'),
                'endpoint' => 'https://securepayments.neoleap.com.sa/pg/payment/hosted.htm',
            ],
        ],

    ],

];
