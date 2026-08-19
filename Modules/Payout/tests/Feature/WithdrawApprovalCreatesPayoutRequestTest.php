<?php

use App\Enums\OperationStatusEnum;
use Illuminate\Support\Facades\DB;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Models\PayoutRequest;
use Modules\Wallet\Http\Controllers\Dashboard\WithdrawRequestController as DashboardWithdrawRequestController;
use Modules\Wallet\Http\Controllers\Provider\WithdrawController;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WalletService;

test('approving a withdraw request creates a PayoutRequest linked to it via operation_type/operation_id, with status pending', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    fundWallet($user, 250);
    $withdrawRequest = createWithdrawFor($user, [
        'amount' => 100,
        'status' => OperationStatusEnum::Pending,
    ]);

    DB::transaction(fn () => app(WalletService::class)->addPendingDebit($user, 100, $withdrawRequest));

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardWithdrawRequestController::class, 'index']))
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdrawRequest->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect(route('dashboard.withdraw-requests.index'))
        ->assertSessionHas('success');

    $payout = PayoutRequest::query()
        ->where('operation_type', WithdrawRequest::class)
        ->where('operation_id', $withdrawRequest->id)
        ->first();

    expect($payout)->not->toBeNull()
        ->and($payout->status)->toBe(PayoutStatusEnum::Pending)
        ->and($payout->maker_admin_id)->toBe($admin->id);
});

test('the PayoutRequest amount and recipient match the withdraw request exactly', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    fundWallet($user, 250);
    $withdrawRequest = createWithdrawFor($user, [
        'amount' => 100,
        'status' => OperationStatusEnum::Pending,
    ]);

    DB::transaction(fn () => app(WalletService::class)->addPendingDebit($user, 100, $withdrawRequest));

    $this->actingAs($admin, 'admin')
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdrawRequest->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect();

    $payout = PayoutRequest::query()
        ->where('operation_type', WithdrawRequest::class)
        ->where('operation_id', $withdrawRequest->id)
        ->first();

    expect($payout)->not->toBeNull()
        ->and((float) $payout->amount)->toBe(100.0)
        ->and($payout->recipient_type)->toBe($user::class)
        ->and((int) $payout->recipient_id)->toBe((int) $user->id);
});

test('WithdrawRequest approval behavior (existing tests) is completely unchanged — this is purely additive', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    fundWallet($user, 250);
    $withdrawRequest = createWithdrawFor($user, [
        'amount' => 100,
        'status' => OperationStatusEnum::Pending,
    ]);

    DB::transaction(fn () => app(WalletService::class)->addPendingDebit($user, 100, $withdrawRequest));

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardWithdrawRequestController::class, 'index']))
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdrawRequest->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect(route('dashboard.withdraw-requests.index'))
        ->assertSessionHas('success');

    $wallet = $user->wallet->fresh();

    expect((float) $wallet->balance)->toBe(150.0)
        ->and((float) $wallet->pending_debit)->toBe(0.0)
        ->and($withdrawRequest->fresh()->status)->toBe(OperationStatusEnum::Approved)
        ->and($withdrawRequest->fresh()->admin_id)->toBe($admin->id);
});

test('rejecting or cancelling a withdraw request does NOT create a PayoutRequest', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    fundWallet($user, 250);
    $rejected = createWithdrawFor($user, [
        'amount' => 100,
        'status' => OperationStatusEnum::Pending,
    ]);
    DB::transaction(fn () => app(WalletService::class)->addPendingDebit($user, 100, $rejected));

    $this->actingAs($admin, 'admin')
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $rejected->id]), [
            'status' => OperationStatusEnum::Rejected->value,
        ])->assertRedirect();

    $provider = createWalletProvider();
    fundWallet($provider, 200);
    $cancelled = createWithdrawFor($provider, [
        'amount' => 80,
        'status' => OperationStatusEnum::Pending,
    ]);
    DB::transaction(fn () => app(WalletService::class)->addPendingDebit($provider, 80, $cancelled));

    $this->actingAs($provider, 'provider')
        ->delete(action([WithdrawController::class, 'destroy'], ['withdraw_request' => $cancelled->id]))
        ->assertRedirect(route('provider.withdraw-requests.index'));

    expect(PayoutRequest::query()->count())->toBe(0)
        ->and(WithdrawRequest::query()->find($cancelled->id))->toBeNull()
        ->and($rejected->fresh()->status)->toBe(OperationStatusEnum::Rejected);
});
