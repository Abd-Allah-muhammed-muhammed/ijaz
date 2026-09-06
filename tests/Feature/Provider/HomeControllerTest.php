<?php

use App\Enums\OperationStatusEnum;
use App\Http\Controllers\Provider\HomeController;
use Modules\Marketplace\Models\Category;
use Modules\Orders\Actions\Provider\GetProviderHomeOrderStatsAction;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Models\PayoutRequest;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Http\Controllers\Dashboard\WithdrawRequestController as DashboardWithdrawRequestController;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WithdrawRequestService;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
    withoutWalletLocaleMiddleware();
});

it('renders provider home with order stats and recommendations', function () {
    $provider = createWalletProvider();
    $category = Category::factory()->create();
    $provider->categories()->attach($category->id);

    Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::InProgress,
    ]);
    Order::factory()->create([
        'category_id' => $category->id,
        'status' => OrderStatusEnum::New,
        'provider_id' => null,
        'accepted_offer_id' => null,
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home/Home')
            ->where('totalOrders', 1)
            ->has('recommendOrders')
            ->has('pendingOrders')
            ->has('inProgressOrders')
            ->has('orderTabCounts')
            ->where('orderTabCounts.in_progress', 1)
        );
});

test('provider home page exposes real wallet balance and amount_in_transfer, not hardcoded values', function () {
    $provider = createWalletProvider();
    $provider->wallet->update(['balance' => 4321.75]);

    $withdraw = createWithdrawFor($provider, ['amount' => 150]);

    PayoutRequest::factory()->create([
        'amount' => 150,
        'status' => PayoutStatusEnum::Pending,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $withdraw->id,
        'recipient_type' => $provider::class,
        'recipient_id' => $provider->id,
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home/Home')
            ->where('wallet.balance', number_format(4321.75, 2))
            ->where('wallet.amount_in_transfer', '150.00')
        );
});

test('provider home page exposes real totalOrders and totalFinishedOrders counts', function () {
    $provider = createWalletProvider();

    Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::InProgress,
    ]);
    Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::EndedByClient,
    ]);
    Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::EndedByClient,
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home/Home')
            ->where('totalOrders', 3)
            ->where('totalFinishedOrders', 2)
        );
});

test('provider home page no longer includes any conversations-card-specific props tied to fake data', function () {
    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home/Home')
            ->missing('conversations')
            ->missing('conversationOrders')
            ->has('endedByProviderOrders')
        );
});

test('provider home page exposes recent wallet transactions, limited to 5, most recent first', function () {
    $provider = createWalletProvider();

    foreach ([10, 20, 30, 40, 50, 60] as $amount) {
        fundWallet($provider, $amount);
    }

    $transactions = $provider->walletTransactions()->orderBy('id')->get();
    expect($transactions)->toHaveCount(6);

    $ordered = $transactions->values();
    $ordered[0]->forceFill(['created_at' => now()->subDays(6)])->save();
    $ordered[1]->forceFill(['created_at' => now()->subDays(5)])->save();
    $ordered[2]->forceFill(['created_at' => now()->subDays(4)])->save();
    $ordered[3]->forceFill(['created_at' => now()->subDays(3)])->save();
    $ordered[4]->forceFill(['created_at' => now()->subDays(2)])->save();
    $ordered[5]->forceFill(['created_at' => now()->subDay()])->save();

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home/Home')
            ->has('recentTransactions', 5)
            ->where('recentTransactions.0.id', $ordered[5]->id)
            ->where('recentTransactions.4.id', $ordered[1]->id)
            ->missing('recentTransactions.5')
        );
});

