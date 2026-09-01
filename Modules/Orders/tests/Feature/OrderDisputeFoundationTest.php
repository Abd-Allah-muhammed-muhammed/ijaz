<?php

use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Orders\Actions\CancelOrderAction;
use Modules\Orders\Actions\Dashboard\ResolveOrderDisputeEscalateAction;
use Modules\Orders\Actions\Dashboard\ResolveOrderDisputeFullToClientAction;
use Modules\Orders\Actions\Dashboard\ResolveOrderDisputeFullToProviderAction;
use Modules\Orders\Actions\Dashboard\ResolveOrderDisputePercentageSplitAction;
use Modules\Orders\Actions\OpenOrderDisputeAction;
use Modules\Orders\Actions\SettleOrderPaymentAction;
use Modules\Orders\Actions\User\EndAndReviewOrderAction;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\DTOs\CancelOrderDTO;
use Modules\Orders\DTOs\EndAndReviewDTO;
use Modules\Orders\Enums\OrderDisputeResolutionEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Handlers\OrderChatHandler;
use Modules\Orders\Http\Controllers\Api\V1\OrderDisputeController;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderStatusHistory;
use Modules\Orders\Notifications\OrderDisputedNotification;
use Modules\Orders\Notifications\OrderDisputeResolvedNotification;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Notification::fake();
});

/**
 * @return array{user: User, provider: Provider, order: Order, admin: Admin}
 */
function orderDisputeContext(array $orderAttributes = []): array
{
    $context = paidInProgressOrder(500.0);
    $order = $context['order'];

    if ($orderAttributes !== []) {
        $order->update($orderAttributes);
        $order = $order->fresh();
    }

    Permission::firstOrCreate(['name' => 'manage orders', 'guard_name' => 'admin']);
    $admin = Admin::query()->create([
        'name' => 'Order Dispute Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
    $admin->givePermissionTo('manage orders');

    return [
        'user' => $context['user'],
        'provider' => $context['provider'],
        'order' => $order,
        'admin' => $admin,
    ];
}

function openOrderDispute(Order $order, User|Provider $actor, string $role = 'user'): Order
{
    return app(OpenOrderDisputeAction::class)->handle(
        $order->fresh(),
        $actor,
        $role,
        'Mandatory dispute reason',
    );
}

/**
 * @return array{balance: float, pending_credit: float, pending_debit: float}
 */
function orderWalletSnapshot(User|Provider $party): array
{
    $wallet = $party->wallet->fresh();

    return [
        'balance' => (float) $wallet->balance,
        'pending_credit' => (float) $wallet->pending_credit,
        'pending_debit' => (float) $wallet->pending_debit,
    ];
}

test('either party (user or provider) can open a dispute on an InProgress order with a required reason', function () {
    foreach (['user', 'provider'] as $role) {
        ['user' => $user, 'provider' => $provider, 'order' => $order] = orderDisputeContext();
        $actor = $role === 'user' ? $user : $provider;

        $updated = openOrderDispute($order, $actor, $role);

        expect($updated->status)->toBe(OrderStatusEnum::Disputed);
    }
});

test('opening a dispute is rejected from any other order status', function () {
    foreach ([
        OrderStatusEnum::New,
        OrderStatusEnum::EndedByClient,
        OrderStatusEnum::EndedViaDispute,
        OrderStatusEnum::CancelledByClient,
        OrderStatusEnum::CancelledViaDispute,
        OrderStatusEnum::Escalated,
        OrderStatusEnum::Settled,
        OrderStatusEnum::Disputed,
    ] as $status) {
        ['user' => $user, 'order' => $order] = orderDisputeContext(['status' => $status]);

        expect(fn () => openOrderDispute($order, $user))
            ->toThrow(OrdersException::class);
    }
});

test('End and Cancel are both blocked while an order is Disputed', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = orderDisputeContext([
        'status' => OrderStatusEnum::Disputed,
    ]);

    expect(Gate::forUser($user)->denies('end', $order))->toBeTrue()
        ->and(Gate::forUser($provider)->denies('end', $order))->toBeTrue()
        ->and(Gate::forUser($user)->denies('cancel', $order))->toBeTrue()
        ->and(Gate::forUser($provider)->denies('cancel', $order))->toBeTrue();

    expect(fn () => app(EndAndReviewOrderAction::class)->handle(
        $order->fresh(),
        $user,
        new EndAndReviewDTO(rating: 5, comment: 'done'),
    ))->toThrow(OrdersException::class);

    expect(fn () => app(CancelOrderAction::class)->handle(
        $order->fresh(),
        $user,
        new CancelOrderDTO(reason: 'too late'),
    ))->toThrow(OrdersException::class);
});

test('chat remains available while Disputed', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order] = orderDisputeContext([
        'status' => OrderStatusEnum::Disputed,
    ]);

    expect(Gate::forUser($user)->allows('chat', $order))->toBeTrue()
        ->and(Gate::forUser($provider)->allows('chat', $order))->toBeTrue()
        ->and((new OrderChatHandler)->canOpen($user, $order->fresh()))->toBeTrue()
        ->and((new OrderChatHandler)->canOpen($provider, $order->fresh()))->toBeTrue();
});

