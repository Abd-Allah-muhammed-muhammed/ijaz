<?php

use App\Enums\OperationStatusEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Payment\Enums\PaymentDriverEnum;
use Modules\Payment\Enums\PaymentMethodEnum;
use Modules\Wallet\Http\Controllers\Dashboard\TopUpRequestController as DashboardTopUpRequestController;
use Modules\Wallet\Http\Controllers\Provider\TopUpController;
use Modules\Wallet\Models\TopUpRequest;

test('unauthenticated cannot access top-up routes → 401', function () {
    withoutWalletLocaleMiddleware();

    $this->get(action([TopUpController::class, 'index']))
        ->assertRedirect();
});

test('provider can list their top-up requests', function () {
    withoutWalletLocaleMiddleware();
    $provider = createWalletProvider();
    createTopUpFor($provider);
    TopUpRequest::factory()->create();

    $this->actingAs($provider, 'provider')
        ->get(action([TopUpController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/TopUpRequests/Index')
            ->has('rows.data', 1)
        );
});

test('provider can create online top-up', function () {
    withoutWalletLocaleMiddleware();
    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->postJson(action([TopUpController::class, 'store']), [
            'amount' => 120,
            'payment_method' => PaymentMethodEnum::Online->value,
            'payment_driver' => PaymentDriverEnum::Testing->value,
        ])->assertSuccessful()
        ->assertJsonStructure(['data' => ['url', 'transaction_id']]);

    expect(TopUpRequest::query()->where('user_id', $provider->id)->exists())->toBeTrue();
});

test('provider can create offline top-up', function () {
    Storage::fake('local');
    withoutWalletLocaleMiddleware();
    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->postJson(action([TopUpController::class, 'store']), [
            'amount' => 90,
            'payment_method' => PaymentMethodEnum::Offline->value,
            'user_notes' => 'Bank transfer reference',
            'transaction_image' => UploadedFile::fake()->image('receipt.jpg'),
        ])->assertSuccessful()
        ->assertJsonPath('data.status', 'pending');

    $topUp = TopUpRequest::query()->where('user_id', $provider->id)->first();

    expect($topUp)->not->toBeNull()
        ->and($topUp->payment_method)->toBe(PaymentMethodEnum::Offline)
        ->and($topUp->wallet_id)->toBe($provider->wallet->id);
});

test('provider can view top-up detail', function () {
    withoutWalletLocaleMiddleware();
    $provider = createWalletProvider();
    $topUp = createTopUpFor($provider);

    $this->actingAs($provider, 'provider')
        ->get(action([TopUpController::class, 'show'], ['top_up_request' => $topUp->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/TopUpRequests/Show')
            ->where('row.id', $topUp->id)
        );
});

test('provider can delete pending top-up', function () {
    withoutWalletLocaleMiddleware();
    $provider = createWalletProvider();
    $topUp = createTopUpFor($provider, ['status' => OperationStatusEnum::Pending]);

    $this->actingAs($provider, 'provider')
        ->delete(action([TopUpController::class, 'destroy'], ['top_up_request' => $topUp->id]))
        ->assertRedirect(route('provider.top-up-requests.index'));

    expect(TopUpRequest::query()->find($topUp->id))->toBeNull();
});

test('admin can list all top-up requests', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    TopUpRequest::factory()->count(2)->create();

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardTopUpRequestController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/TopUpRequests/Index')
            ->has('rows.data', 2)
        );
});

test('admin can view top-up detail', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    $topUp = createTopUpFor($user);

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardTopUpRequestController::class, 'show'], ['topUpRequest' => $topUp->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/TopUpRequests/Show')
            ->where('row.id', $topUp->id)
        );
});

