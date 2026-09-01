<?php

use App\Models\User;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Process;
use Laravel\Sanctum\Sanctum;
use Mockery\MockInterface;
use Modules\Marketplace\Models\Category;
use Modules\Orders\Actions\Offer\InitiateOrderPaymentAction;
use Modules\Orders\Actions\Offer\UpdateOfferStatusAction;
use Modules\Orders\Actions\Provider\ShowProviderOrderAction;
use Modules\Orders\Actions\Provider\UpdateProviderOfferAction;
use Modules\Orders\Actions\Provider\UpdateProviderReviewAction;
use Modules\Orders\Contracts\Repositories\OrderRepositoryInterface;
use Modules\Orders\Database\Factories\OrderOfferFactory;
use Modules\Orders\DTOs\EndAndReviewDTO;
use Modules\Orders\DTOs\UpdateOfferStatusDTO;
use Modules\Orders\DTOs\UpdateOrderOfferDTO;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Http\Controllers\Api\V1\OrderController as UserOrderController;
use Modules\Orders\Http\Controllers\Provider\OrderController as ProviderOrderController;
use Modules\Orders\Models\Order;
use Modules\Orders\Notifications\OrderOfferRejectedNotification;
use Modules\Orders\Policies\OrderPolicy;
use Modules\Orders\Repositories\OrderRepository;
use Modules\Payment\DTOs\PaymentInitResult;
use Modules\Payment\Services\PaymentService;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

beforeEach(function () {
    Notification::fake();
    setWalletSetting('testing_fees', '20');
});

test('sending status=pending or status=paid to update-offer-status is rejected with a clear validation/domain error, not silently falling through to the cancel branch', function (OfferStatusEnum $invalidStatus) {
    ['owner' => $owner, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        offerAttrs: ['status' => OfferStatusEnum::Accepted],
    );

    app(UpdateOfferStatusAction::class)->handle(
        $order,
        $offer,
        $owner,
        new UpdateOfferStatusDTO(status: $invalidStatus),
    );
})->throws(OrdersException::class, 'order_offer_status_not_allowed')->with([
    'pending' => [OfferStatusEnum::Pending],
    'paid' => [OfferStatusEnum::Paid],
]);

test('update-offer-status API rejects pending and paid at validation before the action runs', function (string $status) {
    ['owner' => $owner, 'order' => $order, 'offer' => $offer] = createOrderWithOffer();

    Sanctum::actingAs($owner, ['user-api'], 'user-api');

    $this->postJson(action([UserOrderController::class, 'updateOfferStatus'], [
        'order' => $order,
        'offer' => $offer,
    ]), ['status' => $status])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('status');

    expect($order->fresh()->status)->toBe(OrderStatusEnum::New)
        ->and($offer->fresh()->status)->toBe(OfferStatusEnum::Pending);
})->with(['pending', 'paid']);

test('rejecting an offer that was previously Accepted correctly reverts the order to New and clears provider_id/accepted_offer_id/price', function () {
    ['owner' => $owner, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer();

    app(UpdateOfferStatusAction::class)->handle(
        $order,
        $offer,
        $owner,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Accepted),
    );

    expect($order->fresh()->status)->toBe(OrderStatusEnum::OfferProvided)
        ->and($order->fresh()->accepted_offer_id)->toBe($offer->id);

    app(UpdateOfferStatusAction::class)->handle(
        $order->fresh(),
        $offer->fresh(),
        $owner,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Rejected),
    );

    $order->refresh();
    $offer->refresh();

    expect($offer->status)->toBe(OfferStatusEnum::Rejected)
        ->and($order->status)->toBe(OrderStatusEnum::New)
        ->and($order->provider_id)->toBeNull()
        ->and($order->accepted_offer_id)->toBeNull()
        ->and($order->price)->toBeNull();
});

test('accepting one offer while others are Pending on the same order locks the order row and auto-rejects every sibling pending offer', function () {
    ['owner' => $owner, 'order' => $order, 'offer' => $winningOffer, 'provider' => $winningProvider] = createOrderWithOffer();

    $siblingProvider = createWalletProvider();
    $siblingOffer = OrderOfferFactory::new()
        ->forOrder($order)
        ->forProvider($siblingProvider)
        ->create([
            'price' => 150.0,
            'description' => 'Sibling offer',
            'status' => OfferStatusEnum::Pending,
        ]);

    app(UpdateOfferStatusAction::class)->handle(
        $order,
        $winningOffer,
        $owner,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Accepted),
    );

    expect($order->fresh()->status)->toBe(OrderStatusEnum::OfferProvided)
        ->and($order->fresh()->accepted_offer_id)->toBe($winningOffer->id)
        ->and($winningOffer->fresh()->status)->toBe(OfferStatusEnum::Accepted)
        ->and($siblingOffer->fresh()->status)->toBe(OfferStatusEnum::Rejected);
});

