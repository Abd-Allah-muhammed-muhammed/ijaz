<?php

use App\Enums\OperationStatusEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Payment\Enums\PaymentDriverEnum;
use Modules\Payment\Enums\PaymentMethodEnum;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Models\Payment;
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
    Storage::fake('public');
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
        ->and($topUp->wallet_id)->toBe($provider->wallet->id)
        ->and($topUp->transaction_image)->not->toBeNull()
        ->and(Storage::disk('public')->exists($topUp->transaction_image))->toBeTrue();
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

test('provider top-up details shows the attachment download link when transaction_image exists', function () {
    Storage::fake('public');
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    $path = UploadedFile::fake()->image('receipt.jpg')->store('topup', 'public');
    $topUp = createTopUpFor($provider, [
        'payment_method' => PaymentMethodEnum::Offline->value,
        'transaction_image' => $path,
    ]);

    $expectedUrl = Storage::disk('public')->url($path);

    $this->actingAs($provider, 'provider')
        ->get(action([TopUpController::class, 'show'], ['top_up_request' => $topUp->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/TopUpRequests/Show')
            ->where('row.transaction_image', $expectedUrl)
            ->missing('row.attachment')
        );

    expect($expectedUrl)->toContain('/storage/')
        ->and(Storage::disk('public')->exists($path))->toBeTrue();
});

test('provider top-up details shows a clean no-card-data state for offline payments, not undefined-then-N/A flash', function () {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    $topUp = createTopUpFor($provider, [
        'payment_method' => PaymentMethodEnum::Offline->value,
        'transaction_id' => null,
        'payment_driver' => null,
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action([TopUpController::class, 'show'], ['top_up_request' => $topUp->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/TopUpRequests/Show')
            // Offline has no card data: paymentResponse must be present as null on the
            // initial response (not deferred/undefined), so the UI never flashes a card shell.
            ->where('paymentResponse', null)
            ->etc()
        );
});

test('provider top-up details renders safely when payment response exists but has no card data', function () {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    $topUp = createTopUpFor($provider, [
        'payment_method' => PaymentMethodEnum::Online->value,
        'payment_status' => PaymentStatusEnum::Accepted->value,
        'status' => OperationStatusEnum::Approved->value,
        'transaction_id' => 'test-txn-no-card-info',
        'payment_driver' => PaymentDriverEnum::Testing->value,
    ]);

    Payment::factory()
        ->forProduct($topUp, $provider)
        ->accepted()
        ->create([
            'transaction_id' => 'test-txn-no-card-info',
            'driver' => PaymentDriverEnum::Testing->value,
            // Mirrors the testing gateway / real crash case: status only, no payment_info.
            'response' => ['status' => 'success'],
        ]);

    $this->actingAs($provider, 'provider')
        ->get(action([TopUpController::class, 'show'], ['top_up_request' => $topUp->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/TopUpRequests/Show')
            ->missing('paymentResponse')
            ->loadDeferredProps(fn ($reload) => $reload
                // Partial payment payload must resolve to null (no card), not a crashy object.
                ->where('paymentResponse', null)
            )
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
    Storage::fake('public');
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    $path = UploadedFile::fake()->image('receipt.jpg')->store('topup', 'public');
    $topUp = createTopUpFor($user, [
        'amount' => 75,
        'payment_method' => PaymentMethodEnum::Offline->value,
        'status' => OperationStatusEnum::Pending->value,
        'transaction_image' => $path,
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

test('admin approving an offline top-up request updates both status and payment_status', function () {
    Storage::fake('public');
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    $path = UploadedFile::fake()->image('receipt.jpg')->store('topup', 'public');
    $topUp = createTopUpFor($user, [
        'amount' => 75,
        'payment_method' => PaymentMethodEnum::Offline->value,
        'status' => OperationStatusEnum::Pending->value,
        'payment_status' => PaymentStatusEnum::Pending->value,
        'transaction_image' => $path,
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardTopUpRequestController::class, 'index']))
        ->put(action([DashboardTopUpRequestController::class, 'updateStatus'], ['topUpRequest' => $topUp->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect(route('dashboard.top-up-requests.index'))
        ->assertSessionHas('success');

    $topUp->refresh();

    expect($topUp->status)->toBe(OperationStatusEnum::Approved)
        ->and($topUp->payment_status)->toBe(PaymentStatusEnum::Accepted);
});

test('admin rejecting an offline top-up request updates both status and payment_status', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    $topUp = createTopUpFor($user, [
        'amount' => 100,
        'payment_method' => PaymentMethodEnum::Offline->value,
        'status' => OperationStatusEnum::Pending->value,
        'payment_status' => PaymentStatusEnum::Pending->value,
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardTopUpRequestController::class, 'index']))
        ->put(action([DashboardTopUpRequestController::class, 'updateStatus'], ['topUpRequest' => $topUp->id]), [
            'status' => OperationStatusEnum::Rejected->value,
        ])->assertRedirect(route('dashboard.top-up-requests.index'))
        ->assertSessionHas('success');

    $topUp->refresh();

    expect($topUp->status)->toBe(OperationStatusEnum::Rejected)
        ->and($topUp->payment_status)->toBe(PaymentStatusEnum::Rejected);
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
        ->assertSessionHas('error', __('wallet.cannot_update_top_up_request_status'));
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

test('admin approving an online top-up request is rejected — online top-ups are payment-owned', function () {
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
        ])->assertRedirect()
        ->assertSessionHas('error', __('wallet.online_top_up_payment_owned'));

    expect($topUp->fresh()->status)->toBe(OperationStatusEnum::Pending)
        ->and((float) $user->wallet->fresh()->balance)->toBe($balanceBefore)
        ->and((float) $user->wallet->fresh()->balance)->toBe(40.0);
});

test('approving a top-up request persists admin_id and admin_notes', function () {
    Storage::fake('public');
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    $path = UploadedFile::fake()->image('receipt.jpg')->store('topup', 'public');
    $topUp = createTopUpFor($user, [
        'amount' => 50,
        'payment_method' => PaymentMethodEnum::Offline->value,
        'status' => OperationStatusEnum::Pending->value,
        'transaction_image' => $path,
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

    Storage::fake('public');

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
