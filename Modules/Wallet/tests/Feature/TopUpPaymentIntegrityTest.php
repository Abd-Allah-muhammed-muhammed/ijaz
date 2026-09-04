<?php

use App\Enums\OperationStatusEnum;
use Illuminate\Http\UploadedFile;
use Illuminate\Log\Events\MessageLogged;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Payment\Actions\HandleCallbackAction;
use Modules\Payment\Contracts\Repositories\PaymentRepositoryInterface;
use Modules\Payment\Enums\PaymentMethodEnum;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Models\Payment;
use Modules\Wallet\Actions\TopUp\UpdateTopUpStatusForDashboardAction;
use Modules\Wallet\Exceptions\WalletException;
use Modules\Wallet\Http\Controllers\Api\V1\WalletController;
use Modules\Wallet\Listeners\HandleTopUpPaymentCompleted;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Services\TopUpRequestService;

test('an admin cannot Approve or Reject an online top-up from the dashboard — online top-ups are payment-owned, not admin-decided', function (string $status) {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    $topUp = createTopUpFor($user, [
        'amount' => 100,
        'payment_method' => PaymentMethodEnum::Online->value,
        'status' => OperationStatusEnum::Pending->value,
    ]);

    // Admin HTTP updateStatus is paused — exercise the shared service path instead.
    expect(fn () => app(TopUpRequestService::class)->updateStatusForDashboard(
        $topUp,
        $status,
        null,
        (int) $admin->id,
    ))->toThrow(WalletException::class);

    expect($topUp->fresh()->status)->toBe(OperationStatusEnum::Pending)
        ->and((float) $user->wallet->fresh()->balance)->toBe(0.0);
})->with([
    'approve' => [OperationStatusEnum::Approved->value],
    'reject' => [OperationStatusEnum::Rejected->value],
]);

test('a successful gateway callback credits an online top-up exactly once, regardless of any prior admin interaction attempt', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create(['amount' => 100]);
    $payment = createPaymentFor($user, $topUp, [
        'driver' => 'testing',
        'amount' => 100,
        'status' => PaymentStatusEnum::Pending,
    ]);

    expect(fn () => app(UpdateTopUpStatusForDashboardAction::class)->handle(
        $topUp,
        OperationStatusEnum::Approved->value,
        null,
        $admin->id,
    ))->toThrow(WalletException::class);

    expect($topUp->fresh()->status)->toBe(OperationStatusEnum::Pending);

    $this->post(route('payment.callback', [
        'driver' => 'testing',
        'payment' => $payment->id,
    ]), [
        'status' => 'success',
        'payment_id' => 'topup-integrity-txn-1',
    ])->assertOk();

    expect($topUp->fresh()->status)->toBe(OperationStatusEnum::Approved)
        ->and($topUp->fresh()->payment_status)->toBe(PaymentStatusEnum::Accepted)
        ->and((float) $user->wallet->fresh()->balance)->toBe(100.0);

    $this->post(route('payment.callback', [
        'driver' => 'testing',
        'payment' => $payment->id,
    ]), [
        'status' => 'success',
        'payment_id' => 'topup-integrity-txn-1',
    ])->assertOk();

    expect((float) $user->wallet->fresh()->balance)->toBe(100.0)
        ->and(WalletTransaction::query()->where('operation_id', (string) $topUp->id)->count())->toBe(1);
});

test('a failed/rejected gateway payment never credits the wallet, and the top-up terminal status reflects the rejection, not Pending forever', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create(['amount' => 80]);
    $payment = createPaymentFor($user, $topUp, [
        'driver' => 'testing',
        'amount' => 80,
        'status' => PaymentStatusEnum::Pending,
    ]);

    $this->post(route('payment.callback', [
        'driver' => 'testing',
        'payment' => $payment->id,
    ]), [
        'status' => 'failed',
        'payment_id' => 'topup-fail-txn-1',
    ])->assertOk();

    expect($topUp->fresh()->status)->toBe(OperationStatusEnum::Rejected)
        ->and($topUp->fresh()->payment_status)->toBe(PaymentStatusEnum::Rejected)
        ->and((float) $user->wallet->fresh()->balance)->toBe(0.0);

    // Replayed / late "success" for the same payment must not revive or credit.
    app(HandleTopUpPaymentCompleted::class)->handle(new PaymentCompleted($payment->fresh()));

    expect($topUp->fresh()->status)->toBe(OperationStatusEnum::Rejected)
        ->and((float) $user->wallet->fresh()->balance)->toBe(0.0);
});

