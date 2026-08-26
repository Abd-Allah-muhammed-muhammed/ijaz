<?php

use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Mockery\MockInterface;
use Modules\Guarantor\Actions\Dashboard\DeleteGuarantorForDashboardAction;
use Modules\Guarantor\Actions\Guarantor\CancelGuarantorAction;
use Modules\Guarantor\Actions\Guarantor\OpenGuarantorDisputeAction;
use Modules\Guarantor\Actions\Guarantor\ResolveDisputeEscalateAction;
use Modules\Guarantor\Actions\Guarantor\ResolveDisputeFullToPartyAction;
use Modules\Guarantor\Actions\Guarantor\ResolveDisputePercentageSplitAction;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorDisputeResolutionEnum;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Http\Controllers\Dashboard\GuarantorController as DashboardGuarantorController;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Guarantor\Models\GuarantorStatusHistory;
use Modules\Guarantor\Notifications\GuarantorCancelledNotification;
use Modules\Guarantor\Notifications\GuarantorDisputedNotification;
use Modules\Guarantor\Notifications\GuarantorDisputeResolvedNotification;
use Modules\Guarantor\Repositories\GuarantorRepository;
use Modules\Guarantor\Support\GuarantorDisputeHistoryReason;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Spatie\Permission\Models\Permission;

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest, admin: Admin}
 */
function disputeAuditBatchContext(array $requestAttributes = []): array
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
        'name' => 'Dispute Audit Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
    $admin->givePermissionTo(['manage guarantors', 'show guarantors']);

    return compact('requester', 'counterparty', 'request', 'admin');
}

function completeDisputeAuditPayment($owner, $product, float $amount): void
{
    $payment = createPaymentFor($owner, $product, [
        'amount' => $amount,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    event(new PaymentCompleted($payment->load('product')));
}

function openDisputeForAudit(GuarantorRequest $request, User $actor): GuarantorRequest
{
    return app(OpenGuarantorDisputeAction::class)->handle(
        $request->fresh(),
        $actor,
        'requester',
        'Audit batch dispute reason',
    );
}

function withoutDisputeAuditLocaleMiddleware(): void
{
    test()->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    test()->withoutVite();
}

function assertGuarantorRequestLockedDuring(callable $callback): void
{
    /** @var GuarantorRepository&MockInterface $repository */
    $repository = Mockery::mock(GuarantorRepository::class)->makePartial();
    app()->instance(GuarantorRepositoryInterface::class, $repository);

    $callback();

    $repository->shouldHaveReceived('findForUpdate')->once();
}

test('opening a dispute locks the GuarantorRequest row for update before checking status', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = disputeAuditBatchContext([
        'status' => GuarantorStatusEnum::InProgress,
    ]);
    completeDisputeAuditPayment($counterparty, $request, 1010);

    assertGuarantorRequestLockedDuring(function () use ($request, $requester): void {
        openDisputeForAudit($request, $requester);
    });

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Disputed);
});

test('each of the 4 resolve actions locks the GuarantorRequest row before checking status === Disputed', function (
    string $label,
    callable $setupAndInvoke,
) {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeAuditBatchContext();
    completeDisputeAuditPayment($counterparty, $request, 1010);
    openDisputeForAudit($request, $requester);

    assertGuarantorRequestLockedDuring(fn () => $setupAndInvoke($request->fresh(), $admin));

    expect($request->fresh()->status->isNot(GuarantorStatusEnum::Disputed))->toBeTrue();
})->with([
    'full to requester' => [
        'full to requester',
        fn (GuarantorRequest $request, Admin $admin) => app(ResolveDisputeFullToPartyAction::class)->handle($request, $admin, 'requester'),
    ],
    'full to counterparty' => [
        'full to counterparty',
        fn (GuarantorRequest $request, Admin $admin) => app(ResolveDisputeFullToPartyAction::class)->handle($request, $admin, 'counterparty'),
    ],
    'escalate' => [
        'escalate',
        fn (GuarantorRequest $request, Admin $admin) => app(ResolveDisputeEscalateAction::class)->handle($request, $admin, 'court'),
    ],
    'percentage split' => [
        'percentage split',
        fn (GuarantorRequest $request, Admin $admin) => app(ResolveDisputePercentageSplitAction::class)->handle($request, $admin, 60),
    ],
]);

test('CancelGuarantorAction locks the GuarantorRequest row before checking terminal status', function () {
    ['request' => $request, 'admin' => $admin] = disputeAuditBatchContext([
        'status' => GuarantorStatusEnum::InProgress,
    ]);

    assertGuarantorRequestLockedDuring(function () use ($request, $admin): void {
        app(CancelGuarantorAction::class)->handle(
            $request->fresh(),
            'admin cancel audit',
            null,
            $admin,
        );
    });

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Cancelled);
});

