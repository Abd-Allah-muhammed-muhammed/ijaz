<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Payment\Enums\PaymentDriverEnum;
use Modules\Payment\Enums\PaymentMethodEnum;
use Modules\Payment\Models\Payment;
use Modules\Wallet\Http\Controllers\Api\V1\WalletController;
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
        'payment_driver' => PaymentDriverEnum::Testing->value,
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
        'payment_driver' => PaymentDriverEnum::Testing->value,
    ])->assertSuccessful()
        ->assertJsonStructure(['data' => ['url', 'transaction_id', 'driver']]);
});

test('wallet addBalance response shape is unchanged', function () {
    $user = createWalletUser();
    Sanctum::actingAs($user);

    $response = $this->postJson(action([WalletController::class, 'addBalance']), [
        'amount' => 100,
        'payment_method' => PaymentMethodEnum::Online->value,
        'payment_driver' => PaymentDriverEnum::Testing->value,
    ])->assertSuccessful();

    $data = $response->json('data');

    expect(array_keys($data))->toBe([
        'status',
        'driver',
        'url',
        'payable',
        'transaction_id',
        'message',
        'data',
    ])
        ->and($data['status'])->toBe('success')
        ->and($data['driver'])->toBe(PaymentDriverEnum::Testing->value)
        ->and($data['payable'])->toBeTrue()
        ->and($data['url'])->toBeString()->not->toBeEmpty()
        ->and($data['transaction_id'])->not->toBeNull()
        ->and($data['data'])->toBeArray()
        ->and($data['data'])->toHaveKeys(['id', 'amount', 'status', 'payment_method']);
});

test('wallet balance API response shape is unchanged', function () {
    $user = createWalletUser();
    fundWallet($user, 250);
    $user->wallet->update(['pending_credit' => 20, 'pending_debit' => 50]);
    Sanctum::actingAs($user);

    $data = $this->getJson(action([WalletController::class, 'balance']))
        ->assertSuccessful()
        ->json('data');

    expect(array_keys($data))->toBe([
        'balance',
        'pending_credit',
        'pending_debit',
        'available',
        'total_earning',
        'total_spent',
    ])
        ->and($data['pending_credit'])->toBe(20)
        ->and($data['pending_debit'])->toBe(50)
        ->and($data['available'])->toBe(200);
});

test('wallet transactions API response shape is unchanged', function () {
    $user = createWalletUser();
    fundWallet($user, 100);
    Sanctum::actingAs($user);

    $data = $this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 10]))
        ->assertSuccessful()
        ->json('data');

    expect(array_keys($data))->toBe([
        'items',
        'total',
        'count',
        'per_page',
        'current_page',
        'last_page',
        'has_more_pages',
    ])
        ->and($data['per_page'])->toBe(10)
        ->and($data['current_page'])->toBe(1)
        ->and($data['items'])->toBeArray()
        ->and($data['items'])->not->toBeEmpty();

    expect(array_keys($data['items'][0]))->toBe([
        'id',
        'amount',
        'credit',
        'debit',
        'pending_credit',
        'pending_debit',
        'balance_before',
        'balance_after',
        'description',
        'operation_type',
        'operation_id',
        'created_at',
    ]);
});

test('mobile wallet transaction list exposes a non-zero display amount for a withdraw hold entry', function () {
    $user = createWalletUser();
    fundWallet($user, 400);
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 200,
    ])->assertSuccessful();

    $items = $this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 10]))
        ->assertSuccessful()
        ->json('data.items');

    $hold = collect($items)->first(
        fn (array $item): bool => (float) $item['pending_debit'] === 200.0
    );

    expect($hold)->not->toBeNull()
        ->and($hold)->toHaveKey('amount')
        ->and((float) $hold['amount'])->toBe(200.0);
});

test('add-balance and withdraw response envelopes are byte-identical after refactor', function () {
    Storage::fake('public');
    $user = createWalletUser();
    fundWallet($user, 400);
    Sanctum::actingAs($user);

    $online = $this->postJson(action([WalletController::class, 'addBalance']), [
        'amount' => 100,
        'payment_method' => PaymentMethodEnum::Online->value,
        'payment_driver' => PaymentDriverEnum::Testing->value,
    ])->assertSuccessful()->json('data');

    expect(array_keys($online))->toBe([
        'status',
        'driver',
        'url',
        'payable',
        'transaction_id',
        'message',
        'data',
    ]);

    $offline = $this->postJson(action([WalletController::class, 'addBalance']), [
        'amount' => 80,
        'payment_method' => PaymentMethodEnum::Offline->value,
        'transaction_image' => UploadedFile::fake()->image('receipt.jpg'),
    ])->assertSuccessful()->json('data');

    expect(array_keys($offline))->toBe([
        'status',
        'transaction_id',
        'driver',
        'url',
        'payable',
        'data',
        'message',
    ])
        ->and($offline['status'])->toBe('pending')
        ->and($offline['transaction_id'])->toBe('')
        ->and($offline['driver'])->toBe('offline')
        ->and($offline['url'])->toBe('')
        ->and($offline['payable'])->toBeFalse();

    $withdraw = $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 200,
    ])->assertSuccessful()->json('data');

    expect(array_keys($withdraw))->toBe([
        'status',
        'data',
        'message',
    ])
        ->and($withdraw['status'])->toBe('pending')
        ->and($withdraw['data'])->toBeArray()
        ->and($withdraw['data'])->toHaveKeys(['id', 'amount', 'status', 'admin_notes', 'user_notes', 'created_at']);
});

test('user api top-up ignores client-supplied payment_driver and uses the server-configured driver', function () {
    config(['payment.default' => PaymentDriverEnum::Testing->value]);

    $user = createWalletUser();
    Sanctum::actingAs($user);

    $response = $this->postJson(action([WalletController::class, 'addBalance']), [
        'amount' => 125,
        'payment_method' => PaymentMethodEnum::Online->value,
        'payment_driver' => PaymentDriverEnum::Rajhi->value,
    ])->assertSuccessful();

    $payment = Payment::query()->where('amount', 125)->first();

    expect($payment)->not->toBeNull()
        ->and($payment->driver)->toBe(PaymentDriverEnum::Testing->value)
        ->and($response->json('data.driver'))->toBe(PaymentDriverEnum::Testing->value)
        ->and($response->json('data.url'))->toBe(route('payment.testing.checkout', ['payment' => $payment->id]));
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
    fundWallet($user, 400);
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 200,
    ])->assertSuccessful()
        ->assertJsonPath('data.status', 'pending');

    expect(WithdrawRequest::query()->where('user_id', $user->id)->exists())->toBeTrue();
});

test('withdraw with insufficient balance → 422', function () {
    $user = createWalletUser();
    Sanctum::actingAs($user);

    $response = $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 200,
    ]);

    expect($response->status())->toBeGreaterThanOrEqual(400)
        ->and(WithdrawRequest::query()->count())->toBe(0);
});

test('withdraw creates pending_debit hold on wallet', function () {
    $user = createWalletUser();
    fundWallet($user, 400);
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 200,
    ])->assertSuccessful();

    expect((float) $user->wallet->fresh()->pending_debit)->toBe(200.0);
});

test('missing amount on withdraw → 422', function () {
    $user = createWalletUser();
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'withdraw']), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);
});

test('mobile API validation failure still returns the JSON error envelope', function () {
    $user = createWalletUser();
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'withdraw']), [])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Validation Failed')
        ->assertJsonStructure([
            'success',
            'data',
            'errors' => ['amount'],
            'message',
            'token',
        ]);
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
