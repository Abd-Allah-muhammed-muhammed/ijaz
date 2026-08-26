<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Modules\Guarantor\Actions\Guarantor\CancelGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\LogGuarantorStatusHistoryAction;
use Modules\Guarantor\Actions\Installment\EscalateUnpaidOverdueInstallmentAction;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Jobs\AutoReleaseInstallmentJob;
use Modules\Guarantor\Jobs\NotifyOverdueInstallmentJob;
use Modules\Guarantor\Listeners\HandleGuarantorPaymentCompleted;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorPaymentReceivedNotification;
use Modules\Guarantor\Notifications\UnpaidOverdueInstallmentEscalationNotification;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Payment\Models\Payment;
use Spatie\Permission\Models\Permission;

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest, admin: Admin}
 */
function paymentNotificationContext(array $requestAttributes = []): array
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

    Permission::firstOrCreate(['name' => 'show guarantors', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'manage guarantors', 'guard_name' => 'admin']);
    $admin = Admin::query()->create([
        'name' => 'Escalation Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
    $admin->givePermissionTo(['show guarantors', 'manage guarantors']);

    return compact('requester', 'counterparty', 'request', 'admin');
}

function notifyEscalationIndividualPayment(GuarantorRequest $request, User $payer): Payment
{
    $payment = createPaymentFor($payer, $request, [
        'amount' => 1010,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    app(HandleGuarantorPaymentCompleted::class)->handle(new PaymentCompleted($payment->load('product')));

    return $payment;
}

test('a completed Guarantor payment (Individual or Company installment) now sends a notification to both parties', function () {
    Notification::fake();

    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = paymentNotificationContext();

    notifyEscalationIndividualPayment($request, $counterparty);

    Notification::assertSentTo($requester, GuarantorPaymentReceivedNotification::class);
    Notification::assertSentTo($counterparty, GuarantorPaymentReceivedNotification::class);

    Notification::fake();

    $companyRequest = GuarantorRequest::factory()->company()->accepted()->create([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'counterparty_id' => $counterparty->id,
        'counterparty_type' => User::class,
        'amount' => 1000,
        'fees' => 10,
    ]);
    $installment = GuarantorInstallment::factory()->for($companyRequest, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);

    $payment = createPaymentFor($counterparty, $installment, [
        'amount' => 500,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    app(HandleGuarantorPaymentCompleted::class)->handle(new PaymentCompleted($payment->load('product')));

    Notification::assertSentTo($requester, GuarantorPaymentReceivedNotification::class);
    Notification::assertSentTo($counterparty, GuarantorPaymentReceivedNotification::class);
});

test('a stale rejected payment completion does not send GuarantorPaymentReceivedNotification', function () {
    Notification::fake();

    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = paymentNotificationContext();

    app(CancelGuarantorAction::class)->handle($request->fresh(), 'Cancelled before callback', null, $admin);

    notifyEscalationIndividualPayment($request->fresh(), $counterparty);

    Notification::assertNotSentTo($requester, GuarantorPaymentReceivedNotification::class);
    Notification::assertNotSentTo($counterparty, GuarantorPaymentReceivedNotification::class);
});

test('an installment still Pending past 14 days overdue triggers an admin-visible escalation notification, with no automatic status/wallet change', function () {
    Notification::fake();
    Log::spy();

    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = paymentNotificationContext([
        'type' => GuarantorTypeEnum::Company,
        'status' => GuarantorStatusEnum::Overdue,
    ]);

    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
        'due_date' => now()->subDays(14),
        'status' => InstallmentStatusEnum::Pending,
    ]);

    $requester->wallet->update(['pending_credit' => 0, 'balance' => 100]);
    $counterparty->wallet->update(['pending_debit' => 0, 'balance' => 200]);

    (new NotifyOverdueInstallmentJob($installment))
        ->handle(
            app(LogGuarantorStatusHistoryAction::class),
            app(EscalateUnpaidOverdueInstallmentAction::class),
        );

    Notification::assertSentTo($admin, UnpaidOverdueInstallmentEscalationNotification::class);

    $installment->refresh();
    $request->refresh();
    $requester->wallet->refresh();
    $counterparty->wallet->refresh();

    expect($installment->status)->toBe(InstallmentStatusEnum::Pending)
        ->and($installment->escalated_at)->not->toBeNull()
        ->and($request->status)->toBe(GuarantorStatusEnum::Overdue)
        ->and((float) $requester->wallet->balance)->toBe(100.0)
        ->and((float) $counterparty->wallet->balance)->toBe(200.0);

    Log::shouldHaveReceived('warning')
        ->once()
        ->with(
            'Unpaid guarantor installment overdue past 14 days — admin escalation sent',
            Mockery::on(fn (array $context) => $context['installment_id'] === $installment->id),
        );
});

test('the escalation only fires once per installment past the threshold, not repeatedly every scheduler run', function () {
    Notification::fake();

    ['admin' => $admin, 'request' => $request] = paymentNotificationContext([
        'type' => GuarantorTypeEnum::Company,
        'status' => GuarantorStatusEnum::Overdue,
    ]);

    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
        'due_date' => now()->subDays(15),
        'status' => InstallmentStatusEnum::Pending,
    ]);

    $job = new NotifyOverdueInstallmentJob($installment);
    $logAction = app(LogGuarantorStatusHistoryAction::class);
    $escalateAction = app(EscalateUnpaidOverdueInstallmentAction::class);

    $job->handle($logAction, $escalateAction);
    $job->handle($logAction, $escalateAction);

    Notification::assertSentToTimes($admin, UnpaidOverdueInstallmentEscalationNotification::class, 1);
});

test('the existing day-14 auto-release for PAID overdue installments is unaffected — regression', function () {
    Queue::fake();

    $request = GuarantorRequest::factory()->company()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
        'due_date' => now()->subDays(14),
        'status' => InstallmentStatusEnum::Paid,
        'paid_at' => now()->subDays(10),
    ]);

    (new NotifyOverdueInstallmentJob($installment))
        ->handle(
            app(LogGuarantorStatusHistoryAction::class),
            app(EscalateUnpaidOverdueInstallmentAction::class),
        );

    Queue::assertPushed(AutoReleaseInstallmentJob::class, 1);
    Queue::assertPushedOn('guarantor', AutoReleaseInstallmentJob::class);
});
