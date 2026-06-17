<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;

test('unauthenticated cannot access guarantor index', function () {
    $this->getJson(route('api.v1.guarantor.guarantor.index'))
        ->assertUnauthorized();
});

test('unauthenticated cannot create guarantor', function () {
    $this->postJson(route('api.v1.guarantor.guarantor.store.individual'))
        ->assertUnauthorized();
});

test('unauthenticated cannot access chat', function () {
    $this->getJson(route('chats.guarantor.index'))
        ->assertUnauthorized();
});

test('guarantor index route resolves correctly', function () {
    expect(Route::has('api.v1.guarantor.guarantor.index'))->toBeTrue()
        ->and(route('api.v1.guarantor.guarantor.index'))->toBe(url('api/v1/guarantor'));
});

test('guarantor store individual route resolves correctly', function () {
    expect(Route::has('api.v1.guarantor.guarantor.store.individual'))->toBeTrue()
        ->and(route('api.v1.guarantor.guarantor.store.individual'))->toBe(url('api/v1/guarantor/individual'));
});

test('guarantor store company route resolves correctly', function () {
    expect(Route::has('api.v1.guarantor.guarantor.store.company'))->toBeTrue()
        ->and(route('api.v1.guarantor.guarantor.store.company'))->toBe(url('api/v1/guarantor/company'));
});

test('guarantor show route resolves correctly', function () {
    $guarantorRequest = GuarantorRequest::factory()->create();

    expect(Route::has('api.v1.guarantor.guarantor.show'))->toBeTrue()
        ->and(route('api.v1.guarantor.guarantor.show', $guarantorRequest))
        ->toBe(url('api/v1/guarantor/'.$guarantorRequest->getKey()));
});

test('installments index route resolves correctly', function () {
    $guarantorRequest = GuarantorRequest::factory()->create();

    expect(Route::has('api.v1.guarantor.guarantor.installments.index'))->toBeTrue()
        ->and(route('api.v1.guarantor.guarantor.installments.index', $guarantorRequest))
        ->toBe(url('api/v1/guarantor/'.$guarantorRequest->getKey().'/installments'));
});

test('chat index route resolves correctly', function () {
    expect(Route::has('chats.guarantor.index'))->toBeTrue()
        ->and(route('chats.guarantor.index'))->toBe(url('api/v1/chats/guarantor'));
});

test('chat store route resolves correctly', function () {
    expect(Route::has('chats.guarantor.store'))->toBeTrue()
        ->and(route('chats.guarantor.store'))->toBe(url('api/v1/chats/guarantor'));
});

test('authenticated user can access guarantor index', function () {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson(route('api.v1.guarantor.guarantor.index'))
        ->assertSuccessful();
});

test('installments pay route resolves correctly', function () {
    $guarantorRequest = GuarantorRequest::factory()->create();
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create();

    expect(Route::has('api.v1.guarantor.guarantor.installments.pay'))->toBeTrue()
        ->and(route('api.v1.guarantor.guarantor.installments.pay', [
            'guarantorRequest' => $guarantorRequest,
            'installment' => $installment,
        ]))
        ->toBe(url('api/v1/guarantor/'.$guarantorRequest->getKey().'/installments/'.$installment->getKey().'/pay'));
});

test('chat show route resolves correctly', function () {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();
    $guarantorRequest = GuarantorRequest::factory()->create();

    $conversation = Conversation::query()->create([
        'user1_id' => $user1->getKey(),
        'user1_type' => User::class,
        'user2_id' => $user2->getKey(),
        'user2_type' => User::class,
        'operation_type' => GuarantorRequest::class,
        'operation_id' => $guarantorRequest->id,
    ]);

    expect(Route::has('chats.guarantor.show'))->toBeTrue()
        ->and(route('chats.guarantor.show', $conversation))
        ->toBe(url('api/v1/chats/guarantor/'.$conversation->getKey()));
});
