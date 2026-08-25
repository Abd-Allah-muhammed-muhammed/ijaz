<?php

use App\Enums\OperationStatusEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Modules\Payment\Enums\PaymentDriverEnum;
use Modules\Payment\Enums\PaymentMethodEnum;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Models\Payment;
use Modules\Wallet\Actions\Withdraw\CancelWithdrawRequestAction;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Http\Controllers\Api\V1\WalletController;
use Modules\Wallet\Http\Controllers\Dashboard\TopUpRequestController as DashboardTopUpRequestController;
use Modules\Wallet\Http\Controllers\Dashboard\WithdrawRequestController as DashboardWithdrawRequestController;
use Modules\Wallet\Listeners\HandleTopUpPaymentCompleted;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WithdrawRequestService;

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
        'transfer_status',
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

    for ($i = 0; $i < 3; $i++) {
        fundWallet($user, 10);
    }

    Sanctum::actingAs($user);

    $this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 2]))
        ->assertSuccessful()
        ->assertJsonPath('data.per_page', 2)
        ->assertJsonCount(2, 'data.items');
});

test('WalletTransactionEntryKindEnum has the expected 6 cases with correct string values', function () {
    expect(WalletTransactionEntryKindEnum::cases())->toHaveCount(6)
        ->and(WalletTransactionEntryKindEnum::WithdrawRequested->value)->toBe('withdraw_requested')
        ->and(WalletTransactionEntryKindEnum::WithdrawHoldReleased->value)->toBe('withdraw_hold_released')
        ->and(WalletTransactionEntryKindEnum::WithdrawApproved->value)->toBe('withdraw_approved')
        ->and(WalletTransactionEntryKindEnum::WithdrawRejected->value)->toBe('withdraw_rejected')
        ->and(WalletTransactionEntryKindEnum::WithdrawCancelled->value)->toBe('withdraw_cancelled')
        ->and(WalletTransactionEntryKindEnum::TopupCredited->value)->toBe('topup_credited');
});

test('creating a withdraw request writes entry_kind=WalletTransactionEntryKindEnum::WithdrawRequested', function () {
    $user = createWalletUser();
    fundWallet($user, 400);

    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 200, userNotes: null),
    );

    $row = WalletTransaction::query()->where('operation_id', $withdraw->id)->sole();

    expect($row->entry_kind)->toBe(WalletTransactionEntryKindEnum::WithdrawRequested);
});

test('approving a withdraw writes HoldReleased then Approved, in that order', function () {
    withoutWalletLocaleMiddleware();
    $user = createWalletUser();
    fundWallet($user, 400);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 200, userNotes: null),
    );
    $admin = createWalletAdmin();

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardWithdrawRequestController::class, 'index']))
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdraw->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect();

    $kinds = WalletTransaction::query()
        ->where('operation_id', $withdraw->id)
        ->orderBy('created_at')
        ->orderBy('id')
        ->get()
        ->map(fn (WalletTransaction $row) => $row->entry_kind)
        ->all();

    expect($kinds)->toBe([
        WalletTransactionEntryKindEnum::WithdrawRequested,
        WalletTransactionEntryKindEnum::WithdrawHoldReleased,
        WalletTransactionEntryKindEnum::WithdrawApproved,
    ]);
});

test('rejecting a withdraw writes HoldReleased then Rejected', function () {
    withoutWalletLocaleMiddleware();
    $user = createWalletUser();
    fundWallet($user, 400);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 200, userNotes: null),
    );
    $admin = createWalletAdmin();

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardWithdrawRequestController::class, 'index']))
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdraw->id]), [
            'status' => OperationStatusEnum::Rejected->value,
        ])->assertRedirect();

    $kinds = WalletTransaction::query()
        ->where('operation_id', $withdraw->id)
        ->orderBy('created_at')
        ->orderBy('id')
        ->get()
        ->map(fn (WalletTransaction $row) => $row->entry_kind)
        ->all();

    expect($kinds)->toBe([
        WalletTransactionEntryKindEnum::WithdrawRequested,
        WalletTransactionEntryKindEnum::WithdrawHoldReleased,
        WalletTransactionEntryKindEnum::WithdrawRejected,
    ]);
});

test('cancelling a withdraw writes Cancelled', function () {
    $user = createWalletUser();
    fundWallet($user, 400);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 200, userNotes: null),
    );

    DB::transaction(fn () => app(CancelWithdrawRequestAction::class)->handle($user, $withdraw));

    $kinds = WalletTransaction::query()
        ->where('operation_id', $withdraw->id)
        ->orderBy('created_at')
        ->orderBy('id')
        ->get()
        ->map(fn (WalletTransaction $row) => $row->entry_kind)
        ->all();

    expect($kinds)->toBe([
        WalletTransactionEntryKindEnum::WithdrawRequested,
        WalletTransactionEntryKindEnum::WithdrawCancelled,
    ]);
});

