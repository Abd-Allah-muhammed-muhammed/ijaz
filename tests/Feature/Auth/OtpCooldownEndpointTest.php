<?php

use App\Actions\Auth\EnsureOtpCooldownAction;
use App\Models\Nationality;
use App\Services\Sms\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Storage;

test('register endpoint surfaces otp cooldown as 422 validation error, not generic 400', function () {
    Storage::fake('local');

    $phone = Phone::make('512345678')->toString();
    RateLimiter::clear('otp-send:'.$phone);
    app(EnsureOtpCooldownAction::class)->recordSent($phone);

    $nationality = Nationality::query()->create([
        'code' => 'SA',
        'is_active' => true,
    ]);

    $this->postJson('/api/v1/user/auth/register', [
        'f_name' => 'Jane',
        'l_name' => 'Doe',
        'email' => 'cooldown@example.com',
        'phone' => '512345678',
        'nationality_id' => $nationality->id,
        'image' => UploadedFile::fake()->image('avatar.jpg'),
        'latitude' => '10',
        'longitude' => '20',
        'password' => null,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('phone');

    RateLimiter::clear('otp-send:'.$phone);
});

test('provider web otp endpoint redirects back with flashed cooldown validation error', function () {
    $phone = Phone::make('512345679')->toString();
    RateLimiter::clear('otp-send:'.$phone);
    app(EnsureOtpCooldownAction::class)->recordSent($phone);

    $this->from(route('auth.register'))
        ->post(route('auth.register.otp'), [
            'phone' => '512345679',
        ])
        ->assertRedirect(route('auth.register'))
        ->assertSessionHasErrors('phone');

    RateLimiter::clear('otp-send:'.$phone);
});
