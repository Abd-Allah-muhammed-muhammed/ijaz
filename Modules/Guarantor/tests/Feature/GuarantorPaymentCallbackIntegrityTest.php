<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Mockery\MockInterface;
use Modules\Guarantor\Actions\Dashboard\AdminApproveGuarantorAction;
use Modules\Guarantor\Actions\Dashboard\AdminRejectGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\CancelGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\EndGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\OpenGuarantorDisputeAction;
use Modules\Guarantor\Actions\Payment\ProcessGuarantorPayment;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Listeners\HandleGuarantorPaymentCompleted;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Repositories\GuarantorRepository;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Models\Payment;
use Spatie\Permission\Models\Permission;

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest, admin: Admin}
 */
function paymentCallbackIntegrityContext(array $requestAttributes = []): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create();
    $request = GuarantorRequest::factory()->create(array_merge([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'counterparty_id' => $counterparty->id,
        'counterparty_type' => User::class,
        'amount' => 1000,
        'fees' => 10,
        'status' => GuarantorStatusEnum::Accepted,
    ], $requestAttributes));

    Permission::firstOrCreate(['name' => 'manage guarantors', 'guard_name' => 'admin']);
    $admin = Admin::query()->create([
        'name' => 'Payment Callback Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
    $admin->givePermissionTo(['manage guarantors']);

    return compact('requester', 'counterparty', 'request', 'admin');
}

function delayedIndividualPayment(GuarantorRequest $request, User $payer, float $amount): Payment
{
    $payment = Payment::query()->create([
        'user_id' => $payer->getKey(),
        'user_type' => User::class,
        'product_id' => $request->id,
        'product_type' => GuarantorRequest::class,
        'amount' => $amount,
        'status' => PaymentStatusEnum::Accepted,
        'driver' => 'testing',
    ]);

    return $payment->load('product');
}

function delayedInstallmentPayment(GuarantorInstallment $installment, User $payer, ?float $amount = null): Payment
{
    $payment = Payment::query()->create([
        'user_id' => $payer->getKey(),
        'user_type' => User::class,
        'product_id' => $installment->id,
        'product_type' => GuarantorInstallment::class,
        'amount' => $amount ?? $installment->amount,
        'status' => PaymentStatusEnum::Accepted,
        'driver' => 'testing',
    ]);

    return $payment->load('product');
}

function runGuarantorPaymentCompleted(Payment $payment): void
{
    DB::transaction(fn () => app(HandleGuarantorPaymentCompleted::class)->handle(new PaymentCompleted($payment)));
}

function assertPaymentCallbackGuarantorRequestLockedDuring(callable $callback): void
{
    /** @var GuarantorRepository&MockInterface $repository */
    $repository = Mockery::mock(GuarantorRepository::class)->makePartial();
    app()->instance(GuarantorRepositoryInterface::class, $repository);

    $callback();

    $repository->shouldHaveReceived('findForUpdate')->once();
}

test('a Guarantor payment that completes AFTER the guarantor was cancelled is rejected — no status overwrite, no wallet holds applied', function () {
    ['counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = paymentCallbackIntegrityContext();
    $payment = delayedIndividualPayment($request, $counterparty, 1010);

    app(CancelGuarantorAction::class)->handle(
        $request->fresh(),
        'admin cancelled before callback',
        null,
        $admin,
    );

    runGuarantorPaymentCompleted($payment);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Cancelled)
        ->and($payment->fresh()->status)->toBe(PaymentStatusEnum::NeedsReview)
        ->and((float) ($counterparty->wallet->fresh()->pending_debit ?? 0))->toBe(0.0)
        ->and((float) ($request->requester->wallet->fresh()->pending_credit ?? 0))->toBe(0.0);
});

test('a Guarantor payment that completes AFTER a dispute was opened is rejected — no status overwrite, no wallet holds applied', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = paymentCallbackIntegrityContext([
        'type' => GuarantorTypeEnum::Company,
    ]);
    $first = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->paid()->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $second = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 2,
        'amount' => 500,
    ]);
    $request->update(['status' => GuarantorStatusEnum::InProgress]);
    $payment = delayedInstallmentPayment($second, $counterparty);

    app(OpenGuarantorDisputeAction::class)->handle(
        $request->fresh(),
        $requester,
        'requester',
        'Dispute opened before delayed callback',
    );

    runGuarantorPaymentCompleted($payment);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Disputed)
        ->and($second->fresh()->status)->toBe(InstallmentStatusEnum::Pending)
        ->and($payment->fresh()->status)->toBe(PaymentStatusEnum::NeedsReview)
        ->and((float) ($counterparty->wallet->fresh()->pending_debit ?? 0))->toBe(0.0)
        ->and((float) ($requester->wallet->fresh()->pending_credit ?? 0))->toBe(0.0);
});

