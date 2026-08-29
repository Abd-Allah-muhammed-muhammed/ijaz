<?php

use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Actions\Guarantor\ApproveEndRequestAction;
use Modules\Guarantor\Actions\Guarantor\EndGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\RejectEndRequestAction;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Notifications\GuarantorEndApprovedNotification;
use Modules\Guarantor\Notifications\GuarantorEndedNotification;
use Modules\Guarantor\Notifications\GuarantorEndRejectedNotification;
use Modules\Guarantor\Notifications\GuarantorEndRequestedNotification;

beforeEach(function (): void {
    Notification::fake();
});

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest}
 */
function asymmetricEndContext(array $requestAttributes = []): array
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
        'status' => GuarantorStatusEnum::InProgress,
    ], $requestAttributes));

    $requester->wallet()->firstOrCreate();
    $counterparty->wallet()->firstOrCreate();

    return compact('requester', 'counterparty', 'request');
}

function seedIndividualHolds(User $requester, User $counterparty): void
{
    $requester->wallet->update(['pending_credit' => 1010, 'balance' => 0]);
    $counterparty->wallet->update(['pending_debit' => 1010, 'balance' => 0]);
}

test('counterparty calling End still completes immediately — Ended, wallet released, unchanged from today', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = asymmetricEndContext();
    seedIndividualHolds($requester, $counterparty);

    Sanctum::actingAs($counterparty);

    $this->postJson(route('api.v1.guarantor.guarantor.end', $request))
        ->assertSuccessful()
        ->assertJsonPath('data.status.value', GuarantorStatusEnum::Ended->value);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Ended)
        ->and($request->fresh()->ended_at)->not->toBeNull()
        ->and((float) $requester->wallet->fresh()->balance)->toBe(1000.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(0.0);

    Notification::assertSentTo($requester, GuarantorEndedNotification::class);
    Notification::assertSentTo($counterparty, GuarantorEndedNotification::class);
});

test('requester calling End now transitions to PendingCounterpartyEndApproval, not Ended — no wallet change yet', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = asymmetricEndContext();
    seedIndividualHolds($requester, $counterparty);

    Sanctum::actingAs($requester);

    $this->postJson(route('api.v1.guarantor.guarantor.end', $request))
        ->assertSuccessful()
        ->assertJsonPath('data.status.value', GuarantorStatusEnum::PendingCounterpartyEndApproval->value);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::PendingCounterpartyEndApproval)
        ->and($request->fresh()->ended_at)->toBeNull()
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(1010.0)
        ->and((float) $requester->wallet->fresh()->balance)->toBe(0.0)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(1010.0);

    Notification::assertSentTo($counterparty, GuarantorEndRequestedNotification::class);
    Notification::assertNotSentTo($requester, GuarantorEndedNotification::class);
    Notification::assertNotSentTo($counterparty, GuarantorEndedNotification::class);
});

test('counterparty can approve a pending end request — transitions to Ended with the same wallet mechanics as an ordinary End', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = asymmetricEndContext();
    seedIndividualHolds($requester, $counterparty);

    app(EndGuarantorAction::class)->handle($request->fresh(), $requester, 'requester');

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::PendingCounterpartyEndApproval);

    Sanctum::actingAs($counterparty);

    $this->postJson(route('api.v1.guarantor.guarantor.end.approve', $request))
        ->assertSuccessful()
        ->assertJsonPath('data.status.value', GuarantorStatusEnum::Ended->value);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Ended)
        ->and($request->fresh()->ended_at)->not->toBeNull()
        ->and((float) $requester->wallet->fresh()->balance)->toBe(1000.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(0.0);

    Notification::assertSentTo($requester, GuarantorEndApprovedNotification::class);
    Notification::assertSentTo($requester, GuarantorEndedNotification::class);
    Notification::assertSentTo($counterparty, GuarantorEndedNotification::class);
});

