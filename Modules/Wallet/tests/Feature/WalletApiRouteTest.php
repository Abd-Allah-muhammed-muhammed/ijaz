<?php

use App\Enums\Payment\PaymentMethodEnum;
use App\Models\Payment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Wallet\Http\Controllers\V1\WalletController;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Models\WithdrawRequest;

test('unauthenticated cannot get balance → 401', function () {
    $this->getJson(action([WalletController::class, 'balance']))
        ->assertUnauthorized();
});

test('authenticated user can get balance → 200', function () {
    $user = createWalletUser();
    Sanctum::actingAs($user);

    $this->getJson(action([WalletController::class, 'balance']))
        ->assertSuccessful();
});

test('balance response has correct fields: balance, pending_credit, pending_debit, available', function () {
    $user = createWalletUser();
    fundWallet($user, 250);
    $user->wallet->update(['pending_credit' => 20, 'pending_debit' => 50]);
    Sanctum::actingAs($user);

    $this->getJson(action([WalletController::class, 'balance']))
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => ['balance', 'pending_credit', 'pending_debit', 'available'],
        ])
        ->assertJsonPath('data.pending_credit', 20)
        ->assertJsonPath('data.pending_debit', 50)
        ->assertJsonPath('data.available', 200);
});

test('unauthenticated cannot add balance → 401', function () {
    $this->postJson(action([WalletController::class, 'addBalance']), [
        'amount' => 100,
        'payment_method' => PaymentMethodEnum::Online->value,
    ])->assertUnauthorized();
});

test('online top-up creates TopUpRequest and Payment → 200', function () {
    $user = createWalletUser();
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'addBalance']), [
        'amount' => 150,
        'payment_method' => PaymentMethodEnum::Online->value,
    ])->assertSuccessful();

    expect(TopUpRequest::query()->where('user_id', $user->id)->exists())->toBeTrue()
        ->and(Payment::query()->where('amount', 150)->exists())->toBeTrue();
});

test('online top-up returns payment URL', function () {
    $user = createWalletUser();
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'addBalance']), [
        'amount' => 100,
        'payment_method' => PaymentMethodEnum::Online->value,
    ])->assertSuccessful()
        ->assertJsonStructure(['data' => ['url', 'transaction_id', 'driver']]);
});

test('offline top-up creates TopUpRequest without Payment → 200', function () {
    Storage::fake('public');
    $user = createWalletUser();
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'addBalance']), [
        'amount' => 80,
        'payment_method' => PaymentMethodEnum::Offline->value,
        'transaction_image' => UploadedFile::fake()->image('receipt.jpg'),
    ])->assertSuccessful()
        ->assertJsonPath('data.driver', 'offline');

    expect(TopUpRequest::query()->where('user_id', $user->id)->count())->toBe(1)
        ->and(Payment::query()->count())->toBe(0);
});

test('offline top-up requires transaction_image', function () {
    $user = createWalletUser();
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'addBalance']), [
        'amount' => 80,
        'payment_method' => PaymentMethodEnum::Offline->value,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['transaction_image']);
});

test('missing amount → 422', function () {
    $user = createWalletUser();
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'addBalance']), [
        'payment_method' => PaymentMethodEnum::Online->value,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

test('invalid payment_method → 422', function () {
    $user = createWalletUser();
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'addBalance']), [
        'amount' => 100,
        'payment_method' => 'invalid-method',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['payment_method']);
});

test('unauthenticated cannot withdraw → 401', function () {
    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 50,
    ])->assertUnauthorized();
});

test('withdraw with sufficient balance creates WithdrawRequest → 200', function () {
    $user = createWalletUser();
    fundWallet($user, 200);
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 50,
    ])->assertSuccessful()
        ->assertJsonPath('data.status', 'pending');

    expect(WithdrawRequest::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('withdraw with insufficient balance → 422', function () {
    $user = createWalletUser();
    Sanctum::actingAs($user);

    $response = $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 50,
    ]);

    expect($response->status())->toBeGreaterThanOrEqual(400)
        ->and(WithdrawRequest::query()->count())->toBe(0);
});

test('withdraw creates pending_debit hold on wallet', function () {
    $user = createWalletUser();
    fundWallet($user, 200);
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 60,
    ])->assertSuccessful();

    expect((float) $user->wallet->fresh()->pending_debit)->toBe(60.0);
});

test('missing amount on withdraw → 422', function () {
    $user = createWalletUser();
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'withdraw']), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

test('unauthenticated cannot list transactions → 401', function () {
    $this->getJson(action([WalletController::class, 'transactions']))
        ->assertUnauthorized();
});

test('authenticated user can list transactions → 200', function () {
    $user = createWalletUser();
    fundWallet($user, 100);
    Sanctum::actingAs($user);

    $this->getJson(action([WalletController::class, 'transactions']))
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['items', 'per_page', 'current_page']]);
});

test('transactions are paginated', function () {
    $user = createWalletUser();

    for ($i = 0; $i < 3; $i++) {
        fundWallet($user, 10);
    }

    Sanctum::actingAs($user);

    $this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 2]))
        ->assertSuccessful()
        ->assertJsonPath('data.per_page', 2)
        ->assertJsonCount(2, 'data.items');
});
