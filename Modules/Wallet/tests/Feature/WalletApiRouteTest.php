<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Payment\Enums\PaymentDriverEnum;
use Modules\Payment\Enums\PaymentMethodEnum;
use Modules\Payment\Models\Payment;
use Modules\Wallet\Actions\FinalizeWithdrawAction;
use Modules\Wallet\Http\Controllers\Api\V1\WalletController;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WalletService;

function completeWalletWithdrawViaApi(User $user, float $amount, bool $approved): WithdrawRequest
{
    test()->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => $amount,
    ])->assertSuccessful();

    $withdraw = WithdrawRequest::query()
        ->where('user_id', $user->id)
        ->latest()
        ->firstOrFail();

    DB::transaction(fn () => app(FinalizeWithdrawAction::class)->handle($user, $withdraw, $approved));

    return $withdraw;
}

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

test('wallet balance API response has correct fields, values, and key order', function () {
    $user = createWalletUser();
    fundWallet($user, 250);
    $user->wallet->update(['pending_credit' => 20, 'pending_debit' => 50]);
    Sanctum::actingAs($user);

    $data = $this->getJson(action([WalletController::class, 'balance']))
        ->assertSuccessful()
        ->assertJsonStructure([
            'data' => ['balance', 'pending_credit', 'pending_debit', 'available'],
        ])
        ->assertJsonPath('data.pending_credit', 20)
        ->assertJsonPath('data.pending_debit', 50)
        ->assertJsonPath('data.available', 200)
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

test('wallet transactions API response shape matches grouped lifecycle events', function () {
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
        'operation_id',
        'operation_type',
        'status',
        'amount',
        'balance_before',
        'balance_after',
        'description',
        'created_at',
    ]);
});

test('a completed withdraw appears as ONE item with status completed, showing the real balance_before/after from the final debit', function () {
    $user = createWalletUser();
    fundWallet($user, 400);
    Sanctum::actingAs($user);

    $withdraw = completeWalletWithdrawViaApi($user, 200, approved: true);

    $items = $this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 10]))
        ->assertSuccessful()
        ->json('data.items');

    $withdrawItems = collect($items)->where('operation_id', $withdraw->id)->values();

    expect(WalletTransaction::query()->where('operation_id', $withdraw->id)->count())->toBe(3)
        ->and($withdrawItems)->toHaveCount(1)
        ->and($withdrawItems[0]['status'])->toBe('completed')
        ->and((float) $withdrawItems[0]['amount'])->toBe(200.0)
        ->and((float) $withdrawItems[0]['balance_before'])->toBe(400.0)
        ->and((float) $withdrawItems[0]['balance_after'])->toBe(200.0)
        ->and($withdrawItems[0]['operation_type'])->toBe(WithdrawRequest::class);
});

test('a withdraw request still pending admin approval appears as ONE item with status pending', function () {
    $user = createWalletUser();
    fundWallet($user, 400);
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 200,
    ])->assertSuccessful();

    $withdraw = WithdrawRequest::query()->where('user_id', $user->id)->latest()->firstOrFail();

    $items = $this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 10]))
        ->assertSuccessful()
        ->json('data.items');

    $withdrawItems = collect($items)->where('operation_id', $withdraw->id)->values();

    expect(WalletTransaction::query()->where('operation_id', $withdraw->id)->count())->toBe(1)
        ->and($withdrawItems)->toHaveCount(1)
        ->and($withdrawItems[0]['status'])->toBe('pending')
        ->and((float) $withdrawItems[0]['amount'])->toBe(200.0);
});

test('a rejected withdraw request appears as ONE item with status rejected', function () {
    $user = createWalletUser();
    fundWallet($user, 400);
    Sanctum::actingAs($user);

    $withdraw = completeWalletWithdrawViaApi($user, 200, approved: false);

    $items = $this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 10]))
        ->assertSuccessful()
        ->json('data.items');

    $withdrawItems = collect($items)->where('operation_id', $withdraw->id)->values();

    expect(WalletTransaction::query()->where('operation_id', $withdraw->id)->count())->toBe(2)
        ->and($withdrawItems)->toHaveCount(1)
        ->and($withdrawItems[0]['status'])->toBe('rejected')
        ->and((float) $withdrawItems[0]['amount'])->toBe(200.0)
        ->and((float) $withdrawItems[0]['balance_before'])->toBe(400.0)
        ->and((float) $withdrawItems[0]['balance_after'])->toBe(400.0);
});

test('a top-up credit (single-row operation) still appears as ONE item with status completed, unchanged in substance', function () {
    $user = createWalletUser();
    $topUp = createTopUpFor($user, ['amount' => 150]);

    DB::transaction(fn () => app(WalletService::class)->credit(
        $user,
        150,
        $topUp,
        "Online top-up approved — TopUpRequest#{$topUp->id}",
    ));

    Sanctum::actingAs($user);

    $items = $this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 10]))
        ->assertSuccessful()
        ->json('data.items');

    $topUpItems = collect($items)->where('operation_id', (string) $topUp->id)->values();

    expect(WalletTransaction::query()->where('operation_id', (string) $topUp->id)->count())->toBe(1)
        ->and($topUpItems)->toHaveCount(1)
        ->and($topUpItems[0]['status'])->toBe('completed')
        ->and((float) $topUpItems[0]['amount'])->toBe(150.0)
        ->and($topUpItems[0]['operation_type'])->toBe(TopUpRequest::class)
        ->and((float) $topUpItems[0]['balance_before'])->toBe(0.0)
        ->and((float) $topUpItems[0]['balance_after'])->toBe(150.0);
});

