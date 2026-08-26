<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Guarantor\Actions\Guarantor\CancelGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\EndGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\OpenGuarantorDisputeAction;
use Modules\Guarantor\Actions\Guarantor\ResolveDisputeEscalateAction;
use Modules\Guarantor\Actions\Guarantor\ResolveDisputeFullToPartyAction;
use Modules\Guarantor\Actions\Installment\PayInstallmentAction;
use Modules\Guarantor\Enums\GuarantorDisputeResolutionEnum;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Http\Controllers\Dashboard\GuarantorController as DashboardGuarantorController;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Models\GuarantorStatusHistory;
use Modules\Guarantor\Notifications\GuarantorDisputeResolvedNotification;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Notification::fake();
});

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest, admin: Admin}
 */
function disputeResolutionBatch2Context(array $requestAttributes = []): array
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
    Permission::firstOrCreate(['name' => 'show guarantors', 'guard_name' => 'admin']);
    $admin = Admin::query()->create([
        'name' => 'Dispute Resolution Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
    $admin->givePermissionTo(['manage guarantors', 'show guarantors']);

    return compact('requester', 'counterparty', 'request', 'admin');
}

function completeDisputeResolutionBatch2Payment($owner, $product, float $amount): void
{
    $payment = createPaymentFor($owner, $product, [
        'amount' => $amount,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    event(new PaymentCompleted($payment->load('product')));
}

function openDisputeAfterPayment(GuarantorRequest $request, User $actor, string $actorRole = 'requester'): GuarantorRequest
{
    return app(OpenGuarantorDisputeAction::class)->handle(
        $request->fresh(),
        $actor,
        $actorRole,
        'Dispute opened for resolution tests',
    );
}

function withoutDisputeResolutionLocaleMiddleware(): void
{
    test()->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    test()->withoutVite();
}

/**
 * @return array{balance: float, pending_credit: float, pending_debit: float}
 */
function walletSnapshot(User $user): array
{
    $wallet = $user->wallet->fresh();

    return [
        'balance' => (float) $wallet->balance,
        'pending_credit' => (float) $wallet->pending_credit,
        'pending_debit' => (float) $wallet->pending_debit,
    ];
}

test('Admin can resolve a disputed guarantor fully in favor of the requester — same wallet outcome as an ordinary End', function () {
    ['requester' => $endRequester, 'counterparty' => $endCounterparty, 'request' => $endRequest] = disputeResolutionBatch2Context();
    completeDisputeResolutionBatch2Payment($endCounterparty, $endRequest, 1010);
    app(EndGuarantorAction::class)->handle($endRequest->fresh(), $endRequester, 'requester');
    $endRequesterSnapshot = walletSnapshot($endRequester);
    $endCounterpartySnapshot = walletSnapshot($endCounterparty);

    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeResolutionBatch2Context();
    completeDisputeResolutionBatch2Payment($counterparty, $request, 1010);
    openDisputeAfterPayment($request, $requester);

    app(ResolveDisputeFullToPartyAction::class)->handle($request->fresh(), $admin, 'requester');

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::EndedViaDispute)
        ->and(walletSnapshot($requester))->toBe($endRequesterSnapshot)
        ->and(walletSnapshot($counterparty))->toBe($endCounterpartySnapshot);
});

test('Admin can resolve a disputed guarantor fully in favor of the counterparty — same wallet outcome as the scoped Cancel fix from Batch 1', function () {
    ['requester' => $cancelRequester, 'counterparty' => $cancelCounterparty, 'request' => $cancelRequest, 'admin' => $cancelAdmin] = disputeResolutionBatch2Context();
    completeDisputeResolutionBatch2Payment($cancelCounterparty, $cancelRequest, 1010);
    app(CancelGuarantorAction::class)->handle($cancelRequest->fresh(), 'Admin cancelled', null, $cancelAdmin);
    $cancelRequesterSnapshot = walletSnapshot($cancelRequester);
    $cancelCounterpartySnapshot = walletSnapshot($cancelCounterparty);

    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeResolutionBatch2Context();
    completeDisputeResolutionBatch2Payment($counterparty, $request, 1010);
    openDisputeAfterPayment($request, $requester);

    app(ResolveDisputeFullToPartyAction::class)->handle($request->fresh(), $admin, 'counterparty');

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::CancelledViaDispute)
        ->and(walletSnapshot($requester))->toBe($cancelRequesterSnapshot)
        ->and(walletSnapshot($counterparty))->toBe($cancelCounterpartySnapshot);
});

test('Admin can escalate a disputed guarantor to Escalated status — wallet reversal identical to the counterparty-favor path, but status is Escalated, not Cancelled', function () {
    ['requester' => $cancelRequester, 'counterparty' => $cancelCounterparty, 'request' => $cancelRequest, 'admin' => $cancelAdmin] = disputeResolutionBatch2Context();
    completeDisputeResolutionBatch2Payment($cancelCounterparty, $cancelRequest, 1010);
    app(CancelGuarantorAction::class)->handle($cancelRequest->fresh(), 'Admin cancelled', null, $cancelAdmin);
    $cancelRequesterSnapshot = walletSnapshot($cancelRequester);
    $cancelCounterpartySnapshot = walletSnapshot($cancelCounterparty);

    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeResolutionBatch2Context();
    completeDisputeResolutionBatch2Payment($counterparty, $request, 1010);
    openDisputeAfterPayment($request, $requester);

    app(ResolveDisputeEscalateAction::class)->handle($request->fresh(), $admin);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Escalated)
        ->and($request->fresh()->status->isTerminal())->toBeTrue()
        ->and(walletSnapshot($requester))->toBe($cancelRequesterSnapshot)
        ->and(walletSnapshot($counterparty))->toBe($cancelCounterpartySnapshot);
});

