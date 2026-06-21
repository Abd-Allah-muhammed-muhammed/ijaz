<?php

use App\Enums\OperationStatusEnum;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Http\Controllers\Dashboard\WithdrawRequestController as DashboardWithdrawRequestController;
use Modules\Wallet\Http\Controllers\Provider\WithdrawController;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WalletService;

test('provider can list their withdraw requests', function () {
    withoutWalletLocaleMiddleware();
    $provider = createWalletProvider();
    createWithdrawFor($provider);
    WithdrawRequest::factory()->create();

    $this->actingAs($provider, 'provider')
        ->get(action([WithdrawController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/WithdrawRequests/Index')
            ->has('rows.data', 1)
        );
});

test('provider can create withdraw request with sufficient balance', function () {
    withoutWalletLocaleMiddleware();
    $provider = createWalletProvider();
    fundWallet($provider, 300);

    $this->actingAs($provider, 'provider')
        ->from(action([WithdrawController::class, 'index']))
        ->post(action([WithdrawController::class, 'store']), [
            'amount' => 100,
        ])->assertRedirect()
        ->assertSessionHas('success');

    expect(WithdrawRequest::query()->where('user_id', $provider->id)->exists())->toBeTrue()
        ->and((float) $provider->wallet->fresh()->pending_debit)->toBe(100.0);
});

test('provider cannot create withdraw with insufficient balance', function () {
    withoutWalletLocaleMiddleware();
    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->from(action([WithdrawController::class, 'index']))
        ->post(action([WithdrawController::class, 'store']), [
            'amount' => 50,
        ])->assertRedirect()
        ->assertSessionHas('error');

    expect(WithdrawRequest::query()->count())->toBe(0);
});

test('provider can cancel pending withdraw → reverses pending_debit', function () {
    withoutWalletLocaleMiddleware();
    $provider = createWalletProvider();
    fundWallet($provider, 200);
    $withdrawRequest = createWithdrawFor($provider, [
        'amount' => 80,
        'status' => OperationStatusEnum::Pending,
    ]);

    DB::transaction(fn () => app(WalletService::class)->addPendingDebit($provider, 80, $withdrawRequest));

    $this->actingAs($provider, 'provider')
        ->delete(action([WithdrawController::class, 'destroy'], ['withdraw_request' => $withdrawRequest->id]))
        ->assertRedirect(route('provider.withdraw-requests.index'));

    expect(WithdrawRequest::query()->find($withdrawRequest->id))->toBeNull()
        ->and((float) $provider->wallet->fresh()->pending_debit)->toBe(0.0);
});

test('admin can list all withdraw requests', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    WithdrawRequest::factory()->count(2)->create();

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardWithdrawRequestController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/WithdrawRequests/Index')
            ->has('rows.data', 2)
        );
});

test('admin can approve withdraw → debits balance and clears pending', function () {
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
        ->and($withdrawRequest->fresh()->status)->toBe(OperationStatusEnum::Approved);
});

test('admin can reject withdraw → only clears pending', function () {
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
            'status' => OperationStatusEnum::Rejected->value,
        ])->assertRedirect(route('dashboard.withdraw-requests.index'))
        ->assertSessionHas('success');

    $wallet = $user->wallet->fresh();

    expect((float) $wallet->balance)->toBe(250.0)
        ->and((float) $wallet->pending_debit)->toBe(0.0)
        ->and($withdrawRequest->fresh()->status)->toBe(OperationStatusEnum::Rejected);
});

test('admin cannot process already-processed withdraw', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    $withdrawRequest = createWithdrawFor($user, ['status' => OperationStatusEnum::Approved->value]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardWithdrawRequestController::class, 'show'], ['withdrawRequest' => $withdrawRequest->id]))
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdrawRequest->id]), [
            'status' => OperationStatusEnum::Rejected->value,
        ])->assertRedirect()
        ->assertSessionHas('error');
});
