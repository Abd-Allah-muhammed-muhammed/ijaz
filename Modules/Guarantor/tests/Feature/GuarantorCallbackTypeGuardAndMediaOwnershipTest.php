<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Actions\Guarantor\DeleteGuarantorMediaAction;
use Modules\Guarantor\Actions\Installment\PayInstallmentAction;
use Modules\Guarantor\Actions\Payment\AddCounterpartyWalletTransaction;
use Modules\Guarantor\Actions\Payment\AddRequesterWalletTransaction;
use Modules\Guarantor\Actions\Payment\ProcessGuarantorPayment;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Listeners\HandleGuarantorPaymentCompleted;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Models\Payment;

beforeEach(function (): void {
    config(['app.payment.driver' => 'testing']);
});

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest}
 */
function callbackTypeGuardContext(array $requestAttributes = []): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create();
    $request = GuarantorRequest::factory()->accepted()->create(array_merge([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'counterparty_id' => $counterparty->id,
        'counterparty_type' => User::class,
        'amount' => 1000,
        'fees' => 10,
    ], $requestAttributes));

    return compact('requester', 'counterparty', 'request');
}

function callbackTypeGuardPayment(GuarantorRequest|GuarantorInstallment $product, User $payer, float $amount): Payment
{
    $payment = Payment::query()->create([
        'user_id' => $payer->getKey(),
        'user_type' => User::class,
        'product_id' => $product->getKey(),
        'product_type' => $product::class,
        'amount' => $amount,
        'status' => PaymentStatusEnum::Accepted,
        'driver' => 'testing',
    ]);

    return $payment->load('product');
}

function runCallbackTypeGuardPaymentCompleted(Payment $payment): void
{
    DB::transaction(fn () => app(HandleGuarantorPaymentCompleted::class)->handle(new PaymentCompleted($payment)));
}

test('ProcessGuarantorPayment::processIndividualPayment rejects (NeedsReview, not silent success) if the guarantor is Company type, even if a payment record with product_type=GuarantorRequest exists', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = callbackTypeGuardContext([
        'type' => GuarantorTypeEnum::Company,
    ]);
    GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);
    GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 2,
        'amount' => 500,
    ]);

    $payment = callbackTypeGuardPayment($request, $counterparty, 1010.0);

    expect(app(ProcessGuarantorPayment::class)->handle($payment))->toBeFalse();

    runCallbackTypeGuardPaymentCompleted($payment->fresh()->load('product'));

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Accepted)
        ->and($payment->fresh()->status)->toBe(PaymentStatusEnum::NeedsReview)
        ->and($request->installments()->where('status', InstallmentStatusEnum::Pending)->count())->toBe(2)
        ->and((float) ($counterparty->wallet->fresh()->pending_debit ?? 0))->toBe(0.0)
        ->and((float) ($requester->wallet->fresh()->pending_credit ?? 0))->toBe(0.0);
});

test('ProcessGuarantorPayment::processInstallmentPayment rejects when guarantor is Individual type — defense in depth', function () {
    ['counterparty' => $counterparty, 'request' => $request] = callbackTypeGuardContext([
        'type' => GuarantorTypeEnum::Individual,
    ]);
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $payment = callbackTypeGuardPayment($installment, $counterparty, 500.0);

    expect(app(ProcessGuarantorPayment::class)->handle($payment))->toBeFalse()
        ->and($payment->fresh()->status)->toBe(PaymentStatusEnum::NeedsReview)
        ->and($installment->fresh()->status)->toBe(InstallmentStatusEnum::Pending)
        ->and($request->fresh()->status)->toBe(GuarantorStatusEnum::Accepted);
});

test('AddRequesterWalletTransaction and AddCounterpartyWalletTransaction reject creating a parent-level (GuarantorRequest) wallet hold on a Company-type guarantor', function () {
    ['counterparty' => $counterparty, 'request' => $request] = callbackTypeGuardContext([
        'type' => GuarantorTypeEnum::Company,
    ]);
    $payment = callbackTypeGuardPayment($request, $counterparty, 1010.0);
    $passthrough = static fn (Payment $processed): Payment => $processed;

    expect(fn () => app(AddRequesterWalletTransaction::class)($payment, $passthrough))
        ->toThrow(GuarantorException::class);

    expect(fn () => app(AddCounterpartyWalletTransaction::class)($payment, $passthrough))
        ->toThrow(GuarantorException::class);

    expect((float) ($counterparty->wallet->fresh()->pending_debit ?? 0))->toBe(0.0)
        ->and((float) ($request->requester->wallet->fresh()->pending_credit ?? 0))->toBe(0.0);
});

test('the same wallet transaction Actions still work correctly for Individual guarantors — regression', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = callbackTypeGuardContext([
        'type' => GuarantorTypeEnum::Individual,
    ]);
    $payment = callbackTypeGuardPayment($request, $counterparty, 1010.0);
    $passthrough = static fn (Payment $processed): Payment => $processed;

    app(AddRequesterWalletTransaction::class)($payment, $passthrough);
    app(AddCounterpartyWalletTransaction::class)($payment, $passthrough);

    expect((float) $counterparty->wallet->fresh()->pending_debit)->toBe(1010.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(1010.0);
});

