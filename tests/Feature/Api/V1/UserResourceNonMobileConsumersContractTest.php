<?php

use App\Models\User;
use Modules\Classifieds\Http\Resources\Api\CarAdvisementResource;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Wallet\Http\Resources\TopUpResource;
use Modules\Wallet\Http\Resources\WithdrawRequestResource;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Models\WithdrawRequest;

/**
 * Contract-freeze tests for the FLAT UserResource duplicate
 * (App\Http\Resources\Api\V1\UserResource) as consumed by non-mobile
 * resources (Wallet top-up/withdraw, Classifieds advisements) via their
 * nested `user` key — a before-state lock prior to consolidating the two
 * UserResource duplicates.
 */

/** @return list<string> Exact flat UserResource keys when `nationality` is not eager loaded. */
function flatUserResourceKeys(): array
{
    return [
        'id', 'socket_id', 'name', 'f_name', 'l_name', 'phone', 'image',
        'language', 'latitude', 'longitude', 'email', 'nationality_id',
    ];
}

test('wallet TopUpResource freezes nested flat UserResource output', function () {
    $topUp = TopUpRequest::factory()->create();
    $topUp->load('user');

    // JsonResource::withoutWrapping() is set globally (AppServiceProvider),
    // so the serialized payload is the resource itself — no `data` wrapper.
    $payload = TopUpResource::make($topUp)->response()->getData(true);

    expect(array_keys($payload))->toBe([
        'id', 'user', 'amount', 'status', 'payment_method', 'payment_status',
        'admin_notes', 'transaction_image', 'user_notes', 'created_at',
    ]);

    // Nested user: flat UserResource, no nationality eager load on this path.
    expect(array_keys($payload['user']))->toBe(flatUserResourceKeys())
        ->and($payload['user']['id'])->toBe($topUp->user->id)
        ->and($payload['user']['socket_id'])->toBe('user-'.$topUp->user->id)
        ->and($payload['user']['name'])->toBe($topUp->user->f_name.' '.$topUp->user->l_name)
        ->and($payload['user']['image'])->toBe($topUp->user->image_url);
});

test('wallet WithdrawRequestResource freezes nested flat UserResource output', function () {
    $withdraw = WithdrawRequest::factory()->create();
    $withdraw->load('user');

    $payload = WithdrawRequestResource::make($withdraw)->response()->getData(true);

    expect(array_keys($payload))->toBe([
        'id', 'user', 'amount', 'status', 'admin_notes', 'user_notes', 'created_at',
    ]);

    expect(array_keys($payload['user']))->toBe(flatUserResourceKeys())
        ->and($payload['user']['id'])->toBe($withdraw->user->id)
        ->and($payload['user']['socket_id'])->toBe('user-'.$withdraw->user->id)
        ->and($payload['user']['name'])->toBe($withdraw->user->f_name.' '.$withdraw->user->l_name);
});

test('classifieds CarAdvisementResource freezes nested flat UserResource output', function () {
    $car = CarAdvisement::factory()->create([
        'user_type' => User::class,
        'user_id' => User::factory(),
    ]);
    $car->load('user');

    $payload = CarAdvisementResource::make($car)->response()->getData(true);

    expect($payload)->toHaveKey('user')
        ->and(array_keys($payload['user']))->toBe(flatUserResourceKeys())
        ->and($payload['user']['id'])->toBe($car->user->id)
        ->and($payload['user']['socket_id'])->toBe('user-'.$car->user->id)
        ->and($payload['user']['name'])->toBe($car->user->f_name.' '.$car->user->l_name)
        ->and($payload['user']['nationality_id'])->toBeNull();
});
