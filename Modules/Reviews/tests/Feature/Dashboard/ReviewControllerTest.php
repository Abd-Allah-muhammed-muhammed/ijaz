<?php

use App\Models\Provider;
use App\Models\User;
use Modules\Orders\Models\Order;
use Modules\Reviews\Http\Controllers\Dashboard\ReviewController;
use Modules\Reviews\Models\Review;
use Modules\Reviews\Services\ReviewService;

it('admin can list reviews on dashboard index', function () {
    withoutReviewsDashboardLocaleMiddleware();
    $admin = createReviewsDashboardAdmin(['show reviews']);

    $user = User::factory()->create();
    $provider = createWalletProvider();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'provider_id' => $provider->id,
    ]);

    Review::query()->create([
        'reviewer_type' => User::class,
        'reviewer_id' => $user->id,
        'reviewee_type' => Provider::class,
        'reviewee_id' => $provider->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'rating' => 5,
        'comment' => 'Dashboard list review',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([ReviewController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Reviews/Index')
            ->has('prams')
            ->has('rows.data', 1)
        );
});

it('admin can delete a review', function () {
    withoutReviewsDashboardLocaleMiddleware();
    $admin = createReviewsDashboardAdmin(['delete reviews', 'show reviews']);

    $user = User::factory()->create();
    $provider = createWalletProvider();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'provider_id' => $provider->id,
    ]);

    $review = Review::query()->create([
        'reviewer_type' => User::class,
        'reviewer_id' => $user->id,
        'reviewee_type' => Provider::class,
        'reviewee_id' => $provider->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'rating' => 3,
        'comment' => 'To delete',
    ]);

    $this->actingAs($admin, 'admin')
        ->delete(action([ReviewController::class, 'destroy'], $review))
        ->assertRedirect(route('dashboard.reviews.index'));

    expect(Review::query()->whereKey($review->id)->exists())->toBeFalse();
});

it('admin without show reviews cannot access index', function () {
    withoutReviewsDashboardLocaleMiddleware();
    $admin = createReviewsDashboardAdmin(['show users']);

    $this->actingAs($admin, 'admin')
        ->get(action([ReviewController::class, 'index']))
        ->assertForbidden();
});

it('review service submit creates or updates by reviewer+operation keys', function () {
    $user = User::factory()->create();
    $provider = createWalletProvider();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'provider_id' => $provider->id,
    ]);

    $service = app(ReviewService::class);

    $first = $service->submit($user, $provider, $order, 4, 'Good');
    $second = $service->submit($user, $provider, $order, 5, 'Great');

    expect($first->id)->toBe($second->id)
        ->and($second->rating)->toBe(5)
        ->and($second->comment)->toBe('Great')
        ->and(Review::query()->count())->toBe(1);
});

it('user has HasReviews relation for received provider reviews', function () {
    $user = User::factory()->create();
    $provider = createWalletProvider();
    $order = Order::factory()->create([
        'user_id' => $user->id,
        'provider_id' => $provider->id,
    ]);

    Review::query()->create([
        'reviewer_type' => Provider::class,
        'reviewer_id' => $provider->id,
        'reviewee_type' => User::class,
        'reviewee_id' => $user->id,
        'operation_type' => Order::class,
        'operation_id' => $order->id,
        'rating' => 5,
        'comment' => 'Nice client',
    ]);

    expect($user->reviews()->count())->toBe(1)
        ->and($user->reviews()->first()->rating)->toBe(5);
});