test('HandleTopUpPaymentCompleted verifies payment.amount matches the original TopUpRequest.amount before crediting — mismatch is logged and NOT credited', function () {
    $warnings = collect();
    Log::listen(function (MessageLogged $event) use ($warnings) {
        if ($event->level === 'warning') {
            $warnings->push($event);
        }
    });

    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create(['amount' => 100]);
    $payment = createPaymentFor($user, $topUp, [
        'amount' => 150,
        'driver' => 'testing',
        'transaction_id' => 'topup-mismatch-1',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    app(HandleTopUpPaymentCompleted::class)->handle(new PaymentCompleted($payment));

    expect($topUp->fresh()->status)->toBe(OperationStatusEnum::Pending)
        ->and($topUp->fresh()->payment_status)->toBe(PaymentStatusEnum::NeedsReview)
        ->and($payment->fresh()->status)->toBe(PaymentStatusEnum::NeedsReview)
        ->and((float) $user->wallet->fresh()->balance)->toBe(0.0);

    $warning = $warnings->first(fn (MessageLogged $event) => str_contains($event->message, 'Top-up payment amount mismatch'));

    expect($warning)->not->toBeNull()
        ->and($warning->context['payment_id'] ?? null)->toBe($payment->id)
        ->and((float) ($warning->context['paid_amount'] ?? 0))->toBe(150.0)
        ->and((float) ($warning->context['expected_amount'] ?? 0))->toBe(100.0);
});

test('HandleCallbackAction locks the Payment row before its Pending re-check, preventing a duplicate-webhook race', function () {
    $user = createWalletUser();
    $topUp = TopUpRequest::factory()->for($user, 'user')->online()->create(['amount' => 100]);
    $payment = createPaymentFor($user, $topUp, [
        'driver' => 'testing',
        'amount' => 100,
        'status' => PaymentStatusEnum::Pending,
    ]);

    $real = app(PaymentRepositoryInterface::class);
    $locked = false;

    $repo = Mockery::mock(PaymentRepositoryInterface::class);
    $repo->shouldReceive('lockForUpdate')
        ->once()
        ->with(Mockery::on(fn (Payment $p) => $p->id === $payment->id))
        ->andReturnUsing(function (Payment $p) use ($real, &$locked) {
            $locked = true;

            return $real->lockForUpdate($p);
        });
    $repo->shouldReceive('updateFromVerifyResult')
        ->once()
        ->andReturnUsing(function (Payment $p, $result) use ($real, &$locked) {
            expect($locked)->toBeTrue();

            return $real->updateFromVerifyResult($p, $result);
        });
    $repo->shouldReceive('refresh')->andReturnUsing(fn (Payment $p) => $real->refresh($p));
    $repo->shouldReceive('createForOwner')->andReturnUsing(fn (...$args) => $real->createForOwner(...$args));
    $repo->shouldReceive('findById')->andReturnUsing(fn (...$args) => $real->findById(...$args));
    $repo->shouldReceive('sumAcceptedAmount')->andReturnUsing(fn () => $real->sumAcceptedAmount());
    $repo->shouldReceive('acceptedDailyTotalsSince')->andReturnUsing(fn (...$args) => $real->acceptedDailyTotalsSince(...$args));

    app()->instance(PaymentRepositoryInterface::class, $repo);

    app(HandleCallbackAction::class)->handle($payment->fresh(), [
        'status' => 'success',
        'payment_id' => 'lock-txn-1',
    ]);

    expect($locked)->toBeTrue()
        ->and($payment->fresh()->status)->toBe(PaymentStatusEnum::Accepted);
});

test('an offline top-up cannot be approved without a transaction_image still present on the request', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    $topUp = createTopUpFor($user, [
        'amount' => 75,
        'payment_method' => PaymentMethodEnum::Offline->value,
        'status' => OperationStatusEnum::Pending->value,
        'transaction_image' => null,
    ]);

    expect(fn () => app(TopUpRequestService::class)->updateStatusForDashboard(
        $topUp,
        OperationStatusEnum::Approved->value,
        null,
        (int) $admin->id,
    ))->toThrow(WalletException::class);

    expect($topUp->fresh()->status)->toBe(OperationStatusEnum::Pending)
        ->and((float) $user->wallet->fresh()->balance)->toBe(0.0);
});

test('offline top-up API validation requires an actual image file (type + size), matching the provider web form rules', function () {
    $user = createWalletUser();
    Sanctum::actingAs($user);

    $this->postJson(action([WalletController::class, 'addBalance']), [
        'amount' => 80,
        'payment_method' => PaymentMethodEnum::Offline->value,
        'transaction_image' => 'not-a-file',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['transaction_image']);

    $this->post(action([WalletController::class, 'addBalance']), [
        'amount' => 80,
        'payment_method' => PaymentMethodEnum::Offline->value,
        'transaction_image' => UploadedFile::fake()->create('receipt.pdf', 100, 'application/pdf'),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors(['transaction_image']);
});

test('existing offline approve/reject/idempotency tests still pass unchanged — regression', function () {
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

    app(TopUpRequestService::class)->updateStatusForDashboard(
        $topUp,
        OperationStatusEnum::Approved->value,
        null,
        (int) $admin->id,
    );

    expect((float) $user->wallet->fresh()->balance)->toBe(75.0)
        ->and($topUp->fresh()->status)->toBe(OperationStatusEnum::Approved);

    expect(fn () => app(TopUpRequestService::class)->updateStatusForDashboard(
        $topUp->fresh(),
        OperationStatusEnum::Rejected->value,
        null,
        (int) $admin->id,
    ))->toThrow(WalletException::class);

    expect((float) $user->wallet->fresh()->balance)->toBe(75.0);
});