test('admin can approve offline top-up → credits wallet', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    $topUp = createTopUpFor($user, [
        'amount' => 75,
        'payment_method' => PaymentMethodEnum::Offline->value,
        'status' => OperationStatusEnum::Pending->value,
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardTopUpRequestController::class, 'index']))
        ->put(action([DashboardTopUpRequestController::class, 'updateStatus'], ['topUpRequest' => $topUp->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect(route('dashboard.top-up-requests.index'))
        ->assertSessionHas('success');

    expect((float) $user->wallet->fresh()->balance)->toBe(75.0)
        ->and($topUp->fresh()->status)->toBe(OperationStatusEnum::Approved);
});

test('admin cannot approve already-processed top-up', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    $topUp = createTopUpFor($user, ['status' => OperationStatusEnum::Approved->value]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardTopUpRequestController::class, 'show'], ['topUpRequest' => $topUp->id]))
        ->put(action([DashboardTopUpRequestController::class, 'updateStatus'], ['topUpRequest' => $topUp->id]), [
            'status' => OperationStatusEnum::Rejected->value,
        ])->assertRedirect()
        ->assertSessionHas('error');
});

test('admin can reject top-up → no wallet change', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    $topUp = createTopUpFor($user, [
        'amount' => 100,
        'payment_method' => PaymentMethodEnum::Offline->value,
        'status' => OperationStatusEnum::Pending->value,
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardTopUpRequestController::class, 'index']))
        ->put(action([DashboardTopUpRequestController::class, 'updateStatus'], ['topUpRequest' => $topUp->id]), [
            'status' => OperationStatusEnum::Rejected->value,
        ])->assertRedirect(route('dashboard.top-up-requests.index'))
        ->assertSessionHas('success');

    expect((float) $user->wallet->fresh()->balance)->toBe(0.0)
        ->and($topUp->fresh()->status)->toBe(OperationStatusEnum::Rejected);
});

test('admin approving an online top-up request does not credit the wallet directly', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    fundWallet($user, 40);

    $topUp = createTopUpFor($user, [
        'amount' => 100,
        'payment_method' => PaymentMethodEnum::Online->value,
        'status' => OperationStatusEnum::Pending->value,
    ]);

    $balanceBefore = (float) $user->wallet->fresh()->balance;

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardTopUpRequestController::class, 'index']))
        ->put(action([DashboardTopUpRequestController::class, 'updateStatus'], ['topUpRequest' => $topUp->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect(route('dashboard.top-up-requests.index'))
        ->assertSessionHas('success');

    expect($topUp->fresh()->status)->toBe(OperationStatusEnum::Approved)
        ->and((float) $user->wallet->fresh()->balance)->toBe($balanceBefore)
        ->and((float) $user->wallet->fresh()->balance)->toBe(40.0);
});

test('approving a top-up request persists admin_id and admin_notes', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    $topUp = createTopUpFor($user, [
        'amount' => 50,
        'payment_method' => PaymentMethodEnum::Offline->value,
        'status' => OperationStatusEnum::Pending->value,
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardTopUpRequestController::class, 'index']))
        ->put(action([DashboardTopUpRequestController::class, 'updateStatus'], ['topUpRequest' => $topUp->id]), [
            'status' => OperationStatusEnum::Approved->value,
            'admin_notes' => 'Bank transfer verified',
        ])->assertRedirect(route('dashboard.top-up-requests.index'))
        ->assertSessionHas('success');

    $topUp->refresh();

    expect($topUp->status)->toBe(OperationStatusEnum::Approved)
        ->and($topUp->admin_id)->toBe($admin->id)
        ->and($topUp->admin_notes)->toBe('Bank transfer verified');
});

test('approving offline top-up sets wallet_id on request', function () {
    withoutWalletLocaleMiddleware();
    $provider = createWalletProvider();

    Storage::fake('local');

    $this->actingAs($provider, 'provider')
        ->postJson(action([TopUpController::class, 'store']), [
            'amount' => 55,
            'payment_method' => PaymentMethodEnum::Offline->value,
            'user_notes' => 'Offline transfer',
            'transaction_image' => UploadedFile::fake()->image('proof.jpg'),
        ])->assertSuccessful();

    $topUp = TopUpRequest::query()->where('user_id', $provider->id)->first();

    expect($topUp->wallet_id)->toBe($provider->wallet->id);
});
