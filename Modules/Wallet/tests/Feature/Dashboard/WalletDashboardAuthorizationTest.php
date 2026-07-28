<?php

use App\Enums\OperationStatusEnum;
use Modules\Wallet\Http\Controllers\Dashboard\TopUpRequestController;
use Modules\Wallet\Http\Controllers\Dashboard\WithdrawRequestController;

test('admin without topUpRequests permission cannot access dashboard top-up routes', function () {
    withoutWalletLocaleMiddleware();

    $admin = createWalletAdmin(['show users']);
    $user = createWalletUser();
    $topUp = createTopUpFor($user, ['status' => OperationStatusEnum::Pending->value]);

    $this->actingAs($admin, 'admin')
        ->get(action([TopUpRequestController::class, 'index']))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->get(action([TopUpRequestController::class, 'show'], ['topUpRequest' => $topUp->id]))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->put(action([TopUpRequestController::class, 'updateStatus'], ['topUpRequest' => $topUp->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])
        ->assertForbidden();

    expect($topUp->fresh()->status)->toBe(OperationStatusEnum::Pending);
});

test('admin without withdrawRequests permission cannot access dashboard withdraw routes', function () {
    withoutWalletLocaleMiddleware();

    $admin = createWalletAdmin(['show users']);
    $user = createWalletUser();
    $withdraw = createWithdrawFor($user, ['status' => OperationStatusEnum::Pending->value]);

    $this->actingAs($admin, 'admin')
        ->get(action([WithdrawRequestController::class, 'index']))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->get(action([WithdrawRequestController::class, 'show'], ['withdrawRequest' => $withdraw->id]))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->put(action([WithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdraw->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])
        ->assertForbidden();

    expect($withdraw->fresh()->status)->toBe(OperationStatusEnum::Pending);
});