test('each auto-rejected sibling provider receives a rejection notification', function () {
    ['owner' => $owner, 'order' => $order, 'offer' => $winningOffer] = createOrderWithOffer();

    $siblingProvider = createWalletProvider();
    OrderOfferFactory::new()
        ->forOrder($order)
        ->forProvider($siblingProvider)
        ->create([
            'price' => 150.0,
            'description' => 'Sibling offer',
            'status' => OfferStatusEnum::Pending,
        ]);

    app(UpdateOfferStatusAction::class)->handle(
        $order,
        $winningOffer,
        $owner,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Accepted),
    );

    Notification::assertSentTo($siblingProvider, OrderOfferRejectedNotification::class);
});

test('two simultaneous accept attempts on the same order — only ONE can succeed, the other gets a clear already accepted error, not a silent partial state', function () {
    $dbPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ijaz_order_accept_race_'.uniqid('', true).'.sqlite';
    $monitoringPath = $dbPath.'.monitoring';

    foreach ([$dbPath, $monitoringPath] as $path) {
        if (is_file($path)) {
            unlink($path);
        }
        touch($path);
    }

    $sharedEnv = [
        'APP_ENV' => 'local',
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => $dbPath,
        'DB_MONITORING_DRIVER' => 'sqlite',
        'DB_MONITORING_DATABASE' => $monitoringPath,
        'CACHE_STORE' => 'array',
        'QUEUE_CONNECTION' => 'sync',
    ];

    config([
        'database.connections.sqlite.busy_timeout' => 10000,
        'database.connections.sqlite.journal_mode' => 'WAL',
    ]);

    try {
        $setup = Process::path(base_path())
            ->timeout(120)
            ->env($sharedEnv)
            ->run([
                PHP_BINARY,
                base_path('Modules/Orders/tests/bin/concurrent_accept_setup.php'),
            ]);

        expect($setup->successful())->toBeTrue(
            'Setup failed: '.$setup->output().$setup->errorOutput()
        );

        preg_match('/USER_ID=(\d+)/', $setup->output(), $userMatch);
        preg_match('/ORDER_ID=([0-9a-f-]+)/', $setup->output(), $orderMatch);
        preg_match('/OFFER_A_ID=([0-9a-f-]+)/', $setup->output(), $offerAMatch);
        preg_match('/OFFER_B_ID=([0-9a-f-]+)/', $setup->output(), $offerBMatch);

        $userId = (int) ($userMatch[1] ?? 0);
        $orderId = (string) ($orderMatch[1] ?? '');
        $offerAId = (string) ($offerAMatch[1] ?? '');
        $offerBId = (string) ($offerBMatch[1] ?? '');

        expect($userId)->toBeGreaterThan(0)
            ->and($orderId)->not->toBe('')
            ->and($offerAId)->not->toBe('')
            ->and($offerBId)->not->toBe('');

        $worker = base_path('Modules/Orders/tests/bin/concurrent_accept_worker.php');

        $poolResults = Process::pool(function (Pool $pool) use ($worker, $sharedEnv, $userId, $orderId, $offerAId): void {
            $pool->as('a')
                ->path(base_path())
                ->timeout(60)
                ->env($sharedEnv)
                ->command([PHP_BINARY, $worker, (string) $userId, $orderId, $offerAId]);

            $pool->as('b')
                ->path(base_path())
                ->timeout(60)
                ->env($sharedEnv)
                ->command([PHP_BINARY, $worker, (string) $userId, $orderId, $offerAId]);
        })->start()->wait();

        $successes = 0;
        $alreadyAccepted = 0;

        foreach ($poolResults->collect() as $result) {
            $output = trim($result->output()."\n".$result->errorOutput());

            if (str_contains($output, 'OK')) {
                $successes++;
            } elseif (str_contains($output, 'ALREADY_ACCEPTED')) {
                $alreadyAccepted++;
            } else {
                expect($output)->toContain('OK');
            }
        }

        expect($successes)->toBe(1)
            ->and($alreadyAccepted)->toBe(1);
    } finally {
        foreach ([$dbPath, $monitoringPath] as $path) {
            if (is_file($path)) {
                unlink($path);
            }
        }
    }
});