test('a Guarantor payment completing while still validly Accepted/InProgress/Overdue proceeds normally — regression', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $individual] = paymentCallbackIntegrityContext();
    $individualPayment = delayedIndividualPayment($individual, $counterparty, 1010);

    runGuarantorPaymentCompleted($individualPayment);

    expect($individual->fresh()->status)->toBe(GuarantorStatusEnum::InProgress)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(1010.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(1010.0);

    ['request' => $company, 'requester' => $companyRequester, 'counterparty' => $companyCounterparty] = paymentCallbackIntegrityContext([
        'type' => GuarantorTypeEnum::Company,
    ]);
    $installment = GuarantorInstallment::factory()->for($company, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $companyPayment = delayedInstallmentPayment($installment, $companyCounterparty);

    runGuarantorPaymentCompleted($companyPayment);

    expect($company->fresh()->status)->toBe(GuarantorStatusEnum::InProgress)
        ->and($installment->fresh()->status)->toBe(InstallmentStatusEnum::Paid)
        ->and((float) $companyCounterparty->wallet->fresh()->pending_debit)->toBe(500.0)
        ->and((float) $companyRequester->wallet->fresh()->pending_credit)->toBe(500.0);
});

test('ProcessGuarantorPayment locks the parent GuarantorRequest row before checking status, closing the TOCTOU window', function () {
    ['counterparty' => $counterparty, 'request' => $request] = paymentCallbackIntegrityContext();
    $payment = delayedIndividualPayment($request, $counterparty, 1010);

    assertPaymentCallbackGuarantorRequestLockedDuring(function () use ($payment): void {
        expect(app(ProcessGuarantorPayment::class)->handle($payment))->toBeTrue();
    });
});

test('payment amount is verified against the expected GuarantorRequest total (Individual) or installment amount (Company) before creating wallet holds — mismatch is rejected, not silently applied', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $individual] = paymentCallbackIntegrityContext();
    $individualPayment = delayedIndividualPayment($individual, $counterparty, 999.0);

    runGuarantorPaymentCompleted($individualPayment);

    expect($individual->fresh()->status)->toBe(GuarantorStatusEnum::Accepted)
        ->and($individualPayment->fresh()->status)->toBe(PaymentStatusEnum::NeedsReview)
        ->and((float) ($counterparty->wallet->fresh()->pending_debit ?? 0))->toBe(0.0)
        ->and((float) ($requester->wallet->fresh()->pending_credit ?? 0))->toBe(0.0);

    ['request' => $company, 'counterparty' => $companyCounterparty] = paymentCallbackIntegrityContext([
        'type' => GuarantorTypeEnum::Company,
    ]);
    $installment = GuarantorInstallment::factory()->for($company, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $companyPayment = delayedInstallmentPayment($installment, $companyCounterparty, 499.0);

    runGuarantorPaymentCompleted($companyPayment);

    expect($company->fresh()->status)->toBe(GuarantorStatusEnum::Accepted)
        ->and($installment->fresh()->status)->toBe(InstallmentStatusEnum::Pending)
        ->and($companyPayment->fresh()->status)->toBe(PaymentStatusEnum::NeedsReview)
        ->and((float) ($companyCounterparty->wallet->fresh()->pending_debit ?? 0))->toBe(0.0);
});

test('AdminApproveGuarantorAction and AdminRejectGuarantorAction lock the request row before checking PendingAdmin status — concurrent approve+reject cannot both succeed', function () {
    ['request' => $request, 'admin' => $admin] = paymentCallbackIntegrityContext([
        'status' => GuarantorStatusEnum::PendingAdmin,
    ]);

    assertPaymentCallbackGuarantorRequestLockedDuring(function () use ($request, $admin): void {
        app(AdminApproveGuarantorAction::class)->handle($request->fresh(), 'approved', $admin);
    });

    $rejectRequest = GuarantorRequest::factory()->create([
        'status' => GuarantorStatusEnum::PendingAdmin,
        'amount' => 1000,
        'fees' => 10,
    ]);

    assertPaymentCallbackGuarantorRequestLockedDuring(function () use ($rejectRequest, $admin): void {
        app(AdminRejectGuarantorAction::class)->handle(
            $rejectRequest->fresh(),
            'rejected',
            null,
            $admin,
        );
    });
});

test('EndGuarantorAction locks the request row before checking status — concurrent End and payment-callback cannot both apply', function () {
    ['counterparty' => $counterparty, 'request' => $request] = paymentCallbackIntegrityContext([
        'status' => GuarantorStatusEnum::InProgress,
    ]);

    assertPaymentCallbackGuarantorRequestLockedDuring(function () use ($request, $counterparty): void {
        app(EndGuarantorAction::class)->handle($request->fresh(), $counterparty, 'counterparty');
    });
});
