<?php

use App\Contracts\Auth\AdminRepositoryInterface;
use App\Models\Admin;

test('findAuthenticated returns the currently authenticated admin', function () {
    $admin = Admin::query()->create([
        'name' => 'Auth Admin',
        'phone' => fake()->unique()->numerify('9665########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    $this->actingAs($admin, 'admin');

    $authenticated = app(AdminRepositoryInterface::class)->findAuthenticated();

    expect($authenticated)->not->toBeNull()
        ->and($authenticated->is($admin))->toBeTrue();
});

test('findAuthenticated returns null when no admin is authenticated', function () {
    expect(app(AdminRepositoryInterface::class)->findAuthenticated())->toBeNull();
});