test('accept is a no-op error (not silent skip) if the order is no longer New by the time the lock is acquired', function () {
    ['owner' => $owner, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        orderAttrs: ['status' => OrderStatusEnum::OfferProvided],
    );

    expect(fn () => app(UpdateOfferStatusAction::class)->handle(
        $order,
        $offer,
        $owner,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Accepted),
    ))->toThrow(function (OrdersException $exception): bool {
        return $exception->getMessage() === 'order_already_has_accepted_offer'
            && $exception->getCode() === Response::HTTP_UNPROCESSABLE_ENTITY;
    });

    expect($offer->fresh()->status)->toBe(OfferStatusEnum::Pending);
});

test('UpdateOfferStatusAction locks the order row before accepting an offer', function () {
    ['owner' => $owner, 'order' => $order, 'offer' => $offer] = createOrderWithOffer();

    /** @var OrderRepositoryInterface&MockInterface $repository */
    $repository = Mockery::mock(OrderRepository::class)->makePartial();
    app()->instance(OrderRepositoryInterface::class, $repository);

    app(UpdateOfferStatusAction::class)->handle(
        $order,
        $offer,
        $owner,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Accepted),
    );

    $repository->shouldHaveReceived('lockForUpdate')->once();
});

test('UpdateProviderOfferAction rejects when the offer UUID does not belong to the order UUID in the URL', function () {
    ['provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        offerAttrs: ['status' => OfferStatusEnum::Pending],
    );

    $otherOrder = Order::factory()->create([
        'status' => OrderStatusEnum::New,
        'user_id' => User::factory()->create()->id,
        'category_id' => $order->category_id,
    ]);

    expect(fn () => app(UpdateProviderOfferAction::class)->handle(
        $otherOrder,
        $offer,
        $provider,
        UpdateOrderOfferDTO::fromValidated(['price' => 250.0, 'description' => 'Cross-order edit attempt']),
    ))->toThrow(OrdersException::class, 'sorry this offer does not belong to this order.');
});

test('UpdateProviderReviewAction rejects when the reviewing provider is not the order\'s assigned provider', function () {
    $owner = User::factory()->create();
    $assigned = createWalletProvider();
    $intruder = createWalletProvider();
    $order = Order::factory()->create([
        'user_id' => $owner->id,
        'provider_id' => $assigned->id,
        'status' => OrderStatusEnum::EndedByClient,
    ]);

    expect(fn () => app(UpdateProviderReviewAction::class)->handle(
        $order,
        $intruder,
        new EndAndReviewDTO(rating: 5, comment: 'Should not stick'),
    ))->toThrow(OrdersException::class, 'you can not review this order');
});

test('provider order show requires the requesting provider to be assigned to or have offered on the order — rejects otherwise', function () {
    ['provider' => $assigned, 'order' => $order] = createOrderWithOffer(
        orderAttrs: ['status' => OrderStatusEnum::InProgress, 'provider_id' => null],
    );
    $order->update(['provider_id' => $assigned->id]);

    $intruder = createWalletProvider();

    expect(fn () => app(ShowProviderOrderAction::class)->handle($order->fresh(), $intruder))
        ->toThrow(NotFoundHttpException::class);

    withoutOrdersLocaleMiddleware();

    $this->actingAs($intruder, 'provider')
        ->get(action([ProviderOrderController::class, 'show'], ['order' => $order]))
        ->assertNotFound();
});

test('provider order show allows a provider who has submitted an offer on a new order', function () {
    $provider = createWalletProvider();
    $category = Category::factory()->create();
    $provider->categories()->attach($category->id);
    $owner = User::factory()->create();
    $order = Order::factory()->create([
        'user_id' => $owner->id,
        'category_id' => $category->id,
        'status' => OrderStatusEnum::New,
        'provider_id' => null,
        'accepted_offer_id' => null,
    ]);

    OrderOfferFactory::new()
        ->forOrder($order)
        ->forProvider($provider)
        ->create(['status' => OfferStatusEnum::Pending]);

    expect(app(OrderPolicy::class)->viewAsProvider($provider, $order))->toBeTrue();

    withoutOrdersLocaleMiddleware();

    $this->actingAs($provider, 'provider')
        ->get(action([ProviderOrderController::class, 'show'], ['order' => $order]))
        ->assertSuccessful();
});

