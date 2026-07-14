<?php

use Carbon\Carbon;
use Modules\Sms\DTOs\SmsMessage;
use Modules\Sms\Enums\SmsMessageType;

test('otp shorthand builds a message with body only', function () {
    $message = SmsMessage::otp('1234');

    expect($message->body)->toBe('1234')
        ->and($message->senderName)->toBeNull()
        ->and($message->scheduledAt)->toBeNull();
});

test('otp shorthand sets type to Otp', function () {
    expect(SmsMessage::otp('1234')->type)->toBe(SmsMessageType::Otp);
});

test('default type is Custom when not specified', function () {
    expect(new SmsMessage(body: 'Hello')->type)->toBe(SmsMessageType::Custom);
});

test('isScheduled returns true when scheduledAt is set', function () {
    $message = new SmsMessage(
        body: 'Hello',
        scheduledAt: Carbon::parse('2026-07-14T15:00:00+03:00'),
    );

    expect($message->isScheduled())->toBeTrue();
});

test('isScheduled returns false when scheduledAt is null', function () {
    $message = new SmsMessage(body: 'Hello');

    expect($message->isScheduled())->toBeFalse();
});

test('toArray includes all fields correctly formatted', function () {
    $scheduledAt = Carbon::parse('2026-07-14T15:00:00+03:00');
    $message = new SmsMessage(
        body: 'Hello',
        senderName: 'Ijaz',
        scheduledAt: $scheduledAt,
    );

    expect($message->toArray())->toBe([
        'body' => 'Hello',
        'sender_name' => 'Ijaz',
        'scheduled_at' => $scheduledAt->toIso8601String(),
        'type' => 'custom',
    ]);
});

test('toArray includes type value', function () {
    expect(SmsMessage::otp('9999')->toArray()['type'])->toBe('otp')
        ->and((new SmsMessage(body: 'Hi'))->toArray()['type'])->toBe('custom');
});
