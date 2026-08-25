<?php

use App\Enums\OperationStatusEnum;
use App\Http\Controllers\Provider\AuthController;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Http\Controllers\Dashboard\WithdrawRequestController as DashboardWithdrawRequestController;
use Modules\Wallet\Http\Resources\Dashboard\WalletTransactionResource;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Services\WithdrawRequestService;
use Modules\Wallet\Support\WalletTransactionDisplay;
use Modules\Wallet\Support\WalletTransactionQueryFilters;

test('wallet statements no longer show a withdraw_requested hold row once its terminal sibling exists, same filtering as mobile', function (): void {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    fundWallet($provider, 500);

    $withdraw = app(WithdrawRequestService::class)->create(
        $provider,
        new CreateWithdrawData(amount: 200, userNotes: null),
    );

    $requestedId = WalletTransaction::query()
        ->where('operation_id', $withdraw->id)
        ->where('entry_kind', WalletTransactionEntryKindEnum::WithdrawRequested)
        ->value('id');

    expect($requestedId)->not->toBeNull();

    $admin = createWalletAdmin();
    $this->actingAs($admin, 'admin')
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdraw->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect();

    $approvedId = WalletTransaction::query()
        ->where('operation_id', $withdraw->id)
        ->where('entry_kind', WalletTransactionEntryKindEnum::WithdrawApproved)
        ->value('id');

    expect($approvedId)->not->toBeNull();

    $statementIds = collect(
        $this->actingAs($provider, 'provider')
            ->get(action([AuthController::class, 'statements']))
            ->assertSuccessful()
            ->inertiaProps()['transactions']['data'] ?? []
    )->pluck('id');

    expect($statementIds)->toContain($approvedId)
        ->and($statementIds)->not->toContain($requestedId);

    $filteredIds = WalletTransaction::query()
        ->where('wallet_id', $provider->wallet->id)
        ->tap(fn ($query) => WalletTransactionQueryFilters::excludeInternalWithdrawRows($query))
        ->pluck('id');

    expect($statementIds->sort()->values()->all())->toBe($filteredIds->sort()->values()->all());
});

test('wallet statements header tiles all format numbers consistently with 2 decimals, no unformatted raw integers', function (): void {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    fundWallet($provider, 100);

    $props = $this->actingAs($provider, 'provider')
        ->get(action([AuthController::class, 'statements']))
        ->assertSuccessful()
        ->inertiaProps();

    $wallet = $props['provider']['wallet'] ?? [];

    foreach (['balance', 'pending_debit', 'amount_in_transfer', 'total_earning'] as $key) {
        expect($wallet)->toHaveKey($key)
            ->and($wallet[$key])->toMatch('/^\d+\.\d{2}$/');
    }

    foreach (['total_spent', 'credit', 'pending_credit', 'debit'] as $key) {
        expect($wallet)->toHaveKey($key)
            ->and($wallet[$key])->toMatch('/^\d+\.\d{2}$/');
    }
});

test('wallet statements rows always expose a non-null transfer_status badge on the frontend', function (): void {
    $source = file_get_contents(resource_path('js/apps/provider/pages/Auth/Profile/wallet.tsx'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('transfer_status')
        ->and($source)->toContain('row.transfer_status.color')
        ->and($source)->toContain('row.transfer_status.label')
        ->and($source)->not->toContain('text-muted">—');
});

test('the redesigned statements table combines credit/debit/pending into a single signed amount field from the backend, reusing WalletTransactionDisplay (not a new formula)', function (): void {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    fundWallet($provider, 250);

    app(WithdrawRequestService::class)->create(
        $provider,
        new CreateWithdrawData(amount: 80, userNotes: null),
    );

    $transaction = WalletTransaction::query()
        ->where('wallet_id', $provider->wallet->id)
        ->where('pending_debit', '>', 0)
        ->with(['operation.payoutRequest'])
        ->firstOrFail();

    $payload = WalletTransactionResource::make($transaction)->resolve();

    $expectedAmount = WalletTransactionDisplay::amount(
        (float) $transaction->credit,
        (float) $transaction->debit,
        (float) $transaction->pending_credit,
        (float) $transaction->pending_debit,
    );

    expect($payload['amount'])->toBe($expectedAmount)
        ->and($payload['is_pending'])->toBeTrue();

    $props = $this->actingAs($provider, 'provider')
        ->get(action([AuthController::class, 'statements']))
        ->assertSuccessful()
        ->inertiaProps();

    $row = collect($props['transactions']['data'] ?? [])
        ->firstWhere('id', $transaction->id);

    expect($row)->not->toBeNull()
        ->and((float) $row['amount'])->toBe($expectedAmount)
        ->and($row)->toHaveKey('is_pending');
});