test('Home recent wallet activity now shows 5 transactions, not 2, most recent first', function () {
    $provider = createWalletProvider();

    foreach ([5, 15, 25, 35, 45, 55] as $amount) {
        fundWallet($provider, $amount);
    }

    $transactions = $provider->walletTransactions()->orderBy('id')->get();
    expect($transactions)->toHaveCount(6);

    $transactions[0]->forceFill(['created_at' => now()->subDays(6)])->save();
    $transactions[1]->forceFill(['created_at' => now()->subDays(5)])->save();
    $transactions[2]->forceFill(['created_at' => now()->subDays(4)])->save();
    $transactions[3]->forceFill(['created_at' => now()->subDays(3)])->save();
    $transactions[4]->forceFill(['created_at' => now()->subDays(2)])->save();
    $transactions[5]->forceFill(['created_at' => now()->subDay()])->save();

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home/Home')
            ->has('recentTransactions', 5)
            ->where('recentTransactions.0.id', $transactions[5]->id)
            ->where('recentTransactions.4.id', $transactions[1]->id)
            ->missing('recentTransactions.5')
        );
});

test('provider home page recent transactions only include the authenticated provider\'s own wallet, not other providers', function () {
    $provider = createWalletProvider();
    $other = createWalletProvider();

    fundWallet($other, 999);
    fundWallet($provider, 11);
    fundWallet($provider, 22);

    $otherTransactionIds = $other->walletTransactions()->pluck('id')->all();

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home/Home')
            ->has('recentTransactions', 2)
            ->where('recentTransactions', function ($transactions) use ($provider, $otherTransactionIds) {
                $ids = collect($transactions)->pluck('id');

                return $ids->intersect($otherTransactionIds)->isEmpty()
                    && collect($transactions)->every(
                        fn ($transaction) => (string) $transaction['user_id'] === (string) $provider->id
                            && (string) $transaction['wallet_id'] === (string) $provider->wallet->id
                    );
            })
        );
});

test('provider home page still exposes balance, amount_in_transfer, totalOrders, totalFinishedOrders unchanged from the previous commit', function () {
    $provider = createWalletProvider();
    $provider->wallet->update(['balance' => 4321.75]);

    $withdraw = createWithdrawFor($provider, ['amount' => 150]);

    PayoutRequest::factory()->create([
        'amount' => 150,
        'status' => PayoutStatusEnum::Pending,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $withdraw->id,
        'recipient_type' => $provider::class,
        'recipient_id' => $provider->id,
    ]);

    Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::InProgress,
    ]);
    Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::EndedByClient,
    ]);
    Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::EndedByClient,
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home/Home')
            ->where('wallet.balance', number_format(4321.75, 2))
            ->where('wallet.amount_in_transfer', '150.00')
            ->where('totalOrders', 3)
            ->where('totalFinishedOrders', 2)
        );
});

test('Home recent wallet activity shows the pending_debit amount, labeled as on hold, for a withdraw hold row — not -0', function () {
    $provider = createWalletProvider();
    fundWallet($provider, 500);

    app(WithdrawRequestService::class)->create(
        $provider,
        new CreateWithdrawData(amount: 200, userNotes: null),
    );

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home/Home')
            ->has('recentTransactions', 2)
            ->where('recentTransactions.0.amount', 200)
            ->where('recentTransactions.0.is_pending', true)
            ->where('recentTransactions.0.credit', '0.00')
            ->where('recentTransactions.0.debit', '0.00')
            ->where('recentTransactions.0.pending_debit', '200.00')
        );
});

test('Home recent wallet activity shows a normal credit/debit amount unchanged for terminal rows', function () {
    $provider = createWalletProvider();
    fundWallet($provider, 75);

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home/Home')
            ->has('recentTransactions', 1)
            ->where('recentTransactions.0.amount', 75)
            ->where('recentTransactions.0.is_pending', false)
            ->where('recentTransactions.0.credit', '75.00')
        );
});

