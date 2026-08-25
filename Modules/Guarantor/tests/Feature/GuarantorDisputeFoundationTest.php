<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Actions\Chat\OpenGuarantorChatAction;
use Modules\Guarantor\Actions\Guarantor\CancelGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\EndGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\OpenGuarantorDisputeAction;
use Modules\Guarantor\Actions\Installment\PayInstallmentAction;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Handlers\GuarantorChatHandler;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Models\GuarantorStatusHistory;
use Modules\Guarantor\Notifications\GuarantorDisputedNotification;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Notification::fake();
});

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest, admin: Admin}
 */
function disputeFoundationContext(array $requestAttributes = []): array
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

    Permission::firstOrCreate(['name' => 'manage guarantors', 'guard_name' => 'admin']);
    $admin = Admin::query()->create([
        'name' => 'Dispute Foundation Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
    $admin->givePermissionTo('manage guarantors');

    return compact('requester', 'counterparty', 'request', 'admin');
}

function completeDisputeFoundationPayment($owner, $product, float $amount): void
{
    $payment = createPaymentFor($owner, $product, [
        'amount' => $amount,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    event(new PaymentCompleted($payment->load('product')));
}

test('cancelling a guarantor only reverses THIS guarantor\'s held wallet amount, not the owner\'s entire pending_credit/pending_debit, when other unrelated holds exist on the same wallet', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeFoundationContext([
        'status' => GuarantorStatusEnum::Accepted,
    ]);

    completeDisputeFoundationPayment($counterparty, $request, 1010);

    $unrelated = GuarantorRequest::factory()->create([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'counterparty_id' => $counterparty->id,
        'counterparty_type' => User::class,
        'amount' => 500,
        'fees' => 5,
        'status' => GuarantorStatusEnum::Accepted,
    ]);
    completeDisputeFoundationPayment($counterparty, $unrelated, 505);

    expect((float) $requester->wallet->fresh()->pending_credit)->toBe(1515.0)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(1515.0);

    app(CancelGuarantorAction::class)->handle($request->fresh(), 'Admin cancelled', null, $admin);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Cancelled)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(505.0)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(505.0)
        ->and((float) $requester->wallet->fresh()->balance)->toBe(0.0)
        ->and((float) $counterparty->wallet->fresh()->balance)->toBe(0.0)
        ->and($unrelated->fresh()->status)->toBe(GuarantorStatusEnum::InProgress);
});

test('existing single-guarantor cancel scenarios still reverse correctly — regression against AdminCancelWalletHoldTest scenarios', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeFoundationContext([
        'type' => GuarantorTypeEnum::Company,
        'status' => GuarantorStatusEnum::Accepted,
    ]);
    $first = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);
    GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 2,
        'amount' => 500,
    ]);

    completeDisputeFoundationPayment($counterparty, $first, 500);

    app(CancelGuarantorAction::class)->handle($request->fresh(), 'Admin cancelled', null, $admin);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Cancelled)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(0.0);
});

test('GuarantorStatusEnum no longer has a Refunded case', function () {
    $values = array_column(GuarantorStatusEnum::cases(), 'value');

    expect($values)->not->toContain('refunded')
        ->and(array_map(fn (GuarantorStatusEnum $case) => $case->name, GuarantorStatusEnum::cases()))
        ->not->toContain('Refunded');
});

test('GuarantorStatusEnum has a new Disputed case and a new Escalated terminal case', function () {
    expect(GuarantorStatusEnum::Disputed->value)->toBe('disputed')
        ->and(GuarantorStatusEnum::Escalated->value)->toBe('escalated')
        ->and(GuarantorStatusEnum::Disputed->isTerminal())->toBeFalse()
        ->and(GuarantorStatusEnum::Escalated->isTerminal())->toBeTrue();
});

test('either party (requester or counterparty) can open a dispute from in_progress or overdue, with a mandatory reason', function () {
    foreach ([GuarantorStatusEnum::InProgress, GuarantorStatusEnum::Overdue] as $status) {
        foreach (['requester', 'counterparty'] as $role) {
            ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = disputeFoundationContext([
                'status' => $status,
            ]);
            $actor = $role === 'requester' ? $requester : $counterparty;

            $updated = app(OpenGuarantorDisputeAction::class)->handle(
                $request->fresh(),
                $actor,
                $role,
                'Work not delivered as agreed',
            );

            expect($updated->status)->toBe(GuarantorStatusEnum::Disputed);
        }
    }
});

test('opening a dispute is rejected from accepted status (no money moved yet)', function () {
    ['requester' => $requester, 'request' => $request] = disputeFoundationContext([
        'status' => GuarantorStatusEnum::Accepted,
    ]);

    expect(fn () => app(OpenGuarantorDisputeAction::class)->handle(
        $request->fresh(),
        $requester,
        'requester',
        'Too early',
    ))->toThrow(GuarantorException::class);
});

test('opening a dispute is rejected from any terminal status', function () {
    foreach ([
        GuarantorStatusEnum::Ended,
        GuarantorStatusEnum::Cancelled,
        GuarantorStatusEnum::Escalated,
        GuarantorStatusEnum::Rejected,
        GuarantorStatusEnum::RejectedByAdmin,
    ] as $status) {
        ['requester' => $requester, 'request' => $request] = disputeFoundationContext([
            'status' => $status,
        ]);

        expect(fn () => app(OpenGuarantorDisputeAction::class)->handle(
            $request->fresh(),
            $requester,
            'requester',
            'Too late',
        ))->toThrow(GuarantorException::class);
    }
});