test('counterparty can reject a pending end request with a required reason — reverts to whichever status (in_progress or overdue) it came from, derived from status_histories, no wallet change', function (GuarantorStatusEnum $priorStatus) {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = asymmetricEndContext([
        'status' => $priorStatus,
    ]);
    seedIndividualHolds($requester, $counterparty);

    app(EndGuarantorAction::class)->handle($request->fresh(), $requester, 'requester');

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::PendingCounterpartyEndApproval);

    Sanctum::actingAs($counterparty);

    $this->postJson(route('api.v1.guarantor.guarantor.end.reject', $request), [
        'reason' => 'Work is not finished yet',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.status.value', $priorStatus->value);

    expect($request->fresh()->status)->toBe($priorStatus)
        ->and($request->fresh()->ended_at)->toBeNull()
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(1010.0)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(1010.0);

    Notification::assertSentTo(
        $requester,
        GuarantorEndRejectedNotification::class,
        fn (GuarantorEndRejectedNotification $notification): bool => $notification->reason === 'Work is not finished yet',
    );
})->with([
    GuarantorStatusEnum::InProgress,
    GuarantorStatusEnum::Overdue,
]);

test('rejecting without a reason is rejected with a clear validation error', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = asymmetricEndContext();

    app(EndGuarantorAction::class)->handle($request->fresh(), $requester, 'requester');

    Sanctum::actingAs($counterparty);

    $this->postJson(route('api.v1.guarantor.guarantor.end.reject', $request), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reason']);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::PendingCounterpartyEndApproval);
});

test('requester cannot approve/reject their own pending end request — counterparty only', function () {
    ['requester' => $requester, 'request' => $request] = asymmetricEndContext();

    app(EndGuarantorAction::class)->handle($request->fresh(), $requester, 'requester');

    Sanctum::actingAs($requester);

    $this->postJson(route('api.v1.guarantor.guarantor.end.approve', $request))
        ->assertForbidden();

    $this->postJson(route('api.v1.guarantor.guarantor.end.reject', $request), [
        'reason' => 'I cannot reject my own request',
    ])->assertForbidden();

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::PendingCounterpartyEndApproval);
});

test('requester cannot request end again while one is already pending — duplicate request blocked', function () {
    ['requester' => $requester, 'request' => $request] = asymmetricEndContext();

    app(EndGuarantorAction::class)->handle($request->fresh(), $requester, 'requester');

    Sanctum::actingAs($requester);

    $this->postJson(route('api.v1.guarantor.guarantor.end', $request))
        ->assertForbidden();

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::PendingCounterpartyEndApproval);
});

test('dispute cannot be opened directly from PendingCounterpartyEndApproval — must reject first, then dispute is reachable from the reverted status', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = asymmetricEndContext();

    app(EndGuarantorAction::class)->handle($request->fresh(), $requester, 'requester');

    Sanctum::actingAs($requester);

    $this->postJson(route('api.v1.guarantor.guarantor.dispute', $request), [
        'reason' => 'Cannot dispute while end is pending',
    ])->assertForbidden();

    Sanctum::actingAs($counterparty);

    $this->postJson(route('api.v1.guarantor.guarantor.dispute', $request), [
        'reason' => 'Cannot dispute while end is pending',
    ])->assertForbidden();

    app(RejectEndRequestAction::class)->handle(
        $request->fresh(),
        $counterparty,
        'counterparty',
        'Rejecting so we can dispute',
    );

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::InProgress);

    Sanctum::actingAs($requester);

    $this->postJson(route('api.v1.guarantor.guarantor.dispute', $request), [
        'reason' => 'Now we can open a dispute',
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.status.value', GuarantorStatusEnum::Disputed->value);
});

test('Company: requester-initiated end still correctly releases the latest paid installment once counterparty approves, same as ordinary End does today', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = asymmetricEndContext([
        'type' => GuarantorTypeEnum::Company,
        'status' => GuarantorStatusEnum::InProgress,
        'amount' => 1000,
        'fees' => 10,
    ]);

    $first = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
        'status' => InstallmentStatusEnum::Released,
        'released_at' => now()->subDay(),
    ]);
    $second = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 2,
        'amount' => 500,
        'status' => InstallmentStatusEnum::Paid,
        'paid_at' => now(),
    ]);
    $pending = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 3,
        'amount' => 200,
        'status' => InstallmentStatusEnum::Pending,
    ]);

    $requester->wallet->update(['pending_credit' => 500, 'balance' => 495]);
    $counterparty->wallet->update(['pending_debit' => 1000, 'balance' => 0]);

    app(EndGuarantorAction::class)->handle($request->fresh(), $requester, 'requester');

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::PendingCounterpartyEndApproval)
        ->and($second->fresh()->status)->toBe(InstallmentStatusEnum::Paid)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(500.0);

    app(ApproveEndRequestAction::class)->handle($request->fresh(), $counterparty, 'counterparty');

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Ended)
        ->and($first->fresh()->status)->toBe(InstallmentStatusEnum::Released)
        ->and($second->fresh()->status)->toBe(InstallmentStatusEnum::Released)
        ->and($pending->fresh()->status)->toBe(InstallmentStatusEnum::Voided)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(0.0);
});