test('Home recent wallet activity applies the same internal-row filtering as the mobile API (hides withdraw_hold_released, hides withdraw_requested once a terminal sibling exists)', function () {
    $provider = createWalletProvider();
    fundWallet($provider, 1000);

    $pending = app(WithdrawRequestService::class)->create(
        $provider,
        new CreateWithdrawData(amount: 100, userNotes: null),
    );
    $approved = app(WithdrawRequestService::class)->create(
        $provider,
        new CreateWithdrawData(amount: 200, userNotes: null),
    );

    $admin = createWalletAdmin();
    $this->actingAs($admin, 'admin')
        ->from(action([DashboardWithdrawRequestController::class, 'index']))
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $approved->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect();

    $ledger = WalletTransaction::query()
        ->where('wallet_id', $provider->wallet->id)
        ->get()
        ->keyBy('id');

    $holdReleasedIds = $ledger->filter(
        fn (WalletTransaction $row): bool => $row->entry_kind === WalletTransactionEntryKindEnum::WithdrawHoldReleased,
    )->keys();
    $approvedRequestedId = $ledger->first(
        fn (WalletTransaction $row): bool => $row->operation_id === $approved->id
            && $row->entry_kind === WalletTransactionEntryKindEnum::WithdrawRequested,
    )?->id;
    $approvedId = $ledger->first(
        fn (WalletTransaction $row): bool => $row->entry_kind === WalletTransactionEntryKindEnum::WithdrawApproved,
    )?->id;
    $pendingRequestedId = $ledger->first(
        fn (WalletTransaction $row): bool => $row->operation_id === $pending->id
            && $row->entry_kind === WalletTransactionEntryKindEnum::WithdrawRequested,
    )?->id;

    expect($holdReleasedIds)->not->toBeEmpty();

    $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home/Home')
            ->where('recentTransactions', function ($transactions) use ($holdReleasedIds, $approvedRequestedId, $approvedId, $pendingRequestedId) {
                $itemIds = collect($transactions)->pluck('id');

                return $itemIds->intersect($holdReleasedIds)->isEmpty()
                    && ! $itemIds->contains($approvedRequestedId)
                    && $itemIds->contains($approvedId)
                    && $itemIds->contains($pendingRequestedId);
            })
        );
});

test('provider home order stats include live tab counts alongside totals', function () {
    $provider = createWalletProvider();

    Order::factory()->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::InProgress,
    ]);
    Order::factory()->count(2)->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::EndedByClient,
    ]);
    Order::factory()->count(4)->create([
        'provider_id' => $provider->id,
        'status' => OrderStatusEnum::OfferProvided,
    ]);

    $stats = app(GetProviderHomeOrderStatsAction::class)->handle($provider);

    expect($stats)->toBe([
        'totalOrders' => 7,
        'totalFinishedOrders' => 2,
        'tabCounts' => [
            'new' => 0,
            'offer_provided' => 4,
            'in_progress' => 1,
            'ended_by_provider' => 0,
        ],
    ]);
});

test('provider home windowed orders expose this provider offer price (pending) and order price (accepted stages)', function () {
    ['provider' => $pendingProvider, 'order' => $pendingOrder, 'offer' => $pendingOffer] = createOrderWithOffer(
        offerAttrs: ['price' => 175.5],
    );

    ['provider' => $approvedProvider, 'order' => $approvedOrder, 'offer' => $acceptedOffer] = createOrderWithOffer(
        orderAttrs: [
            'status' => OrderStatusEnum::OfferProvided,
            'price' => 320,
        ],
        offerAttrs: [
            'price' => 320,
            'status' => OfferStatusEnum::Accepted,
        ],
    );

    $approvedOrder->update([
        'provider_id' => $approvedProvider->id,
        'accepted_offer_id' => $acceptedOffer->id,
    ]);

    $this->actingAs($pendingProvider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home/Home')
            ->has('pendingOrders', 1)
            ->where('pendingOrders.0.id', $pendingOrder->id)
            // Wire format may be string or number; Home must coerce either (see Vitest string regression).
            ->where('pendingOrders.0.offers.0.price', fn ($price) => (float) $price === 175.5)
        );

    $this->actingAs($approvedProvider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Home/Home')
            ->has('approvedOrders', 1)
            ->where('approvedOrders.0.id', $approvedOrder->id)
            ->where('approvedOrders.0.price', fn ($price) => (float) $price === 320.0)
            ->where('approvedOrders.0.offers.0.price', fn ($price) => (float) $price === 320.0)
        );
});
