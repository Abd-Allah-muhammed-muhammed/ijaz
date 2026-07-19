<?php

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Support\Facades\Route;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
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
    $this->getJson(route('api.v1.chats.guarantor.index'))
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
    expect(Route::has('api.v1.chats.guarantor.index'))->toBeTrue()
        ->and(route('api.v1.chats.guarantor.index'))->toBe(url('api/v1/chats/guarantor'));
});

test('chat store route resolves correctly', function () {
    expect(Route::has('api.v1.chats.guarantor.store'))->toBeTrue()
        ->and(route('api.v1.chats.guarantor.store'))->toBe(url('api/v1/chats/guarantor'));
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

    expect(Route::has('api.v1.chats.guarantor.show'))->toBeTrue()
        ->and(route('api.v1.chats.guarantor.show', $conversation))
        ->toBe(url('api/v1/chats/guarantor/'.$conversation->getKey()));
});

test('chat send route resolves correctly', function () {
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

    expect(Route::has('api.v1.chats.guarantor.send'))->toBeTrue()
        ->and(route('api.v1.chats.guarantor.send', $conversation))
        ->toBe(url('api/v1/chats/guarantor/'.$conversation->getKey().'/send'));
});

test('guarantor index filters by status', function () {
    $user = User::factory()->create();

    $accepted = GuarantorRequest::factory()->accepted()->create([
        'requester_type' => User::class,
        'requester_id' => $user->getKey(),
        'title' => 'Accepted guarantee',
    ]);

    GuarantorRequest::factory()->pendingAdmin()->create([
        'requester_type' => User::class,
        'requester_id' => $user->getKey(),
        'title' => 'Pending guarantee',
    ]);

    $ids = collect($this->actingAs($user, 'sanctum')
        ->getJson(route('api.v1.guarantor.guarantor.index', ['status' => GuarantorStatusEnum::Accepted->value]))
        ->assertSuccessful()
        ->json('data.items'))
        ->pluck('id');

    expect($ids)->toContain($accepted->id)
        ->and($ids)->toHaveCount(1);
});

test('guarantor index filters by type individual', function () {
    $user = User::factory()->create();

    $individual = GuarantorRequest::factory()->create([
        'type' => GuarantorTypeEnum::Individual,
        'requester_type' => User::class,
        'requester_id' => $user->getKey(),
    ]);

    GuarantorRequest::factory()->company()->create([
        'requester_type' => User::class,
        'requester_id' => $user->getKey(),
    ]);

    $ids = collect($this->actingAs($user, 'sanctum')
        ->getJson(route('api.v1.guarantor.guarantor.index', ['type' => GuarantorTypeEnum::Individual->value]))
        ->assertSuccessful()
        ->json('data.items'))
        ->pluck('id');

    expect($ids)->toContain($individual->id)
        ->and($ids)->toHaveCount(1);
});

test('guarantor index filters by role requester', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $asRequester = GuarantorRequest::factory()->create([
        'requester_type' => User::class,
        'requester_id' => $user->getKey(),
        'counterparty_type' => User::class,
        'counterparty_id' => $other->getKey(),
    ]);

    GuarantorRequest::factory()->create([
        'requester_type' => User::class,
        'requester_id' => $other->getKey(),
        'counterparty_type' => User::class,
        'counterparty_id' => $user->getKey(),
    ]);

    $ids = collect($this->actingAs($user, 'sanctum')
        ->getJson(route('api.v1.guarantor.guarantor.index', ['role' => 'requester']))
        ->assertSuccessful()
        ->json('data.items'))
        ->pluck('id');

    expect($ids)->toContain($asRequester->id)
        ->and($ids)->toHaveCount(1);
});

test('guarantor index filters by role counterparty', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $asCounterparty = GuarantorRequest::factory()->create([
        'requester_type' => User::class,
        'requester_id' => $other->getKey(),
        'counterparty_type' => User::class,
        'counterparty_id' => $user->getKey(),
    ]);

    GuarantorRequest::factory()->create([
        'requester_type' => User::class,
        'requester_id' => $user->getKey(),
        'counterparty_type' => User::class,
        'counterparty_id' => $other->getKey(),
    ]);

    $ids = collect($this->actingAs($user, 'sanctum')
        ->getJson(route('api.v1.guarantor.guarantor.index', ['role' => 'counterparty']))
        ->assertSuccessful()
        ->json('data.items'))
        ->pluck('id');

    expect($ids)->toContain($asCounterparty->id)
        ->and($ids)->toHaveCount(1);
});

test('guarantor index filters by search title', function () {
    $user = User::factory()->create();

    $matching = GuarantorRequest::factory()->create([
        'requester_type' => User::class,
        'requester_id' => $user->getKey(),
        'title' => 'Unique Postman Keyword guarantee',
    ]);

    GuarantorRequest::factory()->create([
        'requester_type' => User::class,
        'requester_id' => $user->getKey(),
        'title' => 'Unrelated title',
    ]);

    $ids = collect($this->actingAs($user, 'sanctum')
        ->getJson(route('api.v1.guarantor.guarantor.index', ['search' => 'Postman Keyword']))
        ->assertSuccessful()
        ->json('data.items'))
        ->pluck('id');

    expect($ids)->toContain($matching->id)
        ->and($ids)->toHaveCount(1);
});

test('guarantor index returns all when no filters', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    $asRequester = GuarantorRequest::factory()->create([
        'requester_type' => User::class,
        'requester_id' => $user->getKey(),
    ]);

    $asCounterparty = GuarantorRequest::factory()->create([
        'requester_type' => User::class,
        'requester_id' => $other->getKey(),
        'counterparty_type' => User::class,
        'counterparty_id' => $user->getKey(),
    ]);

    GuarantorRequest::factory()->create([
        'requester_type' => User::class,
        'requester_id' => $other->getKey(),
    ]);

    $ids = collect($this->actingAs($user, 'sanctum')
        ->getJson(route('api.v1.guarantor.guarantor.index'))
        ->assertSuccessful()
        ->json('data.items'))
        ->pluck('id');

    expect($ids)->toContain($asRequester->id, $asCounterparty->id)
        ->and($ids)->toHaveCount(2);
});