test('opening a dispute notifies the OTHER party (not the opener) and admins with manage orders permission', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order, 'admin' => $admin] = orderDisputeContext();

    openOrderDispute($order, $user);

    Notification::assertSentTo($provider, OrderDisputedNotification::class);
    Notification::assertSentTo($admin, OrderDisputedNotification::class);
    Notification::assertNotSentTo($user, OrderDisputedNotification::class);
});

test('admin full-to-provider resolution: order -> EndedViaDispute, provider wallet hold released in full, user hold settled/closed', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $endedOrder] = orderDisputeContext();
    $endedOrder->update(['status' => OrderStatusEnum::EndedByClient]);
    app(SettleOrderPaymentAction::class)->handle($endedOrder->fresh());
    $endedUserSnapshot = orderWalletSnapshot($user);
    $endedProviderSnapshot = orderWalletSnapshot($provider);

    ['user' => $user, 'provider' => $provider, 'order' => $order, 'admin' => $admin] = orderDisputeContext();
    openOrderDispute($order, $user);

    app(ResolveOrderDisputeFullToProviderAction::class)->handle($order->fresh(), $admin);

    expect($order->fresh()->status)->toBe(OrderStatusEnum::EndedViaDispute)
        ->and(orderWalletSnapshot($user))->toBe($endedUserSnapshot)
        ->and(orderWalletSnapshot($provider))->toBe($endedProviderSnapshot);
});

test('admin full-to-client resolution: order -> CancelledViaDispute, wallet holds reversed internally', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $cancelOrder, 'admin' => $cancelAdmin] = orderDisputeContext();
    app(CancelOrderAction::class)->handle(
        $cancelOrder->fresh(),
        $user,
        new CancelOrderDTO(reason: 'cancel baseline'),
    );
    $cancelUserSnapshot = orderWalletSnapshot($user);
    $cancelProviderSnapshot = orderWalletSnapshot($provider);

    ['user' => $user, 'provider' => $provider, 'order' => $order, 'admin' => $admin] = orderDisputeContext();
    openOrderDispute($order, $user);

    app(ResolveOrderDisputeFullToClientAction::class)->handle($order->fresh(), $admin);

    expect($order->fresh()->status)->toBe(OrderStatusEnum::CancelledViaDispute)
        ->and(orderWalletSnapshot($user))->toBe($cancelUserSnapshot)
        ->and(orderWalletSnapshot($provider))->toBe($cancelProviderSnapshot);
});

test('admin percentage-split resolution: order -> Settled, split persisted on order resource', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order, 'admin' => $admin] = orderDisputeContext();
    openOrderDispute($order, $user);

    app(ResolveOrderDisputePercentageSplitAction::class)->handle($order->fresh(), $admin, 60);

    $settled = $order->fresh();
    expect($settled->status)->toBe(OrderStatusEnum::Settled)
        ->and($settled->dispute_user_percentage)->toBe(60)
        ->and($settled->disputeResolutionForApi())->toMatchArray([
            'user_percentage' => 60,
            'provider_percentage' => 40,
        ]);
});

test('admin escalate resolution: order -> Escalated, full reversal to user', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $cancelOrder, 'admin' => $cancelAdmin] = orderDisputeContext();
    app(CancelOrderAction::class)->handle(
        $cancelOrder->fresh(),
        $user,
        new CancelOrderDTO(reason: 'cancel baseline'),
    );
    $cancelUserSnapshot = orderWalletSnapshot($user);
    $cancelProviderSnapshot = orderWalletSnapshot($provider);

    ['user' => $user, 'provider' => $provider, 'order' => $order, 'admin' => $admin] = orderDisputeContext();
    openOrderDispute($order, $user);

    app(ResolveOrderDisputeEscalateAction::class)->handle($order->fresh(), $admin);

    expect($order->fresh()->status)->toBe(OrderStatusEnum::Escalated)
        ->and(orderWalletSnapshot($user))->toBe($cancelUserSnapshot)
        ->and(orderWalletSnapshot($provider))->toBe($cancelProviderSnapshot);
});

