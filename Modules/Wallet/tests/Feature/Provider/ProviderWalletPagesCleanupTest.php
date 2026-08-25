<?php

use App\Enums\OperationStatusEnum;
use App\Http\Controllers\Provider\AuthController;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Models\PayoutRequest;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Http\Controllers\Dashboard\WithdrawRequestController as DashboardWithdrawRequestController;
use Modules\Wallet\Http\Controllers\Provider\TopUpController;
use Modules\Wallet\Http\Controllers\Provider\WithdrawController;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WithdrawRequestService;

test('provider top-up requests index page can open the create/recharge flow directly from the list page', function (): void {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->get(action([TopUpController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Provider/TopUpRequests/Index'));

    $source = file_get_contents(resource_path('js/apps/provider/pages/TopUpRequests/Index.tsx'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('RechargeTrigger')
        ->and($source)->toContain('addButton');
});

test('provider withdraw requests index page can open the create/withdraw flow directly from the list page', function (): void {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->get(action([WithdrawController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page->component('Provider/WithdrawRequests/Index'));

    $source = file_get_contents(resource_path('js/apps/provider/pages/WithdrawRequests/Index.tsx'));

    expect($source)->not->toBeFalse()
        ->and($source)->toContain('WithdrawTrigger')
        ->and($source)->toContain('addButton');
});

test('wallet statements rows expose transfer_status for withdraw operations, consistent with other surfaces', function (): void {
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

    $props = $this->actingAs($provider, 'provider')
        ->get(action([AuthController::class, 'statements']))
        ->assertSuccessful()
        ->inertiaProps();

    $row = collect($props['transactions']['data'] ?? [])
        ->first(fn (array $row): bool => (string) ($row['operation_id'] ?? '') === (string) $withdraw->id
            && (float) ($row['amount'] ?? 0) === 100.0
            && ($row['is_pending'] ?? true) === false);

    expect($row)->not->toBeNull()
        ->and($row['transfer_status']['value'])->toBe($expected['value'])
        ->and($row['transfer_status']['label'])->toBe($expected['label'])
        ->and($row['transfer_status']['color'])->toBe($expected['color']);
});
