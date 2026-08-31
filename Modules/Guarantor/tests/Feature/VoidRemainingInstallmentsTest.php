<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Actions\Guarantor\CancelGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\EndGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\OpenGuarantorDisputeAction;
use Modules\Guarantor\Actions\Guarantor\ResolveDisputeEscalateAction;
use Modules\Guarantor\Actions\Guarantor\ResolveDisputeFullToPartyAction;
use Modules\Guarantor\Actions\Guarantor\ResolveDisputePercentageSplitAction;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Notification::fake();
});

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest, admin: Admin}
 */
function voidRemainingInstallmentsContext(array $requestAttributes = []): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create();
    $request = GuarantorRequest::factory()->create(array_merge([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'counterparty_id' => $counterparty->id,
        'counterparty_type' => User::class,
        'type' => GuarantorTypeEnum::Company,
        'amount' => 1000,
        'fees' => 10,
        'status' => GuarantorStatusEnum::InProgress,
    ], $requestAttributes));

    Permission::firstOrCreate(['name' => 'manage guarantors', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'show guarantors', 'guard_name' => 'admin']);
    $admin = Admin::query()->create([
        'name' => 'Void Installments Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
    $admin->givePermissionTo(['manage guarantors', 'show guarantors']);

    return compact('requester', 'counterparty', 'request', 'admin');
}

function completeVoidRemainingPayment($owner, $product, float $amount): void
{
    $payment = createPaymentFor($owner, $product, [
        'amount' => $amount,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    event(new PaymentCompleted($payment->load('product')));
}

/**
 * @return array{0: GuarantorInstallment, 1: GuarantorInstallment}
 */
function companyInstallmentsWithFirstPaid(GuarantorRequest $request, User $counterparty): array
{
    $first = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
        'status' => InstallmentStatusEnum::Pending,
    ]);
    $second = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 2,
        'amount' => 500,
        'status' => InstallmentStatusEnum::Pending,
    ]);

    completeVoidRemainingPayment($counterparty, $first, 500);

    return [$first->fresh(), $second->fresh()];
}

test('remaining Pending installments are voided when a guarantor is Ended', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = voidRemainingInstallmentsContext();
    [$first, $second] = companyInstallmentsWithFirstPaid($request, $counterparty);

    app(EndGuarantorAction::class)->handle($request->fresh(), $counterparty, 'counterparty');

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Ended)
        ->and($first->fresh()->status)->toBe(InstallmentStatusEnum::Released)
        ->and($second->fresh()->status)->toBe(InstallmentStatusEnum::Voided);
});

test('remaining Pending installments are voided when a guarantor is Cancelled', function () {
    ['counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = voidRemainingInstallmentsContext();
    [$first, $second] = companyInstallmentsWithFirstPaid($request, $counterparty);

    app(CancelGuarantorAction::class)->handle($request->fresh(), 'Admin cancelled', null, $admin);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Cancelled)
        ->and($first->fresh()->status)->toBe(InstallmentStatusEnum::Reversed)
        ->and($second->fresh()->status)->toBe(InstallmentStatusEnum::Voided);
});

test('remaining Pending installments are voided when a dispute resolves full-to-requester (EndedViaDispute)', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = voidRemainingInstallmentsContext();
    [$first, $second] = companyInstallmentsWithFirstPaid($request, $counterparty);

    app(OpenGuarantorDisputeAction::class)->handle($request->fresh(), $requester, 'requester', 'Dispute reason');
    app(ResolveDisputeFullToPartyAction::class)->handle($request->fresh(), $admin, 'requester');

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::EndedViaDispute)
        ->and($first->fresh()->status)->toBe(InstallmentStatusEnum::Released)
        ->and($second->fresh()->status)->toBe(InstallmentStatusEnum::Voided);
});

test('remaining Pending installments are voided when a dispute resolves full-to-counterparty (CancelledViaDispute)', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = voidRemainingInstallmentsContext();
    [$first, $second] = companyInstallmentsWithFirstPaid($request, $counterparty);

    app(OpenGuarantorDisputeAction::class)->handle($request->fresh(), $requester, 'requester', 'Dispute reason');
    app(ResolveDisputeFullToPartyAction::class)->handle($request->fresh(), $admin, 'counterparty');

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::CancelledViaDispute)
        ->and($first->fresh()->status)->toBe(InstallmentStatusEnum::Reversed)
        ->and($second->fresh()->status)->toBe(InstallmentStatusEnum::Voided);
});