test('all 4 resolution paths require the order to currently be Disputed, reject otherwise', function () {
    ['order' => $order, 'admin' => $admin] = orderDisputeContext([
        'status' => OrderStatusEnum::InProgress,
    ]);

    expect(fn () => app(ResolveOrderDisputeFullToProviderAction::class)->handle($order->fresh(), $admin))
        ->toThrow(OrdersException::class);
    expect(fn () => app(ResolveOrderDisputeFullToClientAction::class)->handle($order->fresh(), $admin))
        ->toThrow(OrdersException::class);
    expect(fn () => app(ResolveOrderDisputePercentageSplitAction::class)->handle($order->fresh(), $admin, 50))
        ->toThrow(OrdersException::class);
    expect(fn () => app(ResolveOrderDisputeEscalateAction::class)->handle($order->fresh(), $admin))
        ->toThrow(OrdersException::class);
});

test('both parties are notified on any resolution outcome', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order, 'admin' => $admin] = orderDisputeContext();
    openOrderDispute($order, $user);

    app(ResolveOrderDisputeFullToProviderAction::class)->handle($order->fresh(), $admin);

    Notification::assertSentTo($user, OrderDisputeResolvedNotification::class);
    Notification::assertSentTo($provider, OrderDisputeResolvedNotification::class);
});

test('a resolved (any outcome) order cannot be disputed again, ended, or cancelled — fully terminal', function () {
    ['user' => $user, 'provider' => $provider, 'order' => $order, 'admin' => $admin] = orderDisputeContext();
    openOrderDispute($order, $user);
    app(ResolveOrderDisputeEscalateAction::class)->handle($order->fresh(), $admin);

    $terminal = $order->fresh();
    expect($terminal->status->isTerminal())->toBeTrue()
        ->and(Gate::forUser($user)->denies('dispute', $terminal))->toBeTrue()
        ->and(Gate::forUser($user)->denies('end', $terminal))->toBeTrue()
        ->and(Gate::forUser($provider)->denies('cancel', $terminal))->toBeTrue();

    expect(fn () => openOrderDispute($terminal, $user))->toThrow(OrdersException::class);
});

test('row locking (lockForUpdate) is used throughout dispute open/resolve', function () {
    ['user' => $user, 'order' => $order] = orderDisputeContext();

    $repository = Mockery::mock(OrderRepositoryInterface::class);
    $repository->shouldReceive('lockForUpdate')->once()->andReturnUsing(fn (Order $locked) => $locked);
    $repository->shouldReceive('update')->once()->andReturnUsing(function (Order $locked, array $data) {
        $locked->fill($data)->save();

        return $locked->fresh();
    });
    app()->instance(OrderRepositoryInterface::class, $repository);

    app(OpenOrderDisputeAction::class)->handle($order->fresh(), $user, 'user', 'lock test');
});

test('POST /api/v1/orders/{order}/dispute opens a dispute for the authenticated user', function () {
    ['user' => $user, 'order' => $order] = orderDisputeContext();

    Sanctum::actingAs($user);

    $this->postJson(action([OrderDisputeController::class, 'store'], ['order' => $order]), [
        'reason' => 'API dispute reason',
    ])->assertSuccessful();

    expect($order->fresh()->status)->toBe(OrderStatusEnum::Disputed);
});

test('POST /api/v1/orders/{order}/dispute requires a reason', function () {
    ['user' => $user, 'order' => $order] = orderDisputeContext();

    Sanctum::actingAs($user);

    $this->postJson(action([OrderDisputeController::class, 'store'], ['order' => $order]), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reason']);
});

test('opening a dispute logs a status history entry with the mandatory reason', function () {
    ['user' => $user, 'order' => $order] = orderDisputeContext();

    openOrderDispute($order, $user, 'user');

    $history = OrderStatusHistory::query()
        ->where('order_id', $order->id)
        ->where('status', OrderStatusEnum::Disputed->value)
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->from_status)->toBe(OrderStatusEnum::InProgress->value)
        ->and($history->getRawOriginal('reason'))->toBe('Mandatory dispute reason');
});

test('OrderDisputeResolutionEnum mirrors guarantor resolution shape', function () {
    expect(OrderDisputeResolutionEnum::FullUser->value)->toBe('full_user')
        ->and(OrderDisputeResolutionEnum::FullProvider->value)->toBe('full_provider')
        ->and(OrderDisputeResolutionEnum::Escalate->historyReason())->toBe('dispute_escalated_to_court');
});

test('two concurrent resolve-dispute attempts result in exactly one success and one already resolved error', function () {
    ['user' => $user, 'order' => $order, 'admin' => $admin] = orderDisputeContext();
    openOrderDispute($order, $user);

    $results = collect(range(1, 2))->map(function () use ($order, $admin) {
        try {
            app(ResolveOrderDisputeFullToProviderAction::class)->handle($order->fresh(), $admin);

            return 'success';
        } catch (OrdersException $exception) {
            return $exception->getTranslationKey();
        }
    });

    expect($results->filter(fn ($r) => $r === 'success')->count())->toBe(1)
        ->and($results->filter(fn ($r) => $r === 'order.dispute_already_resolved')->count())->toBe(1);
});
