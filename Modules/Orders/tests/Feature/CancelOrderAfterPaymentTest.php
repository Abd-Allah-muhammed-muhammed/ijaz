<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Http\Controllers\Api\V1\OrderController as UserOrderController;
use Modules\Orders\Http\Controllers\Provider\OrderController as ProviderOrderController;
use Modules\Orders\Notifications\OrderCancelledNotification;

beforeEach(function () {
    Notification::fake();
    withoutOrdersLocaleMiddleware();
});

test('provider can cancel an InProgress order with a valid reason — status becomes CancelledByProvider, cancelled_at and cancellation_reason are set, wallet holds are reversed, user is notified', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = paidInProgressOrder(500.0);

    expect((float) $user->wallet->fresh()->pending_debit)->toBe(500.0)
        ->and((float) $provider->wallet->fresh()->pending_credit)->toBe(500.0);

    $this->actingAs($provider, 'provider')
        ->post(action([ProviderOrderController::class, 'cancel'], ['order' => $order]), [
            'reason' => 'Client is unresponsive after several attempts',
        ])
        ->assertRedirect();

    $order->refresh();

    expect($order->status)->toBe(OrderStatusEnum::CancelledByProvider)
        ->and($order->cancellation_reason)->toBe('Client is unresponsive after several attempts')
        ->and($order->cancelled_at)->not->toBeNull()
        ->and((float) $user->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $provider->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $provider->wallet->fresh()->pending_debit)->toBe(0.0);

    Notification::assertSentTo($user, OrderCancelledNotification::class);
    Notification::assertNotSentTo($provider, OrderCancelledNotification::class);
});

test('user can cancel an InProgress order with a valid reason — status becomes CancelledByClient, same wallet reversal, provider is notified', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = paidInProgressOrder(500.0);

    Sanctum::actingAs($user, ['user-api'], 'user-api');

    $this->postJson(action([UserOrderController::class, 'cancel'], ['order' => $order]), [
        'reason' => 'Provider did not start the work as agreed',
    ])->assertOk();

    $order->refresh();

    expect($order->status)->toBe(OrderStatusEnum::CancelledByClient)
        ->and($order->cancellation_reason)->toBe('Provider did not start the work as agreed')
        ->and($order->cancelled_at)->not->toBeNull()
        ->and((float) $user->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $provider->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $provider->wallet->fresh()->pending_debit)->toBe(0.0);

    Notification::assertSentTo($provider, OrderCancelledNotification::class);
    Notification::assertNotSentTo($user, OrderCancelledNotification::class);
});

test('cancellation is rejected with a validation error when reason is missing or under 10 characters', function (array $payload) {
    ['user' => $user, 'order' => $order] = paidInProgressOrder();

    Sanctum::actingAs($user, ['user-api'], 'user-api');

    $this->postJson(action([UserOrderController::class, 'cancel'], ['order' => $order]), $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors('reason');

    expect($order->fresh()->status)->toBe(OrderStatusEnum::InProgress);
})->with([
    'missing' => [[]],
    'too short' => [['reason' => 'too short']],
]);

test('cancellation is rejected (422) when order status is not InProgress — cover at least New and EndedByProvider as blocked states', function (OrderStatusEnum $status) {
    ['user' => $user, 'order' => $order] = paidInProgressOrder();
    $order->update(['status' => $status]);

    Sanctum::actingAs($user, ['user-api'], 'user-api');

    $this->postJson(action([UserOrderController::class, 'cancel'], ['order' => $order]), [
        'reason' => 'This reason is long enough to pass validation',
    ])->assertUnprocessable();

    expect($order->fresh()->status)->toBe($status)
        ->and($order->fresh()->cancelled_at)->toBeNull();
})->with([
    'new' => [OrderStatusEnum::New],
    'ended by provider' => [OrderStatusEnum::EndedByProvider],
]);

test('a non-participant (not this order\'s user or provider) cannot cancel — 403 or 404 per existing Orders authorization convention', function () {
    ['order' => $order] = paidInProgressOrder();
    $attacker = User::factory()->create();

    Sanctum::actingAs($attacker, ['user-api'], 'user-api');

    $this->postJson(action([UserOrderController::class, 'cancel'], ['order' => $order]), [
        'reason' => 'Trying to cancel someone else\'s order',
    ])->assertNotFound();

    expect($order->fresh()->status)->toBe(OrderStatusEnum::InProgress);
});

test('cancelling twice is not possible — the second attempt fails cleanly once status is already CancelledBy*', function () {
    ['user' => $user, 'order' => $order] = paidInProgressOrder();

    Sanctum::actingAs($user, ['user-api'], 'user-api');

    $this->postJson(action([UserOrderController::class, 'cancel'], ['order' => $order]), [
        'reason' => 'Provider did not start the work as agreed',
    ])->assertOk();

    expect($order->fresh()->status)->toBe(OrderStatusEnum::CancelledByClient);

    $this->postJson(action([UserOrderController::class, 'cancel'], ['order' => $order]), [
        'reason' => 'Trying to cancel this order a second time',
    ])->assertUnprocessable();

    expect($order->fresh()->status)->toBe(OrderStatusEnum::CancelledByClient);
});