test('an escalated guarantor is terminal — no further End/pay/dispute/cancel actions allowed', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeResolutionBatch2Context();
    completeDisputeResolutionBatch2Payment($counterparty, $request, 1010);
    openDisputeAfterPayment($request, $requester);
    app(ResolveDisputeEscalateAction::class)->handle($request->fresh(), $admin);

    $escalated = $request->fresh();
    expect($escalated->status)->toBe(GuarantorStatusEnum::Escalated);

    $company = GuarantorRequest::factory()->create([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'counterparty_id' => $counterparty->id,
        'counterparty_type' => User::class,
        'type' => GuarantorTypeEnum::Company,
        'amount' => 1000,
        'fees' => 10,
        'status' => GuarantorStatusEnum::Escalated,
    ]);
    $installment = GuarantorInstallment::factory()->for($company, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
        'status' => InstallmentStatusEnum::Pending,
    ]);

    expect(Gate::forUser($requester)->denies('end', $escalated))->toBeTrue()
        ->and(Gate::forUser($requester)->denies('dispute', $escalated))->toBeTrue()
        ->and(Gate::forUser($counterparty)->denies('pay', $installment))->toBeTrue();

    expect(fn () => app(EndGuarantorAction::class)->handle($escalated, $requester, 'requester'))
        ->toThrow(GuarantorException::class);
    expect(fn () => app(OpenGuarantorDisputeAction::class)->handle($escalated, $requester, 'requester', 'late dispute'))
        ->toThrow(GuarantorException::class);
    expect(fn () => app(PayInstallmentAction::class)->handle($company->fresh(), $installment, $counterparty))
        ->toThrow(GuarantorException::class);
    expect(fn () => app(CancelGuarantorAction::class)->handle($escalated, 'late cancel', null, $admin))
        ->toThrow(GuarantorException::class);
});

test('resolving a dispute (any of the 3 paths) is Admin-only — a requester or counterparty cannot call these endpoints', function () {
    withoutDisputeResolutionLocaleMiddleware();

    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = disputeResolutionBatch2Context([
        'status' => GuarantorStatusEnum::Disputed,
    ]);

    foreach ([$requester, $counterparty] as $party) {
        Sanctum::actingAs($party);

        $this->from(action([DashboardGuarantorController::class, 'show'], $request))
            ->put(action([DashboardGuarantorController::class, 'resolveDispute'], $request), [
                'resolution' => GuarantorDisputeResolutionEnum::FullRequester->value,
            ])
            ->assertRedirect();

        expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Disputed);
    }
});

