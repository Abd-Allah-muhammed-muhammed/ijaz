<?php

use App\Actions\Auth\User\RegisterUserAction;
use App\Actions\Auth\User\SendLoginOtpAction;
use App\Contracts\Auth\UserRepositoryInterface;
use App\Models\User;

final class RegisterPasswordCapture
{
    /** @var list<string> */
    public array $values = [];
}

beforeEach(function () {
    $capture = new RegisterPasswordCapture;
    app()->instance(RegisterPasswordCapture::class, $capture);

    $repository = Mockery::mock(UserRepositoryInterface::class);
    $repository->shouldReceive('create')
        ->andReturnUsing(function (array $data) use ($capture): User {
            $capture->values[] = $data['password'];

            return User::query()->create($data);
        });
    app()->instance(UserRepositoryInterface::class, $repository);

    $sendLoginOtpAction = Mockery::mock(SendLoginOtpAction::class);
    $sendLoginOtpAction->shouldReceive('handle')->zeroOrMoreTimes();
    app()->instance(SendLoginOtpAction::class, $sendLoginOtpAction);
});

function registerUserActionPayload(string $email, string $phone, ?string $password = null): array
{
    return [
        'f_name' => 'Jane',
        'l_name' => 'Doe',
        'email' => $email,
        'phone' => $phone,
        'nationality_id' => null,
        'latitude' => '10',
        'longitude' => '20',
        'password' => $password,
    ];
}

test('register generates a random password when none is provided', function () {
    $action = app(RegisterUserAction::class);
    $capture = app(RegisterPasswordCapture::class);

    $action->handle(registerUserActionPayload('first-random@example.com', '512345678'));
    $action->handle(registerUserActionPayload('second-random@example.com', '512345679'));

    expect($capture->values)->toHaveCount(2)
        ->and($capture->values[0])->not->toBe($capture->values[1]);
});

test('generated password is not equal to the phone number', function () {
    $capture = app(RegisterPasswordCapture::class);

    app(RegisterUserAction::class)->handle(
        registerUserActionPayload('not-phone@example.com', '512345678')
    );

    expect($capture->values[0])
        ->not->toBe('512345678')
        ->not->toBe('966512345678');
});

test('generated password meets a minimum length and entropy expectation', function () {
    $capture = app(RegisterPasswordCapture::class);

    app(RegisterUserAction::class)->handle(
        registerUserActionPayload('strong-random@example.com', '512345678')
    );

    expect($capture->values[0])
        ->toHaveLength(32)
        ->toMatch('/^[A-Za-z0-9]{32}$/');
});

test('register still uses the provided password when one is given', function () {
    $capture = app(RegisterPasswordCapture::class);

    app(RegisterUserAction::class)->handle(
        registerUserActionPayload('provided@example.com', '512345678', 'provided-secret')
    );

    expect($capture->values[0])->toBe('provided-secret');
});
