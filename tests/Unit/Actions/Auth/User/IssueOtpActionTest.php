<?php

use App\Actions\Auth\User\IssueOtpAction;
use App\Exceptions\Auth\OtpCooldownException;
use App\Models\User;
use App\Services\Sms\Phone;
use Illuminate\Support\Facades\RateLimiter;

test('IssueOtpAction throws cooldown exception on rapid repeat calls', function () {
    $phone = Phone::make('512345678')->toString();
    $user = User::factory()->create(['phone' => $phone]);
    RateLimiter::clear('otp-send:'.$phone);

    $action = app(IssueOtpAction::class);
    $action->handle($user, 'email');

    expect(fn () => $action->handle($user, 'phone'))
        ->toThrow(OtpCooldownException::class);

    expect($user->verificationCodes()->where('type', 'email')->exists())->toBeTrue()
        ->and($user->verificationCodes()->where('type', 'phone')->exists())->toBeFalse();

    RateLimiter::clear('otp-send:'.$phone);
});
