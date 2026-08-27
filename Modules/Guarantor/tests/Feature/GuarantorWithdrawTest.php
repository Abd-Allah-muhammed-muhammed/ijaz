<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Actions\Guarantor\EndGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\OpenGuarantorDisputeAction;
use Modules\Guarantor\Actions\Guarantor\WithdrawGuarantorAction;
use Modules\Guarantor\Actions\Installment\PayInstallmentAction;
use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorWithdrawnNotificationAudience;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Handlers\GuarantorChatHandler;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Models\GuarantorStatusHistory;
use Modules\Guarantor\Notifications\GuarantorWithdrawnNotification;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Notification::fake();
});

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest, admin: Admin}
 */
function withdrawContext(array $requestAttributes = []): array
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
        'status' => GuarantorStatusEnum::ApprovedByAdmin,
    ], $requestAttributes));

    Permission::firstOrCreate(['name' => 'manage guarantors', 'guard_name' => 'admin']);
    $admin = Admin::query()->create([
        'name' => 'Withdraw Test Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
    $admin->givePermissionTo('manage guarantors');

    return compact('requester', 'counterparty', 'request', 'admin');
}

test('requester can withdraw from approved_by_admin with an optional reason', function () {
    ['requester' => $requester, 'request' => $request] = withdrawContext([
        'status' => GuarantorStatusEnum::ApprovedByAdmin,
    ]);

    $updated = app(WithdrawGuarantorAction::class)->handle(
        $request->fresh(),
        $requester,
        'requester',
        'Changed my mind',
    );

    expect($updated->status)->toBe(GuarantorStatusEnum::Withdrawn);
});

test('counterparty cannot withdraw from approved_by_admin — reject already covers this exact need for them at this stage', function () {
    ['counterparty' => $counterparty, 'request' => $request] = withdrawContext([
        'status' => GuarantorStatusEnum::ApprovedByAdmin,
    ]);

    expect(Gate::forUser($counterparty)->denies('withdraw', $request))->toBeTrue();

    expect(fn () => app(WithdrawGuarantorAction::class)->handle(
        $request->fresh(),
        $counterparty,
        'counterparty',
        null,
    ))->toThrow(GuarantorException::class);
});

test('either party can withdraw from accepted (pre-payment) with an optional reason', function () {
    foreach (['requester', 'counterparty'] as $role) {
        ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = withdrawContext([
            'status' => GuarantorStatusEnum::Accepted,
        ]);
        $actor = $role === 'requester' ? $requester : $counterparty;

        $updated = app(WithdrawGuarantorAction::class)->handle(
            $request->fresh(),
            $actor,
            $role,
            'No longer proceeding',
        );

        expect($updated->status)->toBe(GuarantorStatusEnum::Withdrawn);
    }
});

test('withdraw is rejected from pending_admin — delete remains the only pre-admin-review exit', function () {
    ['requester' => $requester, 'request' => $request] = withdrawContext([
        'status' => GuarantorStatusEnum::PendingAdmin,
    ]);

    expect(Gate::forUser($requester)->denies('withdraw', $request))->toBeTrue();

    expect(fn () => app(WithdrawGuarantorAction::class)->handle(
        $request->fresh(),
        $requester,
        'requester',
        null,
    ))->toThrow(GuarantorException::class);
});

test('withdraw is rejected once in_progress/overdue — End/Dispute are the correct actions post-payment', function () {
    foreach ([GuarantorStatusEnum::InProgress, GuarantorStatusEnum::Overdue] as $status) {
        ['requester' => $requester, 'request' => $request] = withdrawContext([
            'status' => $status,
        ]);

        expect(Gate::forUser($requester)->denies('withdraw', $request))->toBeTrue();

        expect(fn () => app(WithdrawGuarantorAction::class)->handle(
            $request->fresh(),
            $requester,
            'requester',
            null,
        ))->toThrow(GuarantorException::class);
    }
});

test('withdrawing sets a new terminal Withdrawn status, distinct from Cancelled/Rejected', function () {
    ['requester' => $requester, 'request' => $request] = withdrawContext([
        'status' => GuarantorStatusEnum::ApprovedByAdmin,
    ]);

    app(WithdrawGuarantorAction::class)->handle($request->fresh(), $requester, 'requester', null);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Withdrawn)
        ->and(GuarantorStatusEnum::Withdrawn->value)->toBe('withdrawn')
        ->and(GuarantorStatusEnum::Withdrawn->isTerminal())->toBeTrue();
});

test('a Withdrawn guarantor is terminal — no further End/pay/dispute/withdraw actions allowed', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = withdrawContext([
        'status' => GuarantorStatusEnum::Withdrawn,
    ]);
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
        'status' => InstallmentStatusEnum::Pending,
    ]);

    expect(Gate::forUser($requester)->denies('end', $request))->toBeTrue()
        ->and(Gate::forUser($requester)->denies('dispute', $request))->toBeTrue()
        ->and(Gate::forUser($requester)->denies('withdraw', $request))->toBeTrue()
        ->and(Gate::forUser($counterparty)->denies('pay', $request))->toBeTrue()
        ->and(Gate::forUser($counterparty)->denies('pay', $installment))->toBeTrue();

    expect(fn () => app(EndGuarantorAction::class)->handle($request->fresh(), $requester, 'requester'))
        ->toThrow(GuarantorException::class);
    expect(fn () => app(OpenGuarantorDisputeAction::class)->handle($request->fresh(), $requester, 'requester', 'Too late'))
        ->toThrow(GuarantorException::class);
    expect(fn () => app(WithdrawGuarantorAction::class)->handle($request->fresh(), $requester, 'requester', null))
        ->toThrow(GuarantorException::class);
    expect(fn () => app(PayInstallmentAction::class)->handle($request->fresh(), $installment, $counterparty))
        ->toThrow(GuarantorException::class);
});

