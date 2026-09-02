<?php

use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;
use App\Support\LookupCache;
use Illuminate\Support\Facades\Notification;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Http\Controllers\Dashboard\OrderController;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderStatusHistory;
use Modules\Orders\Notifications\OrderCancelledNotification;
use Modules\Orders\Support\OrderDisputeHistoryReason;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Notification::fake();
});

function createOrdersAdminWithManageOrders(): Admin
{
    $admin = createOrdersAdmin();
    Permission::firstOrCreate(['name' => 'manage orders', 'guard_name' => 'admin']);
    $admin->givePermissionTo('manage orders');

    return $admin;
}

function refreshOrderDashboardStats(): array
{
    LookupCache::forget('stats:orders:dashboard');

    return app(OrderRepositoryInterface::class)->dashboardStats();
}

/**
 * @return array{user: User, provider: Provider, order: Order, admin: Admin}
 */
function orderAdminDisputeContext(array $orderAttributes = []): array
{
    $context = paidInProgressOrder(500.0);
    $order = $context['order'];

    if ($orderAttributes !== []) {
        $order->update($orderAttributes);
        $order = $order->fresh();
    }

    return [
        'user' => $context['user'],
        'provider' => $context['provider'],
        'order' => $order,
        'admin' => createOrdersAdminWithManageOrders(),
    ];
}

test('admin Orders index status filter options include Disputed, EndedViaDispute, CancelledViaDispute, Escalated, and Settled', function (): void {
    withoutOrdersLocaleMiddleware();
    $admin = createOrdersAdmin();

    $disputeStatuses = [
        OrderStatusEnum::Disputed,
        OrderStatusEnum::EndedViaDispute,
        OrderStatusEnum::CancelledViaDispute,
        OrderStatusEnum::Escalated,
        OrderStatusEnum::Settled,
    ];

    $this->actingAs($admin, 'admin')
        ->get(action([OrderController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Orders/Index')
            ->has('selects.statuses', count(OrderStatusEnum::cases()))
            ->where('selects.statuses', function ($statuses) use ($disputeStatuses): bool {
                $values = collect($statuses)->pluck('value');

                foreach ($disputeStatuses as $status) {
                    if (! $values->contains($status->value)) {
                        return false;
                    }
                }

                return true;
            })
        );
});

test('the completed/ended stat aggregate now includes ended_via_dispute and settled orders', function (): void {
    Order::factory()->create(['status' => OrderStatusEnum::EndedViaDispute]);
    Order::factory()->create(['status' => OrderStatusEnum::Settled]);
    Order::factory()->create(['status' => OrderStatusEnum::EndedByClient]);

    $stats = refreshOrderDashboardStats();

    expect($stats['completed'])->toBe(3);
});

test('the cancelled stat aggregate now includes cancelled_via_dispute and escalated orders', function (): void {
    Order::factory()->create(['status' => OrderStatusEnum::CancelledViaDispute]);
    Order::factory()->create(['status' => OrderStatusEnum::Escalated]);
    Order::factory()->create(['status' => OrderStatusEnum::CancelledByProvider]);

    $stats = refreshOrderDashboardStats();

    expect($stats['cancelled'])->toBe(3);
});

test('an admin with manage orders can cancel an order while it is Disputed (escape hatch), mirroring Guarantor\'s CancelGuarantorAction', function (): void {
    withoutOrdersLocaleMiddleware();

    ['order' => $order, 'admin' => $admin] = orderAdminDisputeContext(['status' => OrderStatusEnum::Disputed]);

    $this->actingAs($admin, 'admin')
        ->from(action([OrderController::class, 'show'], $order))
        ->post(action([OrderController::class, 'cancel'], $order), [
            'reason' => 'Admin escape hatch during dispute',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($order->fresh()->status)->toBe(OrderStatusEnum::Cancelled);
});

test('admin cancel during dispute logs the correct history/reason and notifies both parties', function (): void {
    withoutOrdersLocaleMiddleware();

    ['user' => $user, 'provider' => $provider, 'order' => $order, 'admin' => $admin] = orderAdminDisputeContext([
        'status' => OrderStatusEnum::Disputed,
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([OrderController::class, 'show'], $order))
        ->post(action([OrderController::class, 'cancel'], $order), [
            'reason' => 'Admin cancelled during dispute',
            'notes' => 'closing case',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(
        OrderStatusHistory::query()
            ->where('order_id', $order->id)
            ->where('reason', OrderDisputeHistoryReason::ClosedByAdminCancel)
            ->exists()
    )->toBeTrue();

    Notification::assertSentTo($user, OrderCancelledNotification::class);
    Notification::assertSentTo($provider, OrderCancelledNotification::class);
});
