<?php

use App\Contracts\Auth\UserRepositoryInterface;
use App\Models\User;
use App\Services\Sms\Phone;

test('findByPhone returns matching user', function () {
    $phone = Phone::make('512345678')->toString();
    $user = User::factory()->create(['phone' => $phone]);

    $found = app(UserRepositoryInterface::class)->findByPhone($phone);

    expect($found)->not->toBeNull()
        ->and($found->is($user))->toBeTrue();
});

test('findByPhone returns null when no match', function () {
    User::factory()->create(['phone' => Phone::make('512345678')->toString()]);

    expect(app(UserRepositoryInterface::class)->findByPhone('000000000'))->toBeNull();
});

test('create persists and returns a new user', function () {
    $user = app(UserRepositoryInterface::class)->create([
        'f_name' => 'Jane',
        'l_name' => 'Doe',
        'email' => 'jane.repo@example.com',
        'phone' => Phone::make('512345678')->toString(),
        'password' => 'secret',
    ]);

    expect($user->exists)->toBeTrue()
        ->and(User::whereKey($user->getKey())->exists())->toBeTrue();
});
