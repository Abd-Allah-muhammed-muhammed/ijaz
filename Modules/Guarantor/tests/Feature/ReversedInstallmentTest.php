<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Modules\Guarantor\Actions\Guarantor\CancelGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\EndGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\OpenGuarantorDisputeAction;
use Modules\Guarantor\Actions\Guarantor\ResolveDisputeEscalateAction;
use Modules\Guarantor\Actions\Guarantor\ResolveDisputeFullToPartyAction;
use Modules\Guarantor\Actions\Guarantor\ResolveDisputePercentageSplitAction;
use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
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
function reversedInstallmentContext(array $requestAttributes = []): array
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
    $admin = Admin::query()->create([
        'name' => 'Reversed Installment Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
    $admin->givePermissionTo('manage guarantors');

    return compact('requester', 'counterparty', 'request', 'admin');
}

function completeReversedInstallmentPayment($owner, $product, float $amount): void
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
function companyInstallmentsWithFirstPaidForReversedTest(GuarantorRequest $request, User $counterparty): array
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

    completeReversedInstallmentPayment($counterparty, $first, 500);

    return [$first->fresh(), $second->fresh()];
}

test('ordinary Cancel transitions a previously-Paid unreleased installment to Reversed, not left at Paid', function () {
    ['counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = reversedInstallmentContext();
    [$first] = companyInstallmentsWithFirstPaidForReversedTest($request, $counterparty);

    app(CancelGuarantorAction::class)->handle($request->fresh(), 'Admin cancelled', null, $admin);

    expect($first->fresh()->status)->toBe(InstallmentStatusEnum::Reversed)
        ->and($first->fresh()->status)->not->toBe(InstallmentStatusEnum::Paid);
});

test('dispute full-to-counterparty resolution transitions a previously-Paid unreleased installment to Reversed', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = reversedInstallmentContext();
    [$first] = companyInstallmentsWithFirstPaidForReversedTest($request, $counterparty);

    app(OpenGuarantorDisputeAction::class)->handle($request->fresh(), $requester, 'requester', 'Dispute reason');
    app(ResolveDisputeFullToPartyAction::class)->handle($request->fresh(), $admin, 'counterparty');

    expect($first->fresh()->status)->toBe(InstallmentStatusEnum::Reversed);
});

test('dispute escalate resolution transitions a previously-Paid unreleased installment to Reversed', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = reversedInstallmentContext();
    [$first] = companyInstallmentsWithFirstPaidForReversedTest($request, $counterparty);

    app(OpenGuarantorDisputeAction::class)->handle($request->fresh(), $requester, 'requester', 'Dispute reason');
    app(ResolveDisputeEscalateAction::class)->handle($request->fresh(), $admin);

    expect($first->fresh()->status)->toBe(InstallmentStatusEnum::Reversed);
});

test('dispute full-to-requester and ordinary End still correctly transition to Released, not Reversed — regression, unaffected by this change', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = reversedInstallmentContext();
    [$firstEnd] = companyInstallmentsWithFirstPaidForReversedTest($request, $counterparty);

    app(EndGuarantorAction::class)->handle($request->fresh(), $requester, 'requester');

    expect($firstEnd->fresh()->status)->toBe(InstallmentStatusEnum::Released)
        ->and($firstEnd->fresh()->status)->not->toBe(InstallmentStatusEnum::Reversed);

    ['requester' => $requesterDispute, 'request' => $requestDispute, 'admin' => $adminDispute] = reversedInstallmentContext();
    [$firstDispute] = companyInstallmentsWithFirstPaidForReversedTest($requestDispute, $requestDispute->counterparty);

    app(OpenGuarantorDisputeAction::class)->handle($requestDispute->fresh(), $requesterDispute, 'requester', 'Dispute reason');
    app(ResolveDisputeFullToPartyAction::class)->handle($requestDispute->fresh(), $adminDispute, 'requester');

    expect($firstDispute->fresh()->status)->toBe(InstallmentStatusEnum::Released)
        ->and($firstDispute->fresh()->status)->not->toBe(InstallmentStatusEnum::Reversed);
});

test('percentage split still marks the held installment Released (requester received their share), not Reversed — regression', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = reversedInstallmentContext();
    [$first] = companyInstallmentsWithFirstPaidForReversedTest($request, $counterparty);

    app(OpenGuarantorDisputeAction::class)->handle($request->fresh(), $requester, 'requester', 'Dispute reason');
    app(ResolveDisputePercentageSplitAction::class)->handle($request->fresh(), $admin, 60);

    expect($first->fresh()->status)->toBe(InstallmentStatusEnum::Released)
        ->and($first->fresh()->status)->not->toBe(InstallmentStatusEnum::Reversed);
});

test('attempting to Release a Reversed installment is rejected at the Action level with a clear message, defense in depth even if the button is hidden', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
        'status' => InstallmentStatusEnum::Reversed,
    ]);

    try {
        app(ReleaseInstallmentAction::class)->handle($installment, 'admin');
        expect(false)->toBeTrue('Expected GuarantorException was not thrown');
    } catch (GuarantorException $exception) {
        expect($exception->getTranslationKey())->toBe('guarantor.release_denied_installment_reversed');
    }
});

test('the Release action refuses to run if the parent guarantor is in ANY terminal status, regardless of the installment row status — belt and suspenders', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->create([
        'amount' => 1000,
        'fees' => 10,
        'status' => GuarantorStatusEnum::Cancelled,
        'cancelled_at' => now(),
    ]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->paid()->create([
        'order' => 1,
        'amount' => 500,
    ]);

    try {
        app(ReleaseInstallmentAction::class)->handle($installment, 'admin');
        expect(false)->toBeTrue('Expected GuarantorException was not thrown');
    } catch (GuarantorException $exception) {
        expect($exception->getTranslationKey())->toBe('guarantor.release_denied_guarantor_terminal');
    }
});

test('InstallmentStatusEnum handles the new Reversed case everywhere an exhaustive match exists (color, label) without an UnhandledMatchError', function () {
    expect(InstallmentStatusEnum::Reversed->value)->toBe('reversed')
        ->and(InstallmentStatusEnum::Reversed->toString())->toBe(__('guarantor.installment_status.reversed'))
        ->and(InstallmentStatusEnum::Reversed->color())->toBeString()
        ->and(InstallmentStatusEnum::Reversed->isTerminal())->toBeTrue()
        ->and(InstallmentStatusEnum::Reversed->isPayable())->toBeFalse();

    foreach (InstallmentStatusEnum::cases() as $case) {
        expect($case->color())->toBeString()
            ->and($case->toString())->toBeString()
            ->and($case->toArray())->toHaveKeys(['value', 'label', 'color']);
    }
});