test('a disputed guarantor cannot be Ended by either party', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = disputeFoundationContext([
        'status' => GuarantorStatusEnum::Disputed,
    ]);

    expect(Gate::forUser($requester)->denies('end', $request))->toBeTrue()
        ->and(Gate::forUser($counterparty)->denies('end', $request))->toBeTrue();

    expect(fn () => app(EndGuarantorAction::class)->handle($request->fresh(), $requester, 'requester'))
        ->toThrow(GuarantorException::class);
});

test('a disputed guarantor cannot accept further installment payments', function () {
    ['counterparty' => $counterparty, 'request' => $request] = disputeFoundationContext([
        'type' => GuarantorTypeEnum::Company,
        'status' => GuarantorStatusEnum::Disputed,
    ]);
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
        'status' => InstallmentStatusEnum::Pending,
    ]);

    expect(Gate::forUser($counterparty)->denies('pay', $installment))->toBeTrue();

    expect(fn () => app(PayInstallmentAction::class)->handle($request->fresh(), $installment, $counterparty))
        ->toThrow(GuarantorException::class);
});

test('a disputed guarantor CAN still be cancelled by Admin (escape hatch)', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeFoundationContext([
        'status' => GuarantorStatusEnum::Accepted,
    ]);
    completeDisputeFoundationPayment($counterparty, $request, 1010);

    app(OpenGuarantorDisputeAction::class)->handle(
        $request->fresh(),
        $requester,
        'requester',
        'Dispute reason',
    );

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Disputed);

    app(CancelGuarantorAction::class)->handle($request->fresh(), 'Admin escape hatch', null, $admin);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Cancelled)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(0.0);
});

test('chat remains accessible (open() policy + handler list) while a guarantor is disputed', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = disputeFoundationContext([
        'status' => GuarantorStatusEnum::Disputed,
    ]);

    expect(Gate::forUser($requester)->allows('chat', $request))->toBeTrue()
        ->and(Gate::forUser($counterparty)->allows('chat', $request))->toBeTrue();

    $conversation = app(OpenGuarantorChatAction::class)->handle($request->fresh(), $requester);

    expect($conversation)->not->toBeNull();

    $listed = (new GuarantorChatHandler)->listQuery($requester)->pluck('operation_id');
    expect($listed)->toContain($request->id);
});

test('opening a dispute logs a status history entry with the mandatory reason', function () {
    ['requester' => $requester, 'request' => $request] = disputeFoundationContext();

    app(OpenGuarantorDisputeAction::class)->handle(
        $request->fresh(),
        $requester,
        'requester',
        'Mandatory dispute reason',
    );

    $history = GuarantorStatusHistory::query()
        ->where('guarantor_request_id', $request->id)
        ->where('to_status', GuarantorStatusEnum::Disputed->value)
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->from_status)->toBe(GuarantorStatusEnum::InProgress->value)
        ->and($history->reason)->toBe('Mandatory dispute reason');
});

test('opening a dispute notifies the other party and Admin', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeFoundationContext();

    app(OpenGuarantorDisputeAction::class)->handle(
        $request->fresh(),
        $requester,
        'requester',
        'Notify both sides',
    );

    Notification::assertSentTo($counterparty, GuarantorDisputedNotification::class);
    Notification::assertSentTo($admin, GuarantorDisputedNotification::class);
    Notification::assertNotSentTo($requester, GuarantorDisputedNotification::class);
});

test('POST /guarantor/{id}/dispute opens a dispute for the authenticated party', function () {
    ['requester' => $requester, 'request' => $request] = disputeFoundationContext();

    Sanctum::actingAs($requester);

    $this->postJson(route('api.v1.guarantor.guarantor.dispute', $request), [
        'reason' => 'API dispute reason',
    ])->assertSuccessful();

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Disputed);
});

test('POST /guarantor/{id}/dispute requires a reason', function () {
    ['requester' => $requester, 'request' => $request] = disputeFoundationContext();

    Sanctum::actingAs($requester);

    $this->postJson(route('api.v1.guarantor.guarantor.dispute', $request), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['reason']);
});

test('admin isAllowed can transition Disputed to Ended, Cancelled, Escalated, or Settled', function () {
    expect(GuarantorStatusEnum::isAllowed(
        GuarantorStatusEnum::Disputed,
        GuarantorStatusEnum::Ended,
        'admin',
    ))->toBeTrue()
        ->and(GuarantorStatusEnum::isAllowed(
            GuarantorStatusEnum::Disputed,
            GuarantorStatusEnum::Cancelled,
            'admin',
        ))->toBeTrue()
        ->and(GuarantorStatusEnum::isAllowed(
            GuarantorStatusEnum::Disputed,
            GuarantorStatusEnum::Escalated,
            'admin',
        ))->toBeTrue()
        ->and(GuarantorStatusEnum::isAllowed(
            GuarantorStatusEnum::Disputed,
            GuarantorStatusEnum::Settled,
            'admin',
        ))->toBeTrue();
});

test('company cancel with released prior installment only reverses remaining held credit for THIS request, leaving released balance intact', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeFoundationContext([
        'type' => GuarantorTypeEnum::Company,
        'status' => GuarantorStatusEnum::Accepted,
        'amount' => 1000,
        'fees' => 10,
    ]);
    $first = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $second = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 2,
        'amount' => 500,
    ]);

    completeDisputeFoundationPayment($counterparty, $first, 500);
    completeDisputeFoundationPayment($counterparty, $second, 500);

    expect($first->fresh()->status)->toBe(InstallmentStatusEnum::Released)
        ->and((float) $requester->wallet->fresh()->balance)->toBe(495.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(500.0)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(1000.0);

    app(CancelGuarantorAction::class)->handle($request->fresh(), 'Cancel after release', null, $admin);

    expect((float) $requester->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $requester->wallet->fresh()->balance)->toBe(495.0);
});