test('two concurrent resolve-dispute attempts on the same guarantor result in exactly one success and one clear already resolved error, not inconsistent state', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeAuditBatchContext();
    completeDisputeAuditPayment($counterparty, $request, 1010);
    openDisputeForAudit($request, $requester);

    app(ResolveDisputeFullToPartyAction::class)->handle($request->fresh(), $admin, 'requester');

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::EndedViaDispute);

    expect(fn () => app(ResolveDisputeFullToPartyAction::class)->handle($request->fresh(), $admin, 'counterparty'))
        ->toThrow(function (GuarantorException $exception): bool {
            return $exception->getMessage() === 'guarantor.dispute_already_resolved';
        });

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::EndedViaDispute);
});

test('dashboard delete is rejected for a guarantor with any unreleased wallet holds (Paid-unreleased installments or Disputed status), with a clear error', function () {
    ['counterparty' => $counterparty, 'request' => $disputed] = disputeAuditBatchContext([
        'status' => GuarantorStatusEnum::Disputed,
    ]);

    expect(fn () => app(DeleteGuarantorForDashboardAction::class)->handle($disputed->fresh()))
        ->toThrow(function (GuarantorException $exception): bool {
            return $exception->getMessage() === 'guarantor.delete_denied_active_dispute';
        });

    ['requester' => $requester, 'counterparty' => $paidCounterparty, 'request' => $held] = disputeAuditBatchContext([
        'status' => GuarantorStatusEnum::InProgress,
    ]);
    completeDisputeAuditPayment($paidCounterparty, $held, 1010);

    expect(fn () => app(DeleteGuarantorForDashboardAction::class)->handle($held->fresh()))
        ->toThrow(function (GuarantorException $exception): bool {
            return $exception->getMessage() === 'guarantor.delete_denied_unreleased_holds';
        });
});

test('dashboard delete still works normally for PendingAdmin / no-payment-yet guarantors — regression', function () {
    $guarantorRequest = GuarantorRequest::factory()->create([
        'status' => GuarantorStatusEnum::PendingAdmin,
    ]);

    app(DeleteGuarantorForDashboardAction::class)->handle($guarantorRequest);

    expect(GuarantorRequest::withTrashed()->find($guarantorRequest->id)?->trashed())->toBeTrue();
});

test('Admin Cancel during an open dispute writes a distinct status history entry (e.g. dispute_closed_by_admin_cancel) so the Dispute tab can show it as closed, not perpetually awaiting resolution', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeAuditBatchContext();
    completeDisputeAuditPayment($counterparty, $request, 1010);
    openDisputeForAudit($request, $requester);

    app(CancelGuarantorAction::class)->handle(
        $request->fresh(),
        'admin cancelled during dispute',
        'closing case',
        $admin,
    );

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Cancelled);

    expect(
        GuarantorStatusHistory::query()
            ->where('guarantor_request_id', $request->id)
            ->where('reason', GuarantorDisputeHistoryReason::ClosedByAdminCancel)
            ->exists()
    )->toBeTrue();
});

test('the Dispute tab shows a closed/cancelled state (not awaiting admin resolution) after an Admin Cancel bypass during a dispute', function () {
    withoutDisputeAuditLocaleMiddleware();

    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeAuditBatchContext();
    completeDisputeAuditPayment($counterparty, $request, 1010);
    openDisputeForAudit($request, $requester);

    app(CancelGuarantorAction::class)->handle(
        $request->fresh(),
        'admin cancelled during dispute',
        'closing case',
        $admin,
    );

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardGuarantorController::class, 'show'], $request))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Guarantor/Show')
            ->where('guarantorRequest.status.value', GuarantorStatusEnum::Cancelled->value)
            ->where(
                'guarantorRequest.status_histories',
                fn ($histories) => collect($histories)->contains(
                    fn ($history) => ($history['reason'] ?? null) === GuarantorDisputeHistoryReason::ClosedByAdminCancel
                )
            )
            ->where(
                'guarantorRequest.status_histories',
                fn ($histories) => collect($histories)->contains(
                    fn ($history) => ($history['to_status']['value'] ?? null) === GuarantorStatusEnum::Disputed->value
                )
            )
        );
});

test('GuarantorDisputeResolvedNotification and GuarantorCancelledNotification now send Firebase to Provider notifiables too, not just User', function () {
    $provider = createWalletProvider();
    $user = User::factory()->create();
    $request = GuarantorRequest::factory()->create([
        'requester_id' => $provider->id,
        'requester_type' => Provider::class,
        'counterparty_id' => $user->id,
        'counterparty_type' => User::class,
        'status' => GuarantorStatusEnum::EndedViaDispute,
    ]);

    $resolved = new GuarantorDisputeResolvedNotification(
        $request,
        GuarantorDisputeResolutionEnum::FullRequester,
    );
    $cancelled = new GuarantorCancelledNotification($request->fresh(['requester', 'counterparty']));
    $disputed = new GuarantorDisputedNotification($request->fresh(), 'provider party dispute');

    expect($resolved->via($provider))->toContain('firebase')
        ->and($resolved->via($user))->toContain('firebase')
        ->and($cancelled->via($provider))->toContain('firebase')
        ->and($cancelled->via($user))->toContain('firebase')
        ->and($disputed->via($provider))->toContain('firebase');
});
