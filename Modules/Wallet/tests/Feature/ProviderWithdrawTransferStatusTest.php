<?php

use App\Enums\OperationStatusEnum;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Models\PayoutRequest;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Http\Controllers\Api\V1\WalletController;
use Modules\Wallet\Http\Controllers\Dashboard\WithdrawRequestController as DashboardWithdrawRequestController;
use Modules\Wallet\Http\Controllers\Provider\WithdrawController;
use Modules\Wallet\Http\Resources\Dashboard\WithdrawResource;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WithdrawRequestService;

test('provider web withdraw-requests index exposes transfer_status using PayoutStatusEnum::toProviderStatus()', function () {
    withoutWalletLocaleMiddleware();
    $provider = createWalletProvider();
    fundWallet($provider, 500);
    $withdraw = app(WithdrawRequestService::class)->create(
        $provider,
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
        ->update(['status' => PayoutStatusEnum::Submitted]);

    $expected = PayoutStatusEnum::Submitted->toProviderStatus();

    $this->actingAs($provider, 'provider')
        ->get(action([WithdrawController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/WithdrawRequests/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $withdraw->id)
            ->where('rows.data.0.transfer_status.value', $expected['value'])
            ->where('rows.data.0.transfer_status.label', $expected['label'])
            ->where('rows.data.0.transfer_status.color', $expected['color'])
        );
});

test('transfer_status on the web dashboard matches the same value the mobile API would return for the same withdraw request', function () {
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

    $mobileStatus = collect($this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 20]))
        ->assertSuccessful()
        ->json('data.items'))
        ->first(fn (array $row): bool => $row['operation_id'] === $withdraw->id
            && (float) $row['debit'] > 0)['transfer_status'] ?? null;

    $webStatus = WithdrawResource::make(
        $withdraw->fresh()->load('payoutRequest')
    )->resolve()['transfer_status'] ?? null;

    expect($mobileStatus)->not->toBeNull()
        ->and($webStatus)->toBe($mobileStatus)
        ->and($webStatus)->toMatchArray(PayoutStatusEnum::Completed->toProviderStatus());
});

test('provider web withdraw-requests index does not leak admin/audit fields (maker_admin_id, submitted_by_admin_id, gateway_reference, proof image, failure_reason) — only the mapped transfer_status', function () {
    withoutWalletLocaleMiddleware();
    $provider = createWalletProvider();
    fundWallet($provider, 500);
    $withdraw = app(WithdrawRequestService::class)->create(
        $provider,
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
        'gateway_reference' => 'SECRET-BANK-REF-WEB',
        'submitted_by_admin_id' => $admin->id,
        'failure_reason' => 'web-should-not-leak',
    ]);

    $props = $this->actingAs($provider, 'provider')
        ->get(action([WithdrawController::class, 'index']))
        ->assertSuccessful()
        ->inertiaProps();

    $row = collect($props['rows']['data'] ?? [])->firstWhere('id', $withdraw->id);
    $encoded = json_encode($row);

    expect($row)->not->toBeNull()
        ->and($row['transfer_status'])->toBeArray()
        ->and(array_keys($row['transfer_status']))->toBe(['value', 'label', 'color'])
        ->and($row)->not->toHaveKeys([
            'maker_admin_id',
            'submitted_by_admin_id',
            'gateway_reference',
            'failure_reason',
            'transfer_proof_url',
            'processed_by_admin_id',
        ])
        ->and($encoded)->not->toContain('SECRET-BANK-REF-WEB')
        ->and($encoded)->not->toContain('web-should-not-leak')
        ->and($encoded)->not->toContain($admin->name)
        ->and($encoded)->not->toContain('submitted_by_admin')
        ->and($encoded)->not->toContain('gateway_reference')
        ->and($encoded)->not->toContain('transfer_proof')
        ->and($encoded)->not->toContain('maker_admin');
});

test('a withdraw request with no linked payout yet exposes transfer_status as null on the web dashboard too', function () {
    withoutWalletLocaleMiddleware();
    $provider = createWalletProvider();
    $withdraw = createWithdrawFor($provider, [
        'status' => OperationStatusEnum::Pending->value,
    ]);

    expect(PayoutRequest::query()->where('operation_id', $withdraw->id)->exists())->toBeFalse();

    $this->actingAs($provider, 'provider')
        ->get(action([WithdrawController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/WithdrawRequests/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $withdraw->id)
            ->where('rows.data.0.transfer_status', null)
        );
});

test('the web dashboard withdraw-requests list query does not introduce N+1 queries when loading transfer_status for a page of results', function () {
    withoutWalletLocaleMiddleware();
    $provider = createWalletProvider();
    fundWallet($provider, 2000);
    $admin = createWalletAdmin();

    $withdrawIds = [];
    for ($i = 0; $i < 5; $i++) {
        $withdraw = app(WithdrawRequestService::class)->create(
            $provider,
            new CreateWithdrawData(amount: 50, userNotes: null),
        );
        $this->actingAs($admin, 'admin')
            ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdraw->id]), [
                'status' => OperationStatusEnum::Approved->value,
            ])->assertRedirect();
        $withdrawIds[] = $withdraw->id;
    }

    expect(PayoutRequest::query()->whereIn('operation_id', $withdrawIds)->count())->toBe(5);

    Model::preventLazyLoading();

    try {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($provider, 'provider')
            ->get(action([WithdrawController::class, 'index'], ['perPage' => 16]))
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Provider/WithdrawRequests/Index')
                ->has('rows.data', 5)
            );

        $payoutSelects = collect(DB::getQueryLog())
            ->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'payout_requests'));

        expect($payoutSelects->count())->toBeLessThanOrEqual(1);
    } finally {
        DB::disableQueryLog();
        Model::preventLazyLoading(false);
    }
});