test('provider order show allows viewing a recommended new order in the provider category before an offer exists', function () {
    $provider = createWalletProvider();
    $category = Category::factory()->create();
    $provider->categories()->attach($category->id);
    $order = Order::factory()->create([
        'category_id' => $category->id,
        'status' => OrderStatusEnum::New,
        'provider_id' => null,
        'accepted_offer_id' => null,
    ]);

    expect(Gate::forUser($provider)->allows('viewAsProvider', $order))->toBeTrue();
});

test('InitiateOrderPaymentAction rejects if the offer is not Accepted, or if the offer is not the order\'s accepted_offer_id', function () {
    ['owner' => $owner, 'order' => $order, 'offer' => $offer] = createOrderWithOffer(
        offerAttrs: ['status' => OfferStatusEnum::Pending],
    );

    expect(fn () => app(InitiateOrderPaymentAction::class)->handle($order, $offer, $owner))
        ->toThrow(OrdersException::class, 'you can not pay for this order');

    app(UpdateOfferStatusAction::class)->handle(
        $order->fresh(),
        $offer->fresh(),
        $owner,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Accepted),
    );

    $wrongOffer = OrderOfferFactory::new()
        ->forOrder($order->fresh())
        ->forProvider(createWalletProvider())
        ->create(['status' => OfferStatusEnum::Rejected]);

    expect(fn () => app(InitiateOrderPaymentAction::class)->handle($order->fresh(), $wrongOffer, $owner))
        ->toThrow(OrdersException::class, 'you can not pay for this order');
});

test('existing happy-path accept/reject/cancel/pay flows are completely unaffected — full regression', function () {
    ['owner' => $owner, 'provider' => $provider, 'order' => $order, 'offer' => $offer] = createOrderWithOffer();

    app(UpdateOfferStatusAction::class)->handle(
        $order,
        $offer,
        $owner,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Accepted),
    );

    expect($order->fresh()->status)->toBe(OrderStatusEnum::OfferProvided)
        ->and($offer->fresh()->status)->toBe(OfferStatusEnum::Accepted);

    ['owner' => $owner2, 'order' => $order2, 'offer' => $offer2, 'provider' => $provider2] = createOrderWithOffer();

    app(UpdateOfferStatusAction::class)->handle(
        $order2,
        $offer2,
        $owner2,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Rejected),
    );

    expect($offer2->fresh()->status)->toBe(OfferStatusEnum::Rejected);

    ['owner' => $owner3, 'order' => $order3, 'offer' => $offer3] = createOrderWithOffer();

    app(UpdateOfferStatusAction::class)->handle(
        $order3,
        $offer3,
        $owner3,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Accepted),
    );

    app(UpdateOfferStatusAction::class)->handle(
        $order3->fresh(),
        $offer3->fresh(),
        $owner3,
        new UpdateOfferStatusDTO(status: OfferStatusEnum::Cancelled),
    );

    expect($order3->fresh()->status)->toBe(OrderStatusEnum::New)
        ->and($offer3->fresh()->status)->toBe(OfferStatusEnum::Cancelled);

    ['owner' => $owner4, 'order' => $order4, 'offer' => $offer4] = createOrderWithOffer(
        orderAttrs: [
            'status' => OrderStatusEnum::OfferProvided,
            'price' => 200,
            'user_fees' => 0,
            'provider_fees' => 31.5,
        ],
        offerAttrs: ['status' => OfferStatusEnum::Accepted, 'price' => 200],
    );
    $order4->update(['accepted_offer_id' => $offer4->id, 'provider_id' => $offer4->provider_id]);

    $mock = Mockery::mock(PaymentService::class);
    $mock->shouldReceive('initiate')
        ->once()
        ->withArgs(function ($ownerArg, $productArg, $amount) use ($owner4, $offer4, $order4) {
            return $ownerArg->is($owner4)
                && $productArg->is($offer4)
                && abs($amount - (float) $order4->fresh()->user_total) < 0.001;
        })
        ->andReturn(new PaymentInitResult(
            status: 'success',
            driver: 'testing',
            url: 'https://pay.test/checkout',
            payable: true,
            transactionId: 'txn-integrity',
            message: null,
        ));
    app()->instance(PaymentService::class, $mock);

    app(InitiateOrderPaymentAction::class)->handle($order4->fresh(), $offer4->fresh(), $owner4);
});
