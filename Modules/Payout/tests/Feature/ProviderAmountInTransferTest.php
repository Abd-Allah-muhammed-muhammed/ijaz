<?php

use App\Http\Controllers\Provider\AuthController;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Models\PayoutRequest;
use Modules\Payout\Services\PayoutService;
use Modules\Wallet\Models\WithdrawRequest;

test('provider profile wallet payload exposes amount_in_transfer summing pending and submitted PayoutRequests for that provider', function () {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    $withdrawA = createWithdrawFor($provider, ['amount' => 100]);
    $withdrawB = createWithdrawFor($provider, ['amount' => 50]);

    PayoutRequest::factory()->create([
        'amount' => 100,
        'status' => PayoutStatusEnum::Pending,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $withdrawA->id,
        'recipient_type' => $provider::class,
        'recipient_id' => $provider->id,
    ]);
    PayoutRequest::factory()->create([
        'amount' => 50,
        'status' => PayoutStatusEnum::Submitted,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $withdrawB->id,
        'recipient_type' => $provider::class,
        'recipient_id' => $provider->id,
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action([AuthController::class, 'profile']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Auth/Profile/Index')
            ->where('provider.wallet.amount_in_transfer', 150)
        );
});

test('completed and failed PayoutRequests are excluded from amount_in_transfer', function () {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    $pendingWithdraw = createWithdrawFor($provider, ['amount' => 80]);
    $completedWithdraw = createWithdrawFor($provider, ['amount' => 200]);
    $failedWithdraw = createWithdrawFor($provider, ['amount' => 300]);

    PayoutRequest::factory()->create([
        'amount' => 80,
        'status' => PayoutStatusEnum::Pending,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $pendingWithdraw->id,
        'recipient_type' => $provider::class,
        'recipient_id' => $provider->id,
    ]);
    PayoutRequest::factory()->create([
        'amount' => 200,
        'status' => PayoutStatusEnum::Completed,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $completedWithdraw->id,
        'recipient_type' => $provider::class,
        'recipient_id' => $provider->id,
    ]);
    PayoutRequest::factory()->create([
        'amount' => 300,
        'status' => PayoutStatusEnum::Failed,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $failedWithdraw->id,
        'recipient_type' => $provider::class,
        'recipient_id' => $provider->id,
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action([AuthController::class, 'profile']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('provider.wallet.amount_in_transfer', 80)
        );
});

test('a provider with no PayoutRequests at all sees amount_in_transfer as zero, not null or an error', function () {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->get(action([AuthController::class, 'profile']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('provider.wallet.amount_in_transfer', 0)
        );
});

test('amount_in_transfer only sums payouts where recipient is the authenticated provider, not other providers', function () {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    $other = createWalletProvider();

    $ownWithdraw = createWithdrawFor($provider, ['amount' => 40]);
    $otherWithdraw = createWithdrawFor($other, ['amount' => 999]);

    PayoutRequest::factory()->create([
        'amount' => 40,
        'status' => PayoutStatusEnum::Pending,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $ownWithdraw->id,
        'recipient_type' => $provider::class,
        'recipient_id' => $provider->id,
    ]);
    PayoutRequest::factory()->create([
        'amount' => 999,
        'status' => PayoutStatusEnum::Submitted,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $otherWithdraw->id,
        'recipient_type' => $other::class,
        'recipient_id' => $other->id,
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action([AuthController::class, 'profile']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('provider.wallet.amount_in_transfer', 40)
        );
});

test('the wallet statements page (Provider/Auth/Profile/wallet) also exposes amount_in_transfer via the same computation, not a duplicated one', function () {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    $withdraw = createWithdrawFor($provider, ['amount' => 125]);

    PayoutRequest::factory()->create([
        'amount' => 125,
        'status' => PayoutStatusEnum::Submitted,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $withdraw->id,
        'recipient_type' => $provider::class,
        'recipient_id' => $provider->id,
    ]);

    $expected = app(PayoutService::class)
        ->sumInProgressAmountForRecipient($provider);

    expect($expected)->toBe(125.0);

    $this->actingAs($provider, 'provider')
        ->get(action([AuthController::class, 'profile']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('provider.wallet.amount_in_transfer', 125)
        );

    $this->actingAs($provider, 'provider')
        ->get(action([AuthController::class, 'statements']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Auth/Profile/wallet')
            ->where('provider.wallet.amount_in_transfer', 125)
        );
});
