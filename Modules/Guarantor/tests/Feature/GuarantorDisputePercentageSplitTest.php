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
use Modules\Guarantor\Actions\Guarantor\ResolveDisputePercentageSplitAction;
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
function disputePercentageSplitContext(array $requestAttributes = []): array
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
        'name' => 'Percentage Split Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
    $admin->givePermissionTo(['manage guarantors', 'show guarantors']);

    return compact('requester', 'counterparty', 'request', 'admin');
}

function completeDisputePercentageSplitPayment($owner, $product, float $amount): void
{
    $payment = createPaymentFor($owner, $product, [
        'amount' => $amount,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    event(new PaymentCompleted($payment->load('product')));
}

function openDisputeForPercentageSplit(GuarantorRequest $request, User $actor): GuarantorRequest
{
    return app(OpenGuarantorDisputeAction::class)->handle(
        $request->fresh(),
        $actor,
        'requester',
        'Dispute for percentage split',
    );
}

function withoutPercentageSplitLocaleMiddleware(): void
{
    test()->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    test()->withoutVite();
}

test('a 60/40 percentage split releases 60% (net of proportional fee) to the requester and voids the remaining 40% of their held credit', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputePercentageSplitContext();
    completeDisputePercentageSplitPayment($counterparty, $request, 1010);
    openDisputeForPercentageSplit($request, $requester);

    expect((float) $requester->wallet->fresh()->pending_credit)->toBe(1010.0);

    app(ResolveDisputePercentageSplitAction::class)->handle($request->fresh(), $admin, 60);

    // requesterGross=606, feeShare=6, net=600; remainder voided=404
    expect((float) $requester->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $requester->wallet->fresh()->balance)->toBe(600.0);
});

test('a 60/40 percentage split credits 40% of the gross (no fee deducted) directly to the counterparty balance', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputePercentageSplitContext();
    completeDisputePercentageSplitPayment($counterparty, $request, 1010);
    openDisputeForPercentageSplit($request, $requester);

    app(ResolveDisputePercentageSplitAction::class)->handle($request->fresh(), $admin, 60);

    expect((float) $counterparty->wallet->fresh()->balance)->toBe(404.0);
});

test('a percentage split fully clears the counterparty pending_debit hold for this guarantor, regardless of the split percentage', function () {
    foreach ([0, 60, 100] as $requesterPercentage) {
        ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputePercentageSplitContext();
        completeDisputePercentageSplitPayment($counterparty, $request, 1010);
        openDisputeForPercentageSplit($request, $requester);

        expect((float) $counterparty->wallet->fresh()->pending_debit)->toBe(1010.0);

        app(ResolveDisputePercentageSplitAction::class)->handle($request->fresh(), $admin, $requesterPercentage);

        expect((float) $counterparty->wallet->fresh()->pending_debit)->toBe(0.0);
    }
});

test('a percentage split transitions the guarantor to a new terminal Settled status, not Ended/Cancelled/Escalated', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputePercentageSplitContext();
    completeDisputePercentageSplitPayment($counterparty, $request, 1010);
    openDisputeForPercentageSplit($request, $requester);

    app(ResolveDisputePercentageSplitAction::class)->handle($request->fresh(), $admin, 60);

    $settled = $request->fresh();
    expect($settled->status)->toBe(GuarantorStatusEnum::Settled)
        ->and($settled->status->isTerminal())->toBeTrue()
        ->and($settled->status)->not->toBe(GuarantorStatusEnum::Ended)
        ->and($settled->status)->not->toBe(GuarantorStatusEnum::Cancelled)
        ->and($settled->status)->not->toBe(GuarantorStatusEnum::Escalated);
});

