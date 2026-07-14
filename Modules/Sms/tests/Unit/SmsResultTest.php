<?php

use Modules\Sms\DTOs\SmsResult;

test('isSuccessful returns true when status is success', function () {
    $result = new SmsResult(status: 'success', driver: 'testing');

    expect($result->isSuccessful())->toBeTrue();
});

test('isSuccessful returns false for any other status', function () {
    $result = new SmsResult(status: 'failed', driver: 'testing', message: 'blocked');

    expect($result->isSuccessful())->toBeFalse();
});

test('toArray includes all fields', function () {
    $result = new SmsResult(
        status: 'success',
        driver: 'testing',
        message: 'ok',
        data: ['number' => '966555338296'],
    );

    expect($result->toArray())->toBe([
        'status' => 'success',
        'driver' => 'testing',
        'message' => 'ok',
        'data' => ['number' => '966555338296'],
    ]);
});
