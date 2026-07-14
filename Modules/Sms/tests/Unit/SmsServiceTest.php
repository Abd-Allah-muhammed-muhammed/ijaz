<?php

use Modules\Sms\DTOs\SmsMessage;
use Modules\Sms\DTOs\SmsResult;
use Modules\Sms\Gateways\AuthenticaGateway;
use Modules\Sms\Gateways\TestingGateway;
use Modules\Sms\Services\SmsService;

test('resolveGateway returns testing gateway instance for testing driver', function () {
    $gateway = app(SmsService::class)->resolveGateway('testing');

    expect($gateway)->toBeInstanceOf(TestingGateway::class);
});

test('resolveGateway returns authentica gateway instance for authentica driver', function () {
    $gateway = app(SmsService::class)->resolveGateway('authentica');

    expect($gateway)->toBeInstanceOf(AuthenticaGateway::class);
});

test('resolveGateway throws RuntimeException for unknown driver', function () {
    expect(fn () => app(SmsService::class)->resolveGateway('unknown-driver'))
        ->toThrow(RuntimeException::class, 'Unsupported SMS driver: [unknown-driver]');
});

test('getDefaultDriver returns config value', function () {
    config(['sms.default' => 'authentica']);

    expect(app(SmsService::class)->getDefaultDriver())->toBe('authentica');
});

test('getDefaultDriver falls back to testing when config missing', function () {
    $sms = config('sms');
    unset($sms['default']);
    config(['sms' => $sms]);

    expect(app(SmsService::class)->getDefaultDriver())->toBe('testing');
});

test('send uses default driver when none specified', function () {
    config(['sms.default' => 'testing']);

    $result = app(SmsService::class)->send(SmsMessage::otp('1234'), '966555338296');

    expect($result->driver)->toBe('testing')
        ->and($result->isSuccessful())->toBeTrue();
});

test('send uses explicit driver when specified, overriding default', function () {
    config(['sms.default' => 'authentica']);

    $result = app(SmsService::class)->send(
        SmsMessage::otp('1234'),
        '966555338296',
        'testing',
    );

    expect($result->driver)->toBe('testing')
        ->and($result->isSuccessful())->toBeTrue();
});

test('sendMany uses default driver when none specified', function () {
    config(['sms.default' => 'testing']);

    $result = app(SmsService::class)->sendMany(
        SmsMessage::otp('1234'),
        ['966555338296', '966555000000'],
    );

    expect($result->driver)->toBe('testing')
        ->and($result->isSuccessful())->toBeTrue()
        ->and($result->data['numbers'])->toBe(['966555338296', '966555000000']);
});

test('send returns SmsResult from the resolved gateway', function () {
    config(['sms.default' => 'testing']);

    $result = app(SmsService::class)->send(SmsMessage::otp('1234'), '966555338296');

    expect($result)->toBeInstanceOf(SmsResult::class)
        ->and($result->toArray())->toMatchArray([
            'status' => 'success',
            'driver' => 'testing',
        ]);
});