test('a settled guarantor is terminal — no further actions allowed', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputePercentageSplitContext();
    completeDisputePercentageSplitPayment($counterparty, $request, 1010);
    openDisputeForPercentageSplit($request, $requester);
    app(ResolveDisputePercentageSplitAction::class)->handle($request->fresh(), $admin, 50);

    $settled = $request->fresh();

    $company = GuarantorRequest::factory()->create([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'counterparty_id' => $counterparty->id,
        'counterparty_type' => User::class,
        'type' => GuarantorTypeEnum::Company,
        'amount' => 1000,
        'fees' => 10,
        'status' => GuarantorStatusEnum::Settled,
    ]);
    $installment = GuarantorInstallment::factory()->for($company, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
        'status' => InstallmentStatusEnum::Pending,
    ]);

    expect(Gate::forUser($requester)->denies('end', $settled))->toBeTrue()
        ->and(Gate::forUser($requester)->denies('dispute', $settled))->toBeTrue()
        ->and(Gate::forUser($counterparty)->denies('pay', $installment))->toBeTrue();

    expect(fn () => app(EndGuarantorAction::class)->handle($settled, $requester, 'requester'))
        ->toThrow(GuarantorException::class);
    expect(fn () => app(OpenGuarantorDisputeAction::class)->handle($settled, $requester, 'requester', 'late'))
        ->toThrow(GuarantorException::class);
    expect(fn () => app(PayInstallmentAction::class)->handle($company->fresh(), $installment, $counterparty))
        ->toThrow(GuarantorException::class);
    expect(fn () => app(CancelGuarantorAction::class)->handle($settled, 'late cancel', null, $admin))
        ->toThrow(GuarantorException::class);
});

test('percentage split for a Company guarantor scopes correctly to only the current unreleased installment, not the full contract or already-released installments', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputePercentageSplitContext([
        'type' => GuarantorTypeEnum::Company,
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
        'status' => InstallmentStatusEnum::Pending,
    ]);

    // Only second installment is paid/held (first already released elsewhere).
    completeDisputePercentageSplitPayment($counterparty, $second, 500);
    $request->update(['status' => GuarantorStatusEnum::InProgress]);
    openDisputeForPercentageSplit($request->fresh(), $requester);

    expect((float) $requester->wallet->fresh()->pending_credit)->toBe(500.0)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(500.0);

    app(ResolveDisputePercentageSplitAction::class)->handle($request->fresh(), $admin, 60);

    // Installment fee portion = round(500/1000*10, 2) = 5
    // requesterGross=300, fee=3, net=297; counterparty=200
    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Settled)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $requester->wallet->fresh()->balance)->toBe(297.0)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $counterparty->wallet->fresh()->balance)->toBe(200.0)
        ->and($first->fresh()->status)->toBe(InstallmentStatusEnum::Released)
        ->and($second->fresh()->status)->toBe(InstallmentStatusEnum::Released);
});

test('percentage split requires the guarantor to currently be Disputed and is Admin-only, same guard pattern as Batch 2', function () {
    withoutPercentageSplitLocaleMiddleware();

    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $inProgress, 'admin' => $admin] = disputePercentageSplitContext([
        'status' => GuarantorStatusEnum::InProgress,
    ]);

    expect(fn () => app(ResolveDisputePercentageSplitAction::class)->handle($inProgress->fresh(), $admin, 50))
        ->toThrow(GuarantorException::class);

    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = disputePercentageSplitContext([
        'status' => GuarantorStatusEnum::Disputed,
    ]);

    Sanctum::actingAs($requester);
    $this->from(action([DashboardGuarantorController::class, 'show'], $request))
        ->put(action([DashboardGuarantorController::class, 'resolveDispute'], $request), [
            'resolution' => GuarantorDisputeResolutionEnum::PercentageSplit->value,
            'requester_percentage' => 50,
        ])
        ->assertRedirect();

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Disputed);
});