test('both parties are notified appropriately at each step: end requested (to counterparty), approved (to requester), rejected (to requester, with reason)', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $approveRequest] = asymmetricEndContext();
    seedIndividualHolds($requester, $counterparty);

    app(EndGuarantorAction::class)->handle($approveRequest->fresh(), $requester, 'requester');
    Notification::assertSentTo($counterparty, GuarantorEndRequestedNotification::class);
    Notification::assertNotSentTo($requester, GuarantorEndRequestedNotification::class);

    app(ApproveEndRequestAction::class)->handle($approveRequest->fresh(), $counterparty, 'counterparty');
    Notification::assertSentTo($requester, GuarantorEndApprovedNotification::class);

    ['requester' => $requester2, 'counterparty' => $counterparty2, 'request' => $rejectRequest] = asymmetricEndContext();
    seedIndividualHolds($requester2, $counterparty2);

    Notification::fake();

    app(EndGuarantorAction::class)->handle($rejectRequest->fresh(), $requester2, 'requester');
    Notification::assertSentTo($counterparty2, GuarantorEndRequestedNotification::class);

    app(RejectEndRequestAction::class)->handle(
        $rejectRequest->fresh(),
        $counterparty2,
        'counterparty',
        'Not ready to end',
    );

    Notification::assertSentTo(
        $requester2,
        GuarantorEndRejectedNotification::class,
        fn (GuarantorEndRejectedNotification $notification): bool => $notification->reason === 'Not ready to end',
    );
    Notification::assertNotSentTo($counterparty2, GuarantorEndRejectedNotification::class);
});

test('PendingCounterpartyEndApproval handles every exhaustive match (color, label, terminal check = false) without an UnhandledMatchError', function () {
    $status = GuarantorStatusEnum::PendingCounterpartyEndApproval;

    expect($status->value)->toBe('pending_counterparty_end_approval')
        ->and($status->isTerminal())->toBeFalse()
        ->and($status->color())->toBeString()->not->toBe('')
        ->and($status->toString())->toBeString()->not->toBe('')
        ->and($status->toArray())->toMatchArray([
            'value' => $status->value,
            'label' => $status->toString(),
            'color' => $status->color(),
        ]);

    foreach (GuarantorStatusEnum::cases() as $case) {
        expect($case->color())->toBeString();
    }

    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::InProgress,
        GuarantorStatusEnum::PendingCounterpartyEndApproval,
        'requester',
    ))->toBeTrue()
        ->and(GuarantorStatusEnum::isAllowed(
            GuarantorStatusEnum::InProgress,
            GuarantorStatusEnum::Ended,
            'requester',
        ))->toBeFalse()
        ->and(GuarantorStatusEnum::isAllowed(
            GuarantorStatusEnum::InProgress,
            GuarantorStatusEnum::Ended,
            'counterparty',
        ))->toBeTrue()
        ->and(GuarantorStatusEnum::isAllowed(
            GuarantorStatusEnum::PendingCounterpartyEndApproval,
            GuarantorStatusEnum::Ended,
            'counterparty',
        ))->toBeTrue()
        ->and(GuarantorStatusEnum::isAllowed(
            GuarantorStatusEnum::PendingCounterpartyEndApproval,
            GuarantorStatusEnum::InProgress,
            'counterparty',
        ))->toBeTrue()
        ->and(GuarantorStatusEnum::isAllowed(
            GuarantorStatusEnum::PendingCounterpartyEndApproval,
            GuarantorStatusEnum::Ended,
            'requester',
        ))->toBeFalse()
        ->and(GuarantorStatusEnum::isAllowed(
            GuarantorStatusEnum::PendingCounterpartyEndApproval,
            GuarantorStatusEnum::Disputed,
            'requester',
        ))->toBeFalse();
});
