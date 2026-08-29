<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Modules\Guarantor\Actions\Guarantor\EndGuarantorAction;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Jobs\ReleaseInstallmentJob;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;

beforeEach(function (): void {
    Notification::fake();
});

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest}
 */
function paidIndividualGuarantorContext(): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create();
    $request = GuarantorRequest::factory()->accepted()->create([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'counterparty_id' => $counterparty->id,
        'counterparty_type' => User::class,
        'amount' => 1000,
        'fees' => 10,
    ]);

    return compact('requester', 'counterparty', 'request');
}

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest}
 */
function companyGuarantorHoldContext(): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create();
    $request = GuarantorRequest::factory()->company()->accepted()->create([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'counterparty_id' => $counterparty->id,
        'counterparty_type' => User::class,
        'amount' => 1000,
        'fees' => 10,
    ]);

    return compact('requester', 'counterparty', 'request');
}

function completeGuarantorPayment($owner, $product, float $amount): void
{
    $payment = createPaymentFor($owner, $product, [
        'amount' => $amount,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    event(new PaymentCompleted($payment->load('product')));
}

test('completing an Individual guarantor payment creates a pending_debit hold on the counterparty wallet and a pending_credit hold on the requester wallet, both equal to the full charged amount (amount+fees)', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = paidIndividualGuarantorContext();

    completeGuarantorPayment($counterparty, $request, 1010);

    expect((float) $counterparty->wallet->fresh()->pending_debit)->toBe(1010.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(1010.0)
        ->and((float) $counterparty->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $requester->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $requester->wallet->fresh()->balance)->toBe(0.0)
        ->and($request->fresh()->status)->toBe(GuarantorStatusEnum::InProgress);
});

test('completing a Company installment payment creates a pending_debit hold on the counterparty wallet and a pending_credit hold on the requester wallet, both equal to that installment amount only (no fees added)', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = companyGuarantorHoldContext();
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);

    completeGuarantorPayment($counterparty, $installment, 500);

    expect((float) $counterparty->wallet->fresh()->pending_debit)->toBe(500.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(500.0)
        ->and((float) $requester->wallet->fresh()->balance)->toBe(0.0)
        ->and($installment->fresh()->status)->toBe(InstallmentStatusEnum::Paid);
});

test('paying a second Company installment ADDS to the existing pending holds rather than replacing them (incremental, not overwritten)', function () {
    Queue::fake([ReleaseInstallmentJob::class]);

    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = companyGuarantorHoldContext();
    $first = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 400,
    ]);
    $second = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 2,
        'amount' => 600,
    ]);

    completeGuarantorPayment($counterparty, $first, 400);

    expect((float) $counterparty->wallet->fresh()->pending_debit)->toBe(400.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(400.0);

    completeGuarantorPayment($counterparty, $second, 600);

    expect((float) $counterparty->wallet->fresh()->pending_debit)->toBe(1000.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(1000.0);
});

test('ending an Individual guarantor request that was actually paid now results in the requester receiving amount (not amount+fees) in real balance, and the counterparty pending_debit hold clearing', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = paidIndividualGuarantorContext();

    completeGuarantorPayment($counterparty, $request, 1010);

    app(EndGuarantorAction::class)->handle($request->fresh(), $counterparty, 'counterparty');

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Ended)
        ->and((float) $requester->wallet->fresh()->balance)->toBe(1000.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $counterparty->wallet->fresh()->balance)->toBe(0.0);
});

test('paying installment 2 of a Company guarantor correctly releases installment 1s hold to the requesters real balance (net of the proportional fee), matching the existing ReleaseInstallmentAction test expectations', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = companyGuarantorHoldContext();
    $first = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $second = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 2,
        'amount' => 500,
    ]);

    completeGuarantorPayment($counterparty, $first, 500);
    completeGuarantorPayment($counterparty, $second, 500);

    expect($first->fresh()->status)->toBe(InstallmentStatusEnum::Released)
        ->and($second->fresh()->status)->toBe(InstallmentStatusEnum::Paid)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(500.0)
        ->and((float) $requester->wallet->fresh()->balance)->toBe(495.0)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(1000.0);
});