test('the same wallet transaction Actions still work correctly for Company installment payments — regression', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = callbackTypeGuardContext([
        'type' => GuarantorTypeEnum::Company,
    ]);
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $payment = callbackTypeGuardPayment($installment, $counterparty, 500.0);
    $passthrough = static fn (Payment $processed): Payment => $processed;

    app(AddRequesterWalletTransaction::class)($payment, $passthrough);
    app(AddCounterpartyWalletTransaction::class)($payment, $passthrough);

    expect((float) $counterparty->wallet->fresh()->pending_debit)->toBe(500.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(500.0);
});

test('deleting media requires the media to actually belong to the guarantor request in the URL — cross-request media UUID is rejected (403/404), not deleted', function () {
    $requester = User::factory()->create();
    $ownerRequest = GuarantorRequest::factory()->pendingAdmin()->create([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
        'status' => GuarantorStatusEnum::PendingAdmin,
    ]);
    $otherRequest = GuarantorRequest::factory()->pendingAdmin()->create([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
        'status' => GuarantorStatusEnum::PendingAdmin,
    ]);

    $media = $ownerRequest
        ->addMedia(UploadedFile::fake()->create('owned.pdf', 100, 'application/pdf'))
        ->toMediaCollection('files');

    Sanctum::actingAs($requester);

    $this->deleteJson(route('api.v1.guarantor.guarantor.deleteMedia', [
        'guarantorRequest' => $otherRequest,
        'media' => $media->uuid,
    ]))->assertNotFound();

    expect($ownerRequest->fresh()->getMedia('files')->contains('id', $media->id))->toBeTrue();

    expect(fn () => app(DeleteGuarantorMediaAction::class)->handle($otherRequest->fresh(), $media->fresh()))
        ->toThrow(function (GuarantorException $e): void {
            expect($e->getTranslationKey())->toBe('guarantor.media_not_found')
                ->and($e->getHttpStatusCode())->toBe(404);
        });

    expect($ownerRequest->fresh()->getMedia('files')->contains('id', $media->id))->toBeTrue();
});

test('deleting media still works normally for media that genuinely belongs to the request — regression', function () {
    $requester = User::factory()->create();
    $guarantorRequest = GuarantorRequest::factory()->pendingAdmin()->create([
        'requester_type' => User::class,
        'requester_id' => $requester->getKey(),
        'status' => GuarantorStatusEnum::PendingAdmin,
    ]);

    $media = $guarantorRequest
        ->addMedia(UploadedFile::fake()->create('keep.pdf', 100, 'application/pdf'))
        ->toMediaCollection('files');

    Sanctum::actingAs($requester);

    $this->deleteJson(route('api.v1.guarantor.guarantor.deleteMedia', [
        'guarantorRequest' => $guarantorRequest,
        'media' => $media->uuid,
    ]))->assertSuccessful();

    expect($guarantorRequest->fresh()->getMedia('files'))->toHaveCount(0);
});

test('PayInstallmentAction/InstallmentPolicy::pay now also explicitly reject Individual-type guarantors (defense in depth, even though structurally unreachable today)', function () {
    ['counterparty' => $counterparty, 'request' => $request] = callbackTypeGuardContext([
        'type' => GuarantorTypeEnum::Individual,
    ]);
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);

    $gate = Gate::forUser($counterparty)->inspect('pay', [$installment, $request]);

    expect($gate->denied())->toBeTrue()
        ->and($gate->message())->toBe(__('guarantor.pay_denied_individual_use_lump_sum'));

    expect(fn () => app(PayInstallmentAction::class)->handle($request->fresh(), $installment->fresh(), $counterparty))
        ->toThrow(function (GuarantorException $e): void {
            expect($e->getTranslationKey())->toBe('guarantor.pay_denied_individual_use_lump_sum')
                ->and($e->getHttpStatusCode())->toBe(422);
        });

    expect(Payment::query()->where('product_id', $installment->id)->exists())->toBeFalse();
});

test('an Overdue-status installment can now be paid without landing in NeedsReview — isCompanyPaymentPayable aligned with PayInstallmentAction\'s existing Overdue allowance', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = callbackTypeGuardContext([
        'type' => GuarantorTypeEnum::Company,
        'status' => GuarantorStatusEnum::Overdue,
    ]);
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
        'status' => InstallmentStatusEnum::Overdue,
        'due_date' => now()->subDays(5),
    ]);

    $payment = callbackTypeGuardPayment($installment, $counterparty, 500.0);

    runCallbackTypeGuardPaymentCompleted($payment);

    expect($payment->fresh()->status)->toBe(PaymentStatusEnum::Accepted)
        ->and($installment->fresh()->status)->toBe(InstallmentStatusEnum::Paid)
        ->and($request->fresh()->status)->toBe(GuarantorStatusEnum::InProgress)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(500.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(500.0);
});
