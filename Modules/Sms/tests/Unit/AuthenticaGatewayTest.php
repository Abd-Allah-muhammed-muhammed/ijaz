<?php

use Illuminate\Support\Facades\Http;
use Modules\Sms\DTOs\SmsMessage;
use Modules\Sms\Gateways\AuthenticaGateway;

beforeEach(function () {
    config()->set('sms.drivers.authentica', [
        'api_key' => 'test-api-key',
        'template_id' => 'tmpl-1',
        'app_name' => 'Ijaz',
        'endpoint' => 'https://api.authentica.sa/api/v2/send-otp',
    ]);
});

test('authentica gateway sends otp message successfully', function () {
    Http::fake([
        'api.authentica.sa/*' => Http::response([
            'success' => true,
            'message' => 'OTP sent',
        ], 200),
    ]);

    $result = app(AuthenticaGateway::class)->send(
        SmsMessage::otp('1234'),
        '966555338296',
    );

    expect($result->isSuccessful())->toBeTrue()
        ->and($result->driver)->toBe('authentica')
        ->and($result->message)->toBe('OTP sent')
        ->and($result->data['phone'])->toBe('966555338296');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://api.authentica.sa/api/v2/send-otp'
            && $request['otp'] === '1234'
            && $request['phone'] === '966555338296'
            && $request['template_id'] === 'tmpl-1'
            && $request->hasHeader('X-Authorization', 'test-api-key');
    });
});

test('authentica gateway returns failed result on http error', function () {
    Http::fake([
        'api.authentica.sa/*' => Http::response([
            'success' => false,
            'message' => 'Invalid template',
        ], 200),
    ]);

    $result = app(AuthenticaGateway::class)->send(
        SmsMessage::otp('1234'),
        '966555338296',
    );

    expect($result->isSuccessful())->toBeFalse()
        ->and($result->driver)->toBe('authentica')
        ->and($result->message)->toBe('Invalid template')
        ->and($result->status)->toBe('failed');
});

test('authentica gateway sendMany stub returns testing-style success', function () {
    $result = app(AuthenticaGateway::class)->sendMany(
        SmsMessage::otp('1234'),
        '966555338296',
        '966555000000',
    );

    expect($result->isSuccessful())->toBeTrue()
        ->and($result->driver)->toBe('testing')
        ->and($result->data['numbers'])->toBe(['966555338296', '966555000000'])
        ->and($result->data['message'])->toBe('This is a test message sent via TestingGate to multiple numbers.');
});
