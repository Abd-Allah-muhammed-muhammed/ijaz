<?php

use App\Http\Controllers\Dashboard\HomeController;
use App\Models\User;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Payment\Models\Payment;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

it('renders dashboard home with full cross-module stats contract', function () {
    $admin = createOrdersAdmin();

    $olderUser = User::factory()->create(['created_at' => now()->subDays(10)->startOfDay()]);
    $todayUser = User::factory()->create(['created_at' => now()->startOfDay()]);
    User::factory()->create(['created_at' => now()->subDays(45)]);

    $providerToday = createWalletProvider();
    $providerToday->forceFill(['created_at' => now()->startOfDay()])->saveQuietly();

    $providerOlder = createWalletProvider();
    $providerOlder->forceFill(['created_at' => now()->subDays(5)->startOfDay()])->saveQuietly();

    Order::factory()->create(['status' => OrderStatusEnum::New, 'user_id' => $todayUser->id]);
    Order::factory()->create(['status' => OrderStatusEnum::New, 'user_id' => $todayUser->id]);
    Order::factory()->create(['status' => OrderStatusEnum::OfferProvided, 'user_id' => $todayUser->id]);
    Order::factory()->create(['status' => OrderStatusEnum::InProgress, 'user_id' => $todayUser->id, 'provider_id' => $providerToday->id]);
    Order::factory()->create(['status' => OrderStatusEnum::EndedByProvider, 'user_id' => $todayUser->id, 'provider_id' => $providerToday->id]);
    Order::factory()->create(['status' => OrderStatusEnum::EndedByClient, 'user_id' => $todayUser->id, 'provider_id' => $providerToday->id]);

    $productOrderA = Order::factory()->create(['status' => OrderStatusEnum::CancelledByClient, 'user_id' => $todayUser->id]);
    $productOrderB = Order::factory()->create(['status' => OrderStatusEnum::CancelledByClient, 'user_id' => $todayUser->id]);
    $productOrderC = Order::factory()->create(['status' => OrderStatusEnum::CancelledByClient, 'user_id' => $todayUser->id]);

    Payment::factory()
        ->forProduct($productOrderA, $todayUser)
        ->accepted()
        ->create([
            'amount' => 150.50,
            'created_at' => now()->startOfDay(),
        ]);
    Payment::factory()
        ->forProduct($productOrderB, $todayUser)
        ->accepted()
        ->create([
            'amount' => 49.50,
            'created_at' => now()->subDays(2)->startOfDay(),
        ]);
    Payment::factory()
        ->forProduct($productOrderC, $todayUser)
        ->create([
            'amount' => 999,
            'created_at' => now()->startOfDay(),
        ]);

    $today = now()->format('Y-m-d');
    $emptyDay = now()->subDays(20)->format('Y-m-d');

    $this->actingAs($admin, 'admin')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Home')
            ->where('stats.totalUsers', 3)
            ->where('stats.totalProviders', 2)
            ->where('stats.totalOrders', 9)
            ->where('stats.totalRevenue', fn ($revenue) => (float) $revenue === 200.0)
            ->has('chartData.dates')
            ->has('chartData.userRegistrations')
            ->has('chartData.providerRegistrations')
            ->has('chartData.revenue')
            ->where('chartData', function ($chartData) use ($today, $emptyDay) {
                $chartData = collect($chartData)->toArray();
                $datesCount = count($chartData['dates']);

                expect($datesCount)->toBeGreaterThanOrEqual(30)
                    ->and(count($chartData['userRegistrations']))->toBe($datesCount)
                    ->and(count($chartData['providerRegistrations']))->toBe($datesCount)
                    ->and(count($chartData['revenue']))->toBe($datesCount)
                    ->and($chartData['dates'][0])->toMatch('/^\d{4}-\d{2}-\d{2}$/')
                    ->and($chartData['dates'][array_key_last($chartData['dates'])])->toBe($today);

                $todayIndex = array_search($today, $chartData['dates'], true);
                expect($todayIndex)->not->toBeFalse()
                    ->and($chartData['userRegistrations'][$todayIndex])->toBe(1)
                    ->and($chartData['providerRegistrations'][$todayIndex])->toBe(1)
                    ->and((float) $chartData['revenue'][$todayIndex])->toBe(150.5);

                $emptyIndex = array_search($emptyDay, $chartData['dates'], true);
                expect($emptyIndex)->not->toBeFalse()
                    ->and($chartData['userRegistrations'][$emptyIndex])->toBe(0)
                    ->and($chartData['providerRegistrations'][$emptyIndex])->toBe(0)
                    ->and((float) $chartData['revenue'][$emptyIndex])->toBe(0.0);

                return true;
            })
            ->where('orderStatusDistribution.'.OrderStatusEnum::New->value, 2)
            ->where('orderStatusDistribution.'.OrderStatusEnum::OfferProvided->value, 1)
            ->where('orderStatusDistribution.'.OrderStatusEnum::InProgress->value, 1)
            ->where('orderStatusDistribution.'.OrderStatusEnum::EndedByProvider->value, 1)
            ->where('orderStatusDistribution.'.OrderStatusEnum::EndedByClient->value, 1)
            ->has('pendingOrders', 2)
            ->has('approvedOrders', 1)
            ->has('inProgressOrders', 1)
            ->has('endedByProviderOrders', 1)
            ->where('approvedOrders.0.status.value', OrderStatusEnum::OfferProvided->value)
            ->has('latestUsers', 3)
            ->where('latestUsers.0.id', $todayUser->id)
            ->has('latestUsers.0.orders_count')
            ->has('latestProviders', 2)
            ->where('latestProviders.0.id', $providerToday->id)
            ->has('latestProviders.0.orders_count')
            ->has('latestProviders.0.reviews_count')
            ->where('latestUsers.1.id', $olderUser->id)
        );
});

it('caps each windowed order bucket at three items', function () {
    $admin = createOrdersAdmin();

    Order::factory()->count(5)->create(['status' => OrderStatusEnum::New]);
    Order::factory()->count(5)->create(['status' => OrderStatusEnum::OfferProvided]);

    $this->actingAs($admin, 'admin')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('pendingOrders', 3)
            ->has('approvedOrders', 3)
        );
});