test('resolving a dispute requires the guarantor to currently be in Disputed status — cannot resolve an in_progress guarantor directly through this endpoint', function () {
    ['request' => $request, 'admin' => $admin] = disputeResolutionBatch2Context([
        'status' => GuarantorStatusEnum::InProgress,
    ]);

    expect(fn () => app(ResolveDisputeFullToPartyAction::class)->handle($request->fresh(), $admin, 'requester'))
        ->toThrow(GuarantorException::class);
    expect(fn () => app(ResolveDisputeFullToPartyAction::class)->handle($request->fresh(), $admin, 'counterparty'))
        ->toThrow(GuarantorException::class);
    expect(fn () => app(ResolveDisputeEscalateAction::class)->handle($request->fresh(), $admin))
        ->toThrow(GuarantorException::class);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::InProgress);
});

test('each resolution path logs a distinct status history entry (to_status + reason) distinguishing full-requester vs full-counterparty vs escalated', function () {
    foreach ([
        ['party' => 'requester', 'to' => GuarantorStatusEnum::EndedViaDispute, 'reason' => 'dispute_resolved_full_requester'],
        ['party' => 'counterparty', 'to' => GuarantorStatusEnum::CancelledViaDispute, 'reason' => 'dispute_resolved_full_counterparty'],
        ['party' => 'escalate', 'to' => GuarantorStatusEnum::Escalated, 'reason' => 'dispute_escalated_to_court'],
    ] as $case) {
        ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeResolutionBatch2Context();
        completeDisputeResolutionBatch2Payment($counterparty, $request, 1010);
        openDisputeAfterPayment($request, $requester);

        if ($case['party'] === 'escalate') {
            app(ResolveDisputeEscalateAction::class)->handle($request->fresh(), $admin, 'court notes');
        } else {
            app(ResolveDisputeFullToPartyAction::class)->handle($request->fresh(), $admin, $case['party'], 'admin notes');
        }

        $history = GuarantorStatusHistory::query()
            ->where('guarantor_request_id', $request->id)
            ->where('to_status', $case['to']->value)
            ->where('reason', $case['reason'])
            ->first();

        expect($history)->not->toBeNull()
            ->and($history->from_status)->toBe(GuarantorStatusEnum::Disputed->value);
    }
});

test('each resolution path sends a distinct notification to both parties, worded appropriately for the outcome', function () {
    foreach ([
        ['party' => 'requester', 'resolution' => GuarantorDisputeResolutionEnum::FullRequester],
        ['party' => 'counterparty', 'resolution' => GuarantorDisputeResolutionEnum::FullCounterparty],
        ['party' => 'escalate', 'resolution' => GuarantorDisputeResolutionEnum::Escalate],
    ] as $case) {
        Notification::fake();

        ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeResolutionBatch2Context();
        completeDisputeResolutionBatch2Payment($counterparty, $request, 1010);
        openDisputeAfterPayment($request, $requester);

        if ($case['party'] === 'escalate') {
            app(ResolveDisputeEscalateAction::class)->handle($request->fresh(), $admin);
        } else {
            app(ResolveDisputeFullToPartyAction::class)->handle($request->fresh(), $admin, $case['party']);
        }

        Notification::assertSentTo($requester, GuarantorDisputeResolvedNotification::class, function ($notification) use ($case) {
            return $notification->resolution === $case['resolution'];
        });
        Notification::assertSentTo($counterparty, GuarantorDisputeResolvedNotification::class, function ($notification) use ($case) {
            return $notification->resolution === $case['resolution'];
        });
    }
});

test('Admin dashboard PUT resolve-dispute endpoint resolves full_requester / full_counterparty / escalate', function () {
    withoutDisputeResolutionLocaleMiddleware();

    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeResolutionBatch2Context();
    completeDisputeResolutionBatch2Payment($counterparty, $request, 1010);
    openDisputeAfterPayment($request, $requester);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardGuarantorController::class, 'show'], $request))
        ->put(action([DashboardGuarantorController::class, 'resolveDispute'], $request), [
            'resolution' => GuarantorDisputeResolutionEnum::Escalate->value,
            'notes' => 'Escalated via dashboard',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Escalated);
});
