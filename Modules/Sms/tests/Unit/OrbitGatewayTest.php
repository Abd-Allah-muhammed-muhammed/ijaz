<?php

use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Modules\Sms\DTOs\SmsMessage;
use Modules\Sms\Gateways\OrbitGateway;

beforeEach(function () {
    config()->set('sms.drivers.orbit', [
        'api_token' => 'orbit-token',
        'sender_name' => 'IjazDefault',
        'endpoint' => 'https://app.mobile.net.sa',
    ]);
});

test('send posts to /api/v1/send with correct payload shape', function () {
    Http::fake([
        'app.mobile.net.sa/api/v1/send' => Http::response([
            'status' => 'Success',
            'data' => ['message' => ['id' => 1, 'status' => 'pending']],
        ], 200),
    ]);

    $result = app(OrbitGateway::class)->send(
        new SmsMessage(body: 'Hello Orbit'),
        '966555338296',
    );

    expect($result->isSuccessful())->toBeTrue()
        ->and($result->driver)->toBe('orbit');

    Http::assertSent(function ($request) {
        return $request->url() === 'https://app.mobile.net.sa/api/v1/send'
            && $request['number'] === '966555338296'
            && $request['messageBody'] === 'Hello Orbit'
            && $request['allow_duplicate'] === true
            && $request->hasHeader('Authorization', 'Bearer orbit-token');
    });
});

test('send uses default sender name from config when message has none', function () {
    Http::fake([
        'app.mobile.net.sa/*' => Http::response(['status' => 'Success', 'data' => []], 200),
    ]);

    app(OrbitGateway::class)->send(
        new SmsMessage(body: 'Hello'),
        '966555338296',
    );

    Http::assertSent(fn ($request) => $request['senderName'] === 'IjazDefault');
});

test('send uses message-provided sender name when set, overriding config default', function () {
    Http::fake([
        'app.mobile.net.sa/*' => Http::response(['status' => 'Success', 'data' => []], 200),
    ]);

    app(OrbitGateway::class)->send(
        new SmsMessage(body: 'Hello', senderName: 'CustomSender'),
        '966555338296',
    );

    Http::assertSent(fn ($request) => $request['senderName'] === 'CustomSender');
});

test('send sets sendAtOption to Now when message is not scheduled', function () {
    Http::fake([
        'app.mobile.net.sa/*' => Http::response(['status' => 'Success', 'data' => []], 200),
    ]);

    app(OrbitGateway::class)->send(
        new SmsMessage(body: 'Hello'),
        '966555338296',
    );

    Http::assertSent(fn ($request) => $request['sendAtOption'] === 'Now'
        && ! array_key_exists('sendAt', $request->data()));
});

test('send sets sendAtOption to Later and includes formatted sendAt when scheduled', function () {
    Http::fake([
        'app.mobile.net.sa/*' => Http::response(['status' => 'Success', 'data' => []], 200),
    ]);

    $scheduledAt = Carbon::parse('2026-07-14 15:30:00');

    app(OrbitGateway::class)->send(
        new SmsMessage(body: 'Hello', scheduledAt: $scheduledAt),
        '966555338296',
    );

    Http::assertSent(fn ($request) => $request['sendAtOption'] === 'Later'
        && $request['sendAt'] === $scheduledAt->format('Y-m-d h:i a'));
});

test('sendMany posts to /api/v1/send-bulk with numbers array', function () {
    Http::fake([
        'app.mobile.net.sa/api/v1/send-bulk' => Http::response([
            'status' => 'Success',
            'data' => [],
        ], 200),
    ]);

    $result = app(OrbitGateway::class)->sendMany(
        new SmsMessage(body: 'Bulk hello'),
        '966555338296',
        '966555000000',
    );

    expect($result->isSuccessful())->toBeTrue();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://app.mobile.net.sa/api/v1/send-bulk'
            && $request['numbers'] === ['966555338296', '966555000000']
            && $request['messageBody'] === 'Bulk hello';
    });
});

test('getBalance posts to /api/v1/get-balance with no body', function () {
    Http::fake([
        'app.mobile.net.sa/api/v1/get-balance' => Http::response([
            'status' => 'Success',
            'data' => ['balance' => 1050],
        ], 200),
    ]);

    app(OrbitGateway::class)->getBalance();

    Http::assertSent(function ($request) {
        return $request->url() === 'https://app.mobile.net.sa/api/v1/get-balance'
            && $request->method() === 'POST'
            && $request->data() === [];
    });
});

test('getBalance returns success result with balance in data', function () {
    Http::fake([
        'app.mobile.net.sa/*' => Http::response([
            'status' => 'Success',
            'data' => ['balance' => 1050],
        ], 200),
    ]);

    $result = app(OrbitGateway::class)->getBalance();

    expect($result->isSuccessful())->toBeTrue()
        ->and($result->driver)->toBe('orbit')
        ->and($result->data['balance'])->toBe(1050);
});

test('send returns failed result on Unauthenticated response', function () {
    Http::fake([
        'app.mobile.net.sa/*' => Http::response([
            'message' => 'Unauthenticated.',
        ], 401),
    ]);

    $result = app(OrbitGateway::class)->send(
        new SmsMessage(body: 'Hello'),
        '966555338296',
    );

    expect($result->isSuccessful())->toBeFalse()
        ->and($result->driver)->toBe('orbit')
        ->and($result->message)->toBe('Unauthenticated.');
});

test('send returns failed result on non-200 http response', function () {
    Http::fake([
        'app.mobile.net.sa/*' => Http::response([
            'message' => 'Server error',
        ], 500),
    ]);

    $result = app(OrbitGateway::class)->send(
        new SmsMessage(body: 'Hello'),
        '966555338296',
    );

    expect($result->isSuccessful())->toBeFalse()
        ->and($result->status)->toBe('failed')
        ->and($result->message)->toBe('Server error');
});

test('send composes full translated message when type is Otp', function () {
    app()->setLocale('en');

    Http::fake([
        'app.mobile.net.sa/*' => Http::response(['status' => 'Success', 'data' => []], 200),
    ]);

    app(OrbitGateway::class)->send(
        SmsMessage::otp('1234'),
        '966555338296',
    );

    Http::assertSent(fn ($request) => $request['messageBody'] === 'Your verification code is: 1234');
});

test('send uses raw body as-is when type is Custom', function () {
    Http::fake([
        'app.mobile.net.sa/*' => Http::response(['status' => 'Success', 'data' => []], 200),
    ]);

    app(OrbitGateway::class)->send(
        new SmsMessage(body: 'Hello raw custom'),
        '966555338296',
    );

    Http::assertSent(fn ($request) => $request['messageBody'] === 'Hello raw custom');
});

test('send composes Arabic message when app locale is ar', function () {
    app()->setLocale('ar');

    Http::fake([
        'app.mobile.net.sa/*' => Http::response(['status' => 'Success', 'data' => []], 200),
    ]);

    app(OrbitGateway::class)->send(
        SmsMessage::otp('5678'),
        '966555338296',
    );

    Http::assertSent(fn ($request) => $request['messageBody'] === 'رمز التحقق الخاص بك هو: 5678');
});

test('send composes English message when app locale is en', function () {
    app()->setLocale('en');

    Http::fake([
        'app.mobile.net.sa/*' => Http::response(['status' => 'Success', 'data' => []], 200),
    ]);

    app(OrbitGateway::class)->send(
        SmsMessage::otp('9012'),
        '966555338296',
    );

    Http::assertSent(fn ($request) => $request['messageBody'] === 'Your verification code is: 9012');
});