test('pagination counts grouped lifecycle events, not raw ledger rows — e.g. 3 withdraws (9 raw rows total) with per_page=2 returns exactly 2 grouped items on page 1, not a partial/split group', function () {
    $user = createWalletUser();
    $user->wallet->update(['balance' => 5000]);
    Sanctum::actingAs($user);

    $first = completeWalletWithdrawViaApi($user, 200, approved: true);
    $this->travel(1)->second();
    $second = completeWalletWithdrawViaApi($user, 300, approved: true);
    $this->travel(1)->second();
    $third = completeWalletWithdrawViaApi($user, 400, approved: true);

    expect(WalletTransaction::query()->where('user_id', $user->id)->count())->toBe(9);

    $page1 = $this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 2]))
        ->assertSuccessful()
        ->assertJsonPath('data.total', 3)
        ->assertJsonPath('data.per_page', 2)
        ->assertJsonPath('data.last_page', 2)
        ->assertJsonCount(2, 'data.items')
        ->json('data.items');

    expect(collect($page1)->pluck('status')->unique()->values()->all())->toBe(['completed'])
        ->and(collect($page1)->pluck('operation_id')->all())->toBe([$third->id, $second->id])
        ->and(collect($page1)->pluck('amount')->map(fn ($amount) => (float) $amount)->all())->toBe([400.0, 300.0]);

    $page2 = $this->getJson(action([WalletController::class, 'transactions'], [
        'per_page' => 2,
        'page' => 2,
    ]))->assertSuccessful()
        ->assertJsonCount(1, 'data.items')
        ->json('data.items');

    expect($page2[0]['operation_id'])->toBe($first->id)
        ->and($page2[0]['status'])->toBe('completed')
        ->and((float) $page2[0]['amount'])->toBe(200.0);
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

test('offline top-up request creation returns the full localized success message at the envelope root, not empty', function (string $locale, string $expectedMessage) {
    Storage::fake('public');
    $user = createWalletUser();
    Sanctum::actingAs($user);

    $response = $this->postJson(
        action([WalletController::class, 'addBalance']),
        [
            'amount' => 80,
            'payment_method' => PaymentMethodEnum::Offline->value,
            'transaction_image' => UploadedFile::fake()->image('receipt.jpg'),
        ],
        ['Accept-Language' => $locale],
    )->assertSuccessful();

    $envelopeMessage = $response->json('message');
    $nestedMessage = $response->json('data.message');

    expect($envelopeMessage)
        ->toBe($expectedMessage)
        ->not->toBe('')
        ->not->toBeNull()
        ->and($nestedMessage)->toBe($expectedMessage)
        ->and(mb_strlen((string) $envelopeMessage))->toBeGreaterThan(10);
})->with([
    'ar' => ['ar', 'تم إنشاء طلب الإيداع بنجاح وهو بانتظار موافقة الإدارة'],
    'en' => ['en', 'Top up request created successfully, waiting for admin approval'],
]);

test('online top-up request creation (payable) still returns correct root message/behavior', function () {
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
        ->and($data['payable'])->toBeTrue()
        ->and($data['url'])->toBeString()->not->toBeEmpty()
        ->and($data['transaction_id'])->not->toBeNull()
        ->and($data['data'])->toBeArray()
        ->and($data['data'])->toHaveKeys(['id', 'amount', 'status', 'payment_method']);

    // Root envelope message mirrors nested data.message (gateway may leave it null/empty
    // for Testing — still must not leave the envelope as a silent null omission).
    $envelopeMessage = $response->json('message');
    $nestedMessage = $response->json('data.message');

    expect($envelopeMessage)
        ->toBe($nestedMessage ?? '')
        ->toBeString();
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

test('withdrawal request creation returns the full localized success message, not a generic label', function (string $locale, string $expectedMessage) {
    $user = createWalletUser();
    fundWallet($user, 400);
    Sanctum::actingAs($user);

    $response = $this->postJson(
        action([WalletController::class, 'withdraw']),
        ['amount' => 200],
        ['Accept-Language' => $locale],
    )->assertSuccessful();

    $envelopeMessage = $response->json('message');
    $nestedMessage = $response->json('data.message');

    expect($envelopeMessage)
        ->toBe($expectedMessage)
        ->not->toBe(__('withdraw', [], $locale))
        ->not->toBe('سحب')
        ->not->toBe('Withdraw')
        ->and($nestedMessage)->toBe($expectedMessage)
        ->and(mb_strlen($envelopeMessage))->toBeGreaterThan(10);
})->with([
    'ar' => ['ar', 'تم إرسال طلب السحب بنجاح وهو قيد المراجعة'],
    'en' => ['en', 'Withdrawal request submitted successfully and is pending review.'],
]);

test('withdraw with insufficient balance → 422', function () {
    $user = createWalletUser();
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'withdraw']), [
        'amount' => 200,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['amount']);

    expect(WithdrawRequest::query()->count())->toBe(0);
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
    $user->wallet->update(['balance' => 2000]);
    Sanctum::actingAs($user);

    foreach ([200, 200, 200] as $amount) {
        $this->postJson(action([WalletController::class, 'withdraw']), [
            'amount' => $amount,
        ])->assertSuccessful();
    }

    $this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 2]))
        ->assertSuccessful()
        ->assertJsonPath('data.per_page', 2)
        ->assertJsonPath('data.total', 3)
        ->assertJsonCount(2, 'data.items');
});
