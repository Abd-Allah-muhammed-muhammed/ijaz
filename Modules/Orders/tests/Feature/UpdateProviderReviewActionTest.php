<?php

use App\Models\Provider;
use App\Models\User;
use Modules\Orders\Actions\Provider\UpdateProviderReviewAction;
use Modules\Orders\DTOs\EndAndReviewDTO;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Reviews\Models\Review;

it('stores internally consistent provider reviewer and user reviewee on the Review row', function () {
    $owner = User::factory()->create();
    $provider = createWalletProvider();
    $order = Order::factory()->create([
        'user_id' => $owner->id,
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::EndedByClient,
    ]);

    app(UpdateProviderReviewAction::class)->handle(
        $order,
        $provider,
        EndAndReviewDTO::fromValidated([
            'rating' => 5,
            'comment' => 'Great client',
        ]),
    );

    $review = Review::query()->where([
        'operation_type' => Order::class,
        'operation_id' => $order->id,
    ])->first();

    expect($review)->not->toBeNull()
        ->and($review->reviewer_type)->toBe(Provider::class)
        ->and($review->reviewer_id)->toBe($provider->id)
        ->and($review->reviewee_type)->toBe(User::class)
        ->and($review->reviewee_id)->toBe($owner->id);
});
