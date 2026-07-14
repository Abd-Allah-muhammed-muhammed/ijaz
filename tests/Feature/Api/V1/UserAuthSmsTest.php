<?php

use App\Models\User;
use App\Services\Sms\Phone;
use Modules\Sms\DTOs\SmsMessage;
use Modules\Sms\DTOs\SmsResult;
use Modules\Sms\Services\SmsService;

beforeEach(function () {
    config(['sms.default' => 'testing']);
});

test('api login sends otp via SmsService', function () {
    $normalized = Phone::make('512345678')->toString();
    User::factory()->create(['phone' => $normalized]);

    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('send')
        ->once()
        ->withArgs(function (SmsMessage $message, string $number) use ($normalized) {
            return $number === $normalized && $message->body !== '';
        })
        ->andReturn(new SmsResult(status: 'success', driver: 'testing'));
    app()->instance(SmsService::class, $sms);

    $this->postJson('/api/v1/user/auth/login', ['phone' => '512345678'])
        ->assertSuccessful();
});

test('provider register otp sends via SmsService', function () {
    $sms = Mockery::mock(SmsService::class);
    $sms->shouldReceive('send')
        ->once()
        ->withArgs(function (SmsMessage $message, string $number) {
            return $number === Phone::make('512345679')->toString() && $message->body !== '';
        })
        ->andReturn(new SmsResult(status: 'success', driver: 'testing'));
    app()->instance(SmsService::class, $sms);

    $this->postJson(route('auth.register.otp'), ['phone' => '512345679'])
        ->assertSuccessful();
});