test('an online top-up credit writes TopupCredited', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create(['amount' => 150]);
    $payment = createPaymentFor($user, $topUp, [
        'amount' => 150,
        'driver' => 'testing',
        'transaction_id' => 'topup-entry-kind',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    DB::transaction(fn () => app(HandleTopUpPaymentCompleted::class)->handle(new PaymentCompleted($payment)));

    $row = WalletTransaction::query()->where('operation_id', (string) $topUp->id)->sole();

    expect($row->entry_kind)->toBe(WalletTransactionEntryKindEnum::TopupCredited);
});

test('an offline top-up approval writes TopupCredited', function () {
    Storage::fake('public');
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    $path = UploadedFile::fake()->image('receipt.jpg')->store('topup', 'public');
    $topUp = createTopUpFor($user, [
        'amount' => 80,
        'payment_method' => PaymentMethodEnum::Offline->value,
        'status' => OperationStatusEnum::Pending->value,
        'transaction_image' => $path,
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardTopUpRequestController::class, 'index']))
        ->put(action([DashboardTopUpRequestController::class, 'updateStatus'], ['topUpRequest' => $topUp->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect();

    $row = WalletTransaction::query()->where('operation_id', (string) $topUp->id)->sole();

    expect($row->entry_kind)->toBe(WalletTransactionEntryKindEnum::TopupCredited);
});

test('GET /api/v1/wallet/transaction excludes HoldReleased rows but includes all other entry_kind rows, using the EXACT same response shape as before grouping (credit/debit/pending_*/balance_before/balance_after/description/operation_type/operation_id/created_at/id/amount)', function () {
    withoutWalletLocaleMiddleware();
    $user = createWalletUser();
    fundWallet($user, 1000);
    $pending = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 100, userNotes: null),
    );
    $approved = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 200, userNotes: null),
    );
    $admin = createWalletAdmin();
    $this->actingAs($admin, 'admin')
        ->from(action([DashboardWithdrawRequestController::class, 'index']))
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $approved->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect();

    Sanctum::actingAs($user);

    $items = $this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 20]))
        ->assertSuccessful()
        ->json('data.items');

    expect(array_keys($items[0]))->toBe([
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
        'transfer_status',
    ]);

    $itemIds = collect($items)->pluck('id');
    $ledger = WalletTransaction::query()
        ->where('user_id', $user->id)
        ->get()
        ->keyBy('id');

    $holdReleasedIds = $ledger->filter(
        fn (WalletTransaction $row): bool => $row->entry_kind === WalletTransactionEntryKindEnum::WithdrawHoldReleased,
    )->keys();
    $approvedRequestedId = $ledger->first(
        fn (WalletTransaction $row): bool => $row->operation_id === $approved->id
            && $row->entry_kind === WalletTransactionEntryKindEnum::WithdrawRequested,
    )?->id;
    $approvedId = $ledger->first(
        fn (WalletTransaction $row): bool => $row->entry_kind === WalletTransactionEntryKindEnum::WithdrawApproved,
    )?->id;
    $pendingRequestedId = $ledger->first(
        fn (WalletTransaction $row): bool => $row->operation_id === $pending->id
            && $row->entry_kind === WalletTransactionEntryKindEnum::WithdrawRequested,
    )?->id;
    $fundingId = $ledger->first(
        fn (WalletTransaction $row): bool => $row->entry_kind === null && (float) $row->credit > 0,
    )?->id;

    expect($holdReleasedIds)->not->toBeEmpty()
        ->and($itemIds->intersect($holdReleasedIds)->all())->toBe([])
        ->and($itemIds)->not->toContain($approvedRequestedId)
        ->and($itemIds)->toContain($approvedId)
        ->and($itemIds)->toContain($pendingRequestedId)
        ->and($itemIds)->toContain($fundingId);
});

test('a withdraw_requested row becomes invisible once its sibling approved or rejected row exists for the same operation_id', function () {
    withoutWalletLocaleMiddleware();
    $user = createWalletUser();
    fundWallet($user, 400);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 200, userNotes: null),
    );

    Sanctum::actingAs($user);
    $before = collect($this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 20]))
        ->assertSuccessful()
        ->json('data.items'))
        ->pluck('id');

    $requestedId = WalletTransaction::query()
        ->where('operation_id', $withdraw->id)
        ->where('entry_kind', WalletTransactionEntryKindEnum::WithdrawRequested)
        ->value('id');

    expect($before)->toContain($requestedId);

    $admin = createWalletAdmin();
    $this->actingAs($admin, 'admin')
        ->from(action([DashboardWithdrawRequestController::class, 'index']))
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdraw->id]), [
            'status' => OperationStatusEnum::Rejected->value,
        ])->assertRedirect();

    Sanctum::actingAs($user);
    $after = collect($this->getJson(action([WalletController::class, 'transactions'], ['per_page' => 20]))
        ->assertSuccessful()
        ->json('data.items'))
        ->pluck('id');

    expect($after)->not->toContain($requestedId);
});

