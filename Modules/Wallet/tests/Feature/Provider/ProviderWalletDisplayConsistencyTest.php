<?php

use App\Enums\OperationStatusEnum;
use App\Http\Controllers\Provider\AuthController;
use App\Http\Controllers\Provider\HomeController;
use Modules\Payment\Enums\PaymentMethodEnum;
use Modules\Wallet\DTOs\CreateTopUpData;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Http\Controllers\Dashboard\TopUpRequestController as DashboardTopUpRequestController;
use Modules\Wallet\Http\Controllers\Provider\WithdrawController;
use Modules\Wallet\Http\Resources\Dashboard\WalletTransactionResource;
use Modules\Wallet\Http\Resources\Dashboard\WithdrawResource;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Services\TopUpRequestService;
use Modules\Wallet\Services\WithdrawRequestService;
use Modules\Wallet\Support\WalletTransactionDisplay;

test('a wallet transaction reference is derived from the operation_id, matching the same ref shown in the description text', function (): void {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    fundWallet($provider, 100);

    app(WithdrawRequestService::class)->create(
        $provider,
        new CreateWithdrawData(amount: 50, userNotes: null),
    );

    $transaction = WalletTransaction::query()
        ->where('wallet_id', $provider->wallet->id)
        ->where('pending_debit', '>', 0)
        ->with(['operation.payoutRequest'])
        ->firstOrFail();

    $expectedRef = WalletTransactionDisplay::operationReference($transaction->operation_id);

    $payload = WalletTransactionResource::make($transaction)->resolve();

    expect($payload['reference_short'])->toBe($expectedRef)
        ->and($transaction->description)->toContain($expectedRef);

    $props = $this->actingAs($provider, 'provider')
        ->get(action([AuthController::class, 'statements']))
        ->assertSuccessful()
        ->inertiaProps();

    $row = collect($props['transactions']['data'] ?? [])
        ->firstWhere('id', $transaction->id);

    expect($row)->not->toBeNull()
        ->and($row['reference_short'])->toBe($expectedRef)
        ->and($row['reference_short'])->not->toBe(strtoupper(substr((string) $transaction->id, -8)));
});

test('a withdraw wallet transaction row with no linked PayoutRequest shows the withdraw request own status (pending/rejected), not null', function (): void {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    fundWallet($provider, 200);

    $withdraw = app(WithdrawRequestService::class)->create(
        $provider,
        new CreateWithdrawData(amount: 75, userNotes: null),
    );

    $transaction = WalletTransaction::query()
        ->where('operation_id', $withdraw->id)
        ->where('pending_debit', '>', 0)
        ->with(['operation.payoutRequest'])
        ->firstOrFail();

    $payload = WalletTransactionResource::make($transaction)->resolve();

    expect($payload['transfer_status'])->toMatchArray(OperationStatusEnum::Pending->toArray());

    $props = $this->actingAs($provider, 'provider')
        ->get(action([AuthController::class, 'statements']))
        ->assertSuccessful()
        ->inertiaProps();

    $row = collect($props['transactions']['data'] ?? [])
        ->firstWhere('id', $transaction->id);

    expect($row['transfer_status'])->toMatchArray(OperationStatusEnum::Pending->toArray());
});

test('a top-up wallet transaction row shows its own OperationStatusEnum status when no comparable payout concept applies', function (): void {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    $admin = createWalletAdmin();

    $topUp = app(TopUpRequestService::class)->create(
        $provider,
        new CreateTopUpData(
            amount: 120,
            paymentMethod: PaymentMethodEnum::Offline,
            paymentDriver: null,
            transactionImage: 'media/test-receipt.png',
            userNotes: null,
        ),
    )['topUpRequest'];

    $this->actingAs($admin, 'admin')
        ->put(action([DashboardTopUpRequestController::class, 'updateStatus'], ['topUpRequest' => $topUp->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect();

    $transaction = WalletTransaction::query()
        ->where('operation_id', $topUp->id)
        ->with(['operation'])
        ->firstOrFail();

    $payload = WalletTransactionResource::make($transaction)->resolve();

    expect($payload['transfer_status'])->toMatchArray(OperationStatusEnum::Approved->toArray());
});

test('a non-withdraw non-topup wallet transaction row (e.g. Order) shows a generic Pending or Completed status derived from is_pending, never null', function (): void {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    fundWallet($provider, 150);

    $transaction = WalletTransaction::query()
        ->where('wallet_id', $provider->wallet->id)
        ->where('credit', '>', 0)
        ->firstOrFail();

    $payload = WalletTransactionResource::make($transaction)->resolve();

    expect($payload['transfer_status'])->toBeArray()
        ->and($payload['transfer_status']['value'])->toBe('completed')
        ->and($payload['transfer_status']['label'])->toBe(__('completed'))
        ->and($payload['transfer_status']['color'])->toBe('success');
});

test('the withdraw-requests list page (WithdrawResource) also applies the same fallback — no blank Transfer status cell for a pending withdraw with no payout yet', function (): void {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    fundWallet($provider, 300);

    $withdraw = app(WithdrawRequestService::class)->create(
        $provider,
        new CreateWithdrawData(amount: 100, userNotes: null),
    );

    $resourcePayload = WithdrawResource::make($withdraw->fresh()->load('payoutRequest'))->resolve();

    expect($resourcePayload['transfer_status'])->toMatchArray(OperationStatusEnum::Pending->toArray());

    $props = $this->actingAs($provider, 'provider')
        ->get(action([WithdrawController::class, 'index']))
        ->assertSuccessful()
        ->inertiaProps();

    $row = collect($props['rows']['data'] ?? [])->firstWhere('id', $withdraw->id);

    expect($row['transfer_status'])->toMatchArray(OperationStatusEnum::Pending->toArray());
});

test('WalletQuickActions on the Withdraw Requests page renders only the withdraw trigger, not recharge', function (): void {
    $source = file_get_contents(resource_path('js/apps/provider/pages/WithdrawRequests/Index.tsx'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('WithdrawTrigger')
        ->and($source)->not->toContain('RechargeTrigger')
        ->and($source)->not->toContain('<WalletQuickActions');
});

test('WalletQuickActions on the Top-up Requests page renders only the recharge trigger, not withdraw', function (): void {
    $source = file_get_contents(resource_path('js/apps/provider/pages/TopUpRequests/Index.tsx'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('RechargeTrigger')
        ->and($source)->not->toContain('WithdrawTrigger')
        ->and($source)->not->toContain('<WalletQuickActions');
});

test('home recent transactions expose a non-null transfer_status via the shared resolver', function (): void {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    fundWallet($provider, 80);

    $props = $this->actingAs($provider, 'provider')
        ->get(action(HomeController::class))
        ->assertSuccessful()
        ->inertiaProps();

    $transaction = collect($props['recentTransactions'] ?? [])->first();

    expect($transaction)->not->toBeNull()
        ->and($transaction['transfer_status'])->toBeArray()
        ->and($transaction['transfer_status'])->toHaveKeys(['value', 'label', 'color']);
});

test('the view all wallet details accordion toggles open and closed via React state', function (): void {
    $source = file_get_contents(resource_path('js/apps/provider/layouts/AccountLayout.tsx'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('showWalletDetails')
        ->and($source)->toContain('setShowWalletDetails')
        ->and($source)->toMatch('/showWalletDetails\s*&&/');
});