test('percentage split logs a distinct status history entry including both percentages', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputePercentageSplitContext();
    completeDisputePercentageSplitPayment($counterparty, $request, 1010);
    openDisputeForPercentageSplit($request, $requester);

    app(ResolveDisputePercentageSplitAction::class)->handle($request->fresh(), $admin, 60, 'split notes');

    $history = GuarantorStatusHistory::query()
        ->where('guarantor_request_id', $request->id)
        ->where('to_status', GuarantorStatusEnum::Settled->value)
        ->first();

    expect($history)->not->toBeNull()
        ->and($history->from_status)->toBe(GuarantorStatusEnum::Disputed->value)
        ->and($history->getRawOriginal('reason'))->toBe('dispute_resolved_percentage_split:60/40')
        ->and($history->notes)->toBe('split notes');
});

test('percentage split notifies both parties with outcome-specific copy stating their respective amounts', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputePercentageSplitContext();
    completeDisputePercentageSplitPayment($counterparty, $request, 1010);
    openDisputeForPercentageSplit($request, $requester);

    app(ResolveDisputePercentageSplitAction::class)->handle($request->fresh(), $admin, 60);

    Notification::assertSentTo($requester, GuarantorDisputeResolvedNotification::class, function ($notification) {
        return $notification->resolution === GuarantorDisputeResolutionEnum::PercentageSplit
            && $notification->requesterPercentage === 60
            && $notification->counterpartyPercentage === 40
            && $notification->requesterAmount === 600.0
            && $notification->counterpartyAmount === 404.0;
    });
    Notification::assertSentTo($counterparty, GuarantorDisputeResolvedNotification::class, function ($notification) {
        return $notification->resolution === GuarantorDisputeResolutionEnum::PercentageSplit
            && $notification->requesterAmount === 600.0
            && $notification->counterpartyAmount === 404.0;
    });
});

test('requesterPercentage must be a valid 0-100 value — invalid input is rejected before any wallet mutation', function () {
    withoutPercentageSplitLocaleMiddleware();

    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputePercentageSplitContext();
    completeDisputePercentageSplitPayment($counterparty, $request, 1010);
    openDisputeForPercentageSplit($request, $requester);

    $pendingBefore = (float) $requester->wallet->fresh()->pending_credit;

    expect(fn () => app(ResolveDisputePercentageSplitAction::class)->handle($request->fresh(), $admin, -1))
        ->toThrow(GuarantorException::class);
    expect(fn () => app(ResolveDisputePercentageSplitAction::class)->handle($request->fresh(), $admin, 101))
        ->toThrow(GuarantorException::class);

    expect((float) $requester->wallet->fresh()->pending_credit)->toBe($pendingBefore)
        ->and($request->fresh()->status)->toBe(GuarantorStatusEnum::Disputed);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardGuarantorController::class, 'show'], $request))
        ->put(action([DashboardGuarantorController::class, 'resolveDispute'], $request), [
            'resolution' => GuarantorDisputeResolutionEnum::PercentageSplit->value,
            'requester_percentage' => 150,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('requester_percentage');

    expect((float) $requester->wallet->fresh()->pending_credit)->toBe($pendingBefore)
        ->and($request->fresh()->status)->toBe(GuarantorStatusEnum::Disputed);
});

test('Admin dashboard percentage_split resolution settles a disputed guarantor', function () {
    withoutPercentageSplitLocaleMiddleware();

    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputePercentageSplitContext();
    completeDisputePercentageSplitPayment($counterparty, $request, 1010);
    openDisputeForPercentageSplit($request, $requester);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardGuarantorController::class, 'show'], $request))
        ->put(action([DashboardGuarantorController::class, 'resolveDispute'], $request), [
            'resolution' => GuarantorDisputeResolutionEnum::PercentageSplit->value,
            'requester_percentage' => 60,
            'notes' => 'Fair split',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Settled)
        ->and((float) $requester->wallet->fresh()->balance)->toBe(600.0)
        ->and((float) $counterparty->wallet->fresh()->balance)->toBe(404.0);
});