test('a withdraw_requested transaction returns a translated description in the request locale, not a raw class name', function () {
    $user = createWalletUser();
    fundWallet($user, 400);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 200, userNotes: null),
    );
    Sanctum::actingAs($user);

    $ref = strtoupper(substr((string) $withdraw->id, -8));
    $expected = __('wallet.entry_kind.withdraw_requested', ['ref' => $ref], 'en');

    $item = collect($this->getJson(
        action([WalletController::class, 'transactions'], ['per_page' => 20]),
        ['Accept-Language' => 'en'],
    )->assertSuccessful()->json('data.items'))
        ->firstWhere('operation_id', $withdraw->id);

    $stored = WalletTransaction::query()
        ->where('operation_id', $withdraw->id)
        ->where('entry_kind', WalletTransactionEntryKindEnum::WithdrawRequested)
        ->sole();

    expect($item)->not->toBeNull()
        ->and($item['description'])->toBe($expected)
        ->and($item['description'])->toContain($ref)
        ->and($item['description'])->not->toContain('Modules\\Wallet')
        ->and($item['description'])->not->toContain(WithdrawRequest::class)
        ->and($item['description'])->not->toContain('Withdraw Request Created')
        ->and($stored->getRawOriginal('description'))->toBe('wallet.entry_kind.withdraw_requested')
        ->and($stored->description)->toBe($expected);
});

test('the same transaction returns a different description string when requested with a different Accept-Language / locale', function () {
    $user = createWalletUser();
    fundWallet($user, 400);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 200, userNotes: null),
    );
    Sanctum::actingAs($user);

    $ref = strtoupper(substr((string) $withdraw->id, -8));

    $english = collect($this->getJson(
        action([WalletController::class, 'transactions'], ['per_page' => 20]),
        ['Accept-Language' => 'en'],
    )->assertSuccessful()->json('data.items'))
        ->firstWhere('operation_id', $withdraw->id)['description'];

    $arabic = collect($this->getJson(
        action([WalletController::class, 'transactions'], ['per_page' => 20]),
        ['Accept-Language' => 'ar'],
    )->assertSuccessful()->json('data.items'))
        ->firstWhere('operation_id', $withdraw->id)['description'];

    expect($english)->toBe(__('wallet.entry_kind.withdraw_requested', ['ref' => $ref], 'en'))
        ->and($arabic)->toBe(__('wallet.entry_kind.withdraw_requested', ['ref' => $ref], 'ar'))
        ->and($english)->not->toBe($arabic)
        ->and($english)->toContain($ref)
        ->and($arabic)->toContain($ref);
});

test('an entry_kind=null transaction (e.g. an Order-related row) still uses its original stored description, unaffected by this change', function () {
    $user = createWalletUser();
    fundWallet($user, 100);

    $orderRow = WalletTransaction::query()->create([
        'wallet_id' => $user->wallet->id,
        'user_id' => $user->id,
        'user_type' => $user::class,
        'credit' => 25,
        'debit' => 0,
        'pending_credit' => 0,
        'pending_debit' => 0,
        'balance_before' => 100,
        'balance_after' => 125,
        'description' => 'Order settlement for completed job',
        'operation_type' => 'Modules\\Orders\\Models\\Order',
        'operation_id' => (string) Str::uuid(),
        'entry_kind' => null,
    ]);

    Sanctum::actingAs($user);

    $items = collect($this->getJson(
        action([WalletController::class, 'transactions'], ['per_page' => 20]),
        ['Accept-Language' => 'ar'],
    )->assertSuccessful()->json('data.items'));

    $orderItem = $items->firstWhere('id', $orderRow->id);
    $fundingItem = $items->firstWhere('description', 'Test wallet funding');

    expect($orderItem)->not->toBeNull()
        ->and($orderItem['description'])->toBe('Order settlement for completed job')
        ->and($fundingItem)->not->toBeNull()
        ->and($fundingItem['description'])->toBe('Test wallet funding');
});

test('mobile wallet transaction list returns a short, translated operation_type label (e.g. "Withdraw Request"), not the raw PHP class name (Modules\\Wallet\\Models\\WithdrawRequest)', function () {
    $user = createWalletUser();
    fundWallet($user, 400);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 200, userNotes: null),
    );
    Sanctum::actingAs($user);

    $item = collect($this->getJson(
        action([WalletController::class, 'transactions'], ['per_page' => 20]),
        ['Accept-Language' => 'en'],
    )->assertSuccessful()->json('data.items'))
        ->firstWhere('operation_id', $withdraw->id);

    expect($item)->not->toBeNull()
        ->and($item['operation_type'])->toBe(__('WithdrawRequest', [], 'en'))
        ->and($item['operation_type'])->toBe('Withdraw Request')
        ->and($item['operation_type'])->not->toContain('Modules\\Wallet')
        ->and($item['operation_type'])->not->toBe(WithdrawRequest::class);
});
