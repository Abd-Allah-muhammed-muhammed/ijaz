<?php

use Carbon\Carbon;
use Modules\Sms\DTOs\SmsMessage;

test('otp shorthand builds a message with body only', function () {
    $message = SmsMessage::otp('1234');

    expect($message->body)->toBe('1234')
        ->and($message->senderName)->toBeNull()
        ->and($message->scheduledAt)->toBeNull();
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
    ]);
});