test('withdraw without a reason succeeds — reason is optional, unlike dispute', function () {
    ['requester' => $requester, 'request' => $request] = withdrawContext([
        'status' => GuarantorStatusEnum::Accepted,
    ]);

    app(WithdrawGuarantorAction::class)->handle($request->fresh(), $requester, 'requester', null);

    $history = GuarantorStatusHistory::query()
        ->where('guarantor_request_id', $request->id)
        ->where('to_status', GuarantorStatusEnum::Withdrawn->value)
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->getRawOriginal('reason'))->toBeNull();
});

test('withdrawing logs a status history entry with the correct actor (requester or counterparty) and optional reason — this is how mobile/dashboard determine who withdrew', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = withdrawContext([
        'status' => GuarantorStatusEnum::Accepted,
    ]);

    app(WithdrawGuarantorAction::class)->handle(
        $request->fresh(),
        $counterparty,
        'counterparty',
        'Counterparty reason',
    );

    $history = GuarantorStatusHistory::query()
        ->where('guarantor_request_id', $request->id)
        ->where('to_status', GuarantorStatusEnum::Withdrawn->value)
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->from_status)->toBe(GuarantorStatusEnum::Accepted->value)
        ->and($history->actor_type)->toBe(User::class)
        ->and((string) $history->actor_id)->toBe((string) $counterparty->id)
        ->and($history->getRawOriginal('reason'))->toBe('Counterparty reason');
});

test('all three parties are notified when someone withdraws: the withdrawer (confirmation), the other party (informational), and Admins (visibility) — three distinct notification calls/recipients, not one broadcast', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = withdrawContext([
        'status' => GuarantorStatusEnum::Accepted,
    ]);

    app(WithdrawGuarantorAction::class)->handle(
        $request->fresh(),
        $requester,
        'requester',
        'Party withdrew',
    );

    Notification::assertSentTo(
        $requester,
        GuarantorWithdrawnNotification::class,
        fn (GuarantorWithdrawnNotification $notification): bool => $notification->audience === GuarantorWithdrawnNotificationAudience::Withdrawer,
    );
    Notification::assertSentTo(
        $counterparty,
        GuarantorWithdrawnNotification::class,
        fn (GuarantorWithdrawnNotification $notification): bool => $notification->audience === GuarantorWithdrawnNotificationAudience::OtherParty,
    );
    Notification::assertSentTo(
        $admin,
        GuarantorWithdrawnNotification::class,
        fn (GuarantorWithdrawnNotification $notification): bool => $notification->audience === GuarantorWithdrawnNotificationAudience::Admin,
    );
});

test('GuarantorStatusEnum handles the new Withdrawn case in every exhaustive match (color, label) without an UnhandledMatchError', function () {
    $status = GuarantorStatusEnum::Withdrawn;

    expect($status->color())->toBeString()->not->toBe('')
        ->and($status->toString())->toBeString()->not->toBe('')
        ->and($status->toArray())->toMatchArray([
            'value' => $status->value,
            'label' => $status->toString(),
            'color' => $status->color(),
        ]);
});

test('scopeActive, chat eligibility, and overdue queries correctly treat Withdrawn as terminal', function () {
    $active = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::InProgress]);
    $withdrawn = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::Withdrawn]);

    expect(GuarantorRequest::query()->active()->pluck('id')->all())
        ->toContain($active->id)
        ->not->toContain($withdrawn->id);

    $overdueInstallment = GuarantorInstallment::factory()->for($active, 'guarantorRequest')->create([
        'status' => InstallmentStatusEnum::Pending,
        'due_date' => now()->subDay(),
    ]);
    $terminalOverdueInstallment = GuarantorInstallment::factory()->for($withdrawn, 'guarantorRequest')->create([
        'status' => InstallmentStatusEnum::Pending,
        'due_date' => now()->subDay(),
    ]);

    $overdueIds = app(InstallmentRepositoryInterface::class)
        ->getOverdue()
        ->pluck('id')
        ->all();

    expect($overdueIds)->toContain($overdueInstallment->id)
        ->not->toContain($terminalOverdueInstallment->id);

    ['requester' => $requester, 'request' => $request] = withdrawContext([
        'status' => GuarantorStatusEnum::Withdrawn,
    ]);

    expect(Gate::forUser($requester)->denies('chat', $request))->toBeTrue();

    $listed = (new GuarantorChatHandler)->listQuery($requester)->pluck('operation_id');
    expect($listed)->not->toContain($request->id);
});

test('POST /guarantor/{id}/withdraw withdraws for the authenticated party', function () {
    ['requester' => $requester, 'request' => $request] = withdrawContext([
        'status' => GuarantorStatusEnum::ApprovedByAdmin,
    ]);

    Sanctum::actingAs($requester);

    $this->postJson(route('api.v1.guarantor.guarantor.withdraw', $request), [
        'reason' => 'API withdraw reason',
    ])->assertSuccessful();

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Withdrawn);
});
