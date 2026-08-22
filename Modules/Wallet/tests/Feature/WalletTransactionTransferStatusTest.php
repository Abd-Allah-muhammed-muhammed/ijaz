<?php

use App\Enums\OperationStatusEnum;
use Laravel\Sanctum\Sanctum;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Models\PayoutRequest;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Http\Controllers\Api\V1\WalletController;
use Modules\Wallet\Http\Controllers\Dashboard\WithdrawRequestController as DashboardWithdrawRequestController;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WithdrawRequestService;

test('a wallet transaction for an approved withdraw exposes transfer_status as in_progress when the payout is pending or submitted', function (PayoutStatusEnum $payoutStatus) {
    withoutWalletLocaleMiddleware();
    $user = createWalletUser();
    fundWallet($user, 500);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 100, userNotes: null),
    );
    $admin = createWalletAdmin();
    $this->actingAs($admin, 'admin')
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdraw->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect();

    $payout = PayoutRequest::query()
        ->where('operation_type', WithdrawRequest::class)
        ->where('operation_id', $withdraw->id)
        ->first();

    expect($payout)->not->toBeNull();
    $payout->update(['status' => $payoutStatus]);

    Sanctum::actingAs($user);

    $item = collect($this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 20]))
        ->assertSuccessful()
        ->json('data.items'))
        ->first(fn (array $row): bool => $row['operation_id'] === $withdraw->id
            && (float) $row['debit'] > 0);

    expect($item)->not->toBeNull()
        ->and($item['transfer_status'])->toMatchArray([
            'value' => 'in_progress',
            'label' => __('payout.transfer_status.in_progress'),
            'color' => 'warning',
        ]);
})->with([
    'pending' => PayoutStatusEnum::Pending,
    'submitted' => PayoutStatusEnum::Submitted,
]);

test('a wallet transaction exposes transfer_status as transferred when the payout is completed', function () {
    withoutWalletLocaleMiddleware();
    $user = createWalletUser();
    fundWallet($user, 500);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 100, userNotes: null),
    );
    $admin = createWalletAdmin();
    $this->actingAs($admin, 'admin')
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdraw->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect();

    PayoutRequest::query()
        ->where('operation_type', WithdrawRequest::class)
        ->where('operation_id', $withdraw->id)
        ->update(['status' => PayoutStatusEnum::Completed]);

    Sanctum::actingAs($user);

    $item = collect($this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 20]))
        ->assertSuccessful()
        ->json('data.items'))
        ->first(fn (array $row): bool => $row['operation_id'] === $withdraw->id
            && (float) $row['debit'] > 0);

    expect($item['transfer_status'])->toMatchArray([
        'value' => 'transferred',
        'label' => __('payout.transfer_status.transferred'),
        'color' => 'success',
    ]);
});

test('a wallet transaction exposes transfer_status as delayed when the payout is failed', function () {
    withoutWalletLocaleMiddleware();
    $user = createWalletUser();
    fundWallet($user, 500);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 100, userNotes: null),
    );
    $admin = createWalletAdmin();
    $this->actingAs($admin, 'admin')
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdraw->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect();

    PayoutRequest::query()
        ->where('operation_type', WithdrawRequest::class)
        ->where('operation_id', $withdraw->id)
        ->update(['status' => PayoutStatusEnum::Failed]);

    Sanctum::actingAs($user);

    $item = collect($this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 20]))
        ->assertSuccessful()
        ->json('data.items'))
        ->first(fn (array $row): bool => $row['operation_id'] === $withdraw->id
            && (float) $row['debit'] > 0);

    expect($item['transfer_status'])->toMatchArray([
        'value' => 'delayed',
        'label' => __('payout.transfer_status.delayed'),
        'color' => 'danger',
    ]);
});

test('a wallet transaction exposes transfer_status as the withdraw operation status when no payout exists yet for the operation', function () {
    withoutWalletLocaleMiddleware();
    $user = createWalletUser();
    fundWallet($user, 500);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 100, userNotes: null),
    );

    expect(PayoutRequest::query()->where('operation_id', $withdraw->id)->exists())->toBeFalse();

    Sanctum::actingAs($user);

    $item = collect($this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 20]))
        ->assertSuccessful()
        ->json('data.items'))
        ->first(fn (array $row): bool => $row['operation_id'] === $withdraw->id);

    expect($item)->not->toBeNull()
        ->and($item['transfer_status'])->toMatchArray(OperationStatusEnum::Pending->toArray());
});

test('a wallet transaction for non-withdraw operations exposes a generic completed transfer_status without loading the operation relation', function () {
    withoutWalletLocaleMiddleware();
    $user = createWalletUser();
    fundWallet($user, 250);

    Sanctum::actingAs($user);

    $item = collect($this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 20]))
        ->assertSuccessful()
        ->json('data.items'))
        ->first(fn (array $row): bool => (float) $row['credit'] > 0);

    expect($item)->not->toBeNull()
        ->and(array_key_exists('transfer_status', $item))->toBeTrue()
        ->and($item['transfer_status'])->toMatchArray([
            'value' => 'completed',
            'label' => __('completed'),
            'color' => 'success',
        ]);
});

test('transfer_status never includes admin names, gateway_reference, submitted_by_admin_id, or proof image data', function () {
    withoutWalletLocaleMiddleware();
    $user = createWalletUser();
    fundWallet($user, 500);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 100, userNotes: null),
    );
    $admin = createWalletAdmin();
    $this->actingAs($admin, 'admin')
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdraw->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect();

    $payout = PayoutRequest::query()
        ->where('operation_type', WithdrawRequest::class)
        ->where('operation_id', $withdraw->id)
        ->firstOrFail();

    $payout->update([
        'status' => PayoutStatusEnum::Submitted,
        'gateway_reference' => 'SECRET-BANK-REF-999',
        'submitted_by_admin_id' => $admin->id,
        'maker_admin_id' => $admin->id,
        'failure_reason' => 'should-not-leak',
    ]);

    Sanctum::actingAs($user);

    $item = collect($this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 20]))
        ->assertSuccessful()
        ->json('data.items'))
        ->first(fn (array $row): bool => $row['operation_id'] === $withdraw->id
            && (float) $row['debit'] > 0);

    $encoded = json_encode($item);
    $status = $item['transfer_status'];

    expect($status)->toBeArray()
        ->and(array_keys($status))->toBe(['value', 'label', 'color'])
        ->and($status)->not->toHaveKeys([
            'gateway_reference',
            'submitted_by_admin_id',
            'maker_admin_id',
            'failure_reason',
            'transfer_proof_url',
            'processed_by_admin_id',
        ])
        ->and($encoded)->not->toContain('SECRET-BANK-REF-999')
        ->and($encoded)->not->toContain('should-not-leak')
        ->and($encoded)->not->toContain($admin->name)
        ->and($encoded)->not->toContain('submitted_by_admin')
        ->and($encoded)->not->toContain('gateway_reference')
        ->and($encoded)->not->toContain('transfer_proof')
        ->and($encoded)->not->toContain('maker_admin');
});
