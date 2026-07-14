<?php

use Modules\Sms\DTOs\SmsMessage;
use Modules\Sms\Gateways\TestingGateway;

test('testing gateway send always returns success', function () {
    $result = app(TestingGateway::class)->send(
        SmsMessage::otp('1234'),
        '966555338296',
    );

    expect($result->isSuccessful())->toBeTrue()
        ->and($result->driver)->toBe('testing')
        ->and($result->status)->toBe('success');
});

test('testing gateway sendMany always returns success', function () {
    $result = app(TestingGateway::class)->sendMany(
        new SmsMessage(body: 'Hello'),
        '966555338296',
        '966555000000',
    );

    expect($result->isSuccessful())->toBeTrue()
        ->and($result->driver)->toBe('testing')
        ->and($result->data['numbers'])->toBe(['966555338296', '966555000000']);
});

test('testing gateway result includes message and number in data', function () {
    $result = app(TestingGateway::class)->send(
        SmsMessage::otp('1234'),
        '966555338296',
    );

    expect($result->data)->toBe([
        'number' => '966555338296',
        'message' => 'This is a test message sent via TestingGate.',
    ]);
});