test('remaining Pending installments are voided when a dispute is escalated (Escalated)', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = voidRemainingInstallmentsContext();
    [$first, $second] = companyInstallmentsWithFirstPaid($request, $counterparty);

    app(OpenGuarantorDisputeAction::class)->handle($request->fresh(), $requester, 'requester', 'Dispute reason');
    app(ResolveDisputeEscalateAction::class)->handle($request->fresh(), $admin);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Escalated)
        ->and($first->fresh()->status)->toBe(InstallmentStatusEnum::Reversed)
        ->and($second->fresh()->status)->toBe(InstallmentStatusEnum::Voided);
});

test('remaining Pending installments are voided when a dispute resolves via percentage split (Settled) — the currently-held installment still becomes Released, others become Voided, not both Released', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = voidRemainingInstallmentsContext();
    [$first, $second] = companyInstallmentsWithFirstPaid($request, $counterparty);

    app(OpenGuarantorDisputeAction::class)->handle($request->fresh(), $requester, 'requester', 'Dispute reason');
    app(ResolveDisputePercentageSplitAction::class)->handle($request->fresh(), $admin, 60);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Settled)
        ->and($first->fresh()->status)->toBe(InstallmentStatusEnum::Released)
        ->and($second->fresh()->status)->toBe(InstallmentStatusEnum::Voided);
});

test('already-Paid or already-Released installments are never touched by voiding — only Pending/Overdue ones', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = voidRemainingInstallmentsContext();

    $released = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 300,
        'status' => InstallmentStatusEnum::Released,
        'released_at' => now()->subDay(),
    ]);
    $paid = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 2,
        'amount' => 300,
        'status' => InstallmentStatusEnum::Paid,
        'paid_at' => now(),
    ]);
    $pending = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 3,
        'amount' => 200,
        'status' => InstallmentStatusEnum::Pending,
    ]);
    $overdue = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 4,
        'amount' => 200,
        'status' => InstallmentStatusEnum::Overdue,
        'due_date' => now()->subDays(5),
    ]);

    $requester->wallet()->firstOrCreate();
    $counterparty->wallet()->firstOrCreate();
    $requester->wallet->update(['pending_credit' => 300, 'balance' => 0]);
    $counterparty->wallet->update(['pending_debit' => 300, 'balance' => 1000]);

    app(EndGuarantorAction::class)->handle($request->fresh(), $counterparty, 'counterparty');

    expect($released->fresh()->status)->toBe(InstallmentStatusEnum::Released)
        ->and($paid->fresh()->status)->toBe(InstallmentStatusEnum::Released)
        ->and($pending->fresh()->status)->toBe(InstallmentStatusEnum::Voided)
        ->and($overdue->fresh()->status)->toBe(InstallmentStatusEnum::Voided);
});

test('attempting to pay an installment while the guarantor is Disputed returns a specific "this guarantee has an active dispute" message, not a generic unauthorized error', function () {
    ['counterparty' => $counterparty, 'request' => $request] = voidRemainingInstallmentsContext([
        'status' => GuarantorStatusEnum::Disputed,
    ]);
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
        'status' => InstallmentStatusEnum::Pending,
    ]);

    expect(Gate::forUser($counterparty)->allows('pay', [$installment, $request]))->toBeTrue();

    Sanctum::actingAs($counterparty);
    $this->postJson(route('api.v1.guarantor.guarantor.installments.pay', [
        'guarantorRequest' => $request,
        'installment' => $installment,
    ]))
        ->assertUnprocessable()
        ->assertJson([
            'message' => __('guarantor.pay_denied_active_dispute'),
        ]);
});

test('attempting to pay a Voided installment returns a specific "this installment is no longer payable" message', function () {
    ['counterparty' => $counterparty, 'request' => $request] = voidRemainingInstallmentsContext([
        'status' => GuarantorStatusEnum::Accepted,
    ]);
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
        'status' => InstallmentStatusEnum::Voided,
    ]);

    expect(Gate::forUser($counterparty)->allows('pay', [$installment, $request]))->toBeTrue();

    Sanctum::actingAs($counterparty);
    $this->postJson(route('api.v1.guarantor.guarantor.installments.pay', [
        'guarantorRequest' => $request,
        'installment' => $installment,
    ]))
        ->assertUnprocessable()
        ->assertJson([
            'message' => __('guarantor.pay_denied_installment_voided'),
        ]);
});

test('InstallmentStatusEnum no longer has a Refunded case (dead, replaced by the new Voided case)', function () {
    $values = array_column(InstallmentStatusEnum::cases(), 'value');
    $names = array_map(fn (InstallmentStatusEnum $case) => $case->name, InstallmentStatusEnum::cases());

    expect($values)->not->toContain('refunded')
        ->and($names)->not->toContain('Refunded')
        ->and($values)->toContain('voided')
        ->and(InstallmentStatusEnum::Voided->isTerminal())->toBeTrue()
        ->and(InstallmentStatusEnum::Voided->isPayable())->toBeFalse();
});
