<?php

use App\Models\Admin;
use App\Models\User;
use App\Support\LookupCache;
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
use Modules\Guarantor\Actions\Guarantor\ResolveDisputeFullToPartyAction;
use Modules\Guarantor\Actions\Installment\PayInstallmentAction;
use Modules\Guarantor\Contracts\Repositories\GuarantorRepositoryInterface;
use Modules\Guarantor\Contracts\Repositories\InstallmentRepositoryInterface;
use Modules\Guarantor\Enums\GuarantorDisputeResolutionEnum;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Http\Controllers\Dashboard\GuarantorController as DashboardGuarantorController;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
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
function disputeTerminalStatusContext(array $requestAttributes = []): array
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
        'name' => 'Dispute Terminal Status Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
    $admin->givePermissionTo(['manage guarantors', 'show guarantors']);

    return compact('requester', 'counterparty', 'request', 'admin');
}

function completeDisputeTerminalStatusPayment($owner, $product, float $amount): void
{
    $payment = createPaymentFor($owner, $product, [
        'amount' => $amount,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    event(new PaymentCompleted($payment->load('product')));
}

function openDisputeForTerminalStatusTests(GuarantorRequest $request, User $actor, string $actorRole = 'requester'): GuarantorRequest
{
    return app(OpenGuarantorDisputeAction::class)->handle(
        $request->fresh(),
        $actor,
        $actorRole,
        'Dispute opened for terminal status tests',
    );
}

function withoutDisputeTerminalStatusLocaleMiddleware(): void
{
    test()->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    test()->withoutVite();
}

test('dispute resolution full-to-requester now sets status to EndedViaDispute, not Ended', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeTerminalStatusContext();
    completeDisputeTerminalStatusPayment($counterparty, $request, 1010);
    openDisputeForTerminalStatusTests($request, $requester);

    app(ResolveDisputeFullToPartyAction::class)->handle($request->fresh(), $admin, 'requester');

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::EndedViaDispute)
        ->and($request->fresh()->status)->not->toBe(GuarantorStatusEnum::Ended);
});

test('dispute resolution full-to-counterparty now sets status to CancelledViaDispute, not Cancelled', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeTerminalStatusContext();
    completeDisputeTerminalStatusPayment($counterparty, $request, 1010);
    openDisputeForTerminalStatusTests($request, $requester);

    app(ResolveDisputeFullToPartyAction::class)->handle($request->fresh(), $admin, 'counterparty');

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::CancelledViaDispute)
        ->and($request->fresh()->status)->not->toBe(GuarantorStatusEnum::Cancelled);
});

test('ordinary party-initiated End still sets plain Ended — regression, unaffected', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = disputeTerminalStatusContext();
    completeDisputeTerminalStatusPayment($counterparty, $request, 1010);

    app(EndGuarantorAction::class)->handle($request->fresh(), $requester, 'requester');

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Ended)
        ->and($request->fresh()->status)->not->toBe(GuarantorStatusEnum::EndedViaDispute);
});

test('ordinary admin Cancel still sets plain Cancelled — regression, unaffected', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeTerminalStatusContext();
    completeDisputeTerminalStatusPayment($counterparty, $request, 1010);

    app(CancelGuarantorAction::class)->handle($request->fresh(), 'Admin cancelled', null, $admin);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Cancelled)
        ->and($request->fresh()->status)->not->toBe(GuarantorStatusEnum::CancelledViaDispute);
});

test('EndedViaDispute and CancelledViaDispute are both terminal — no further End/pay/dispute/cancel/release actions allowed', function () {
    foreach ([
        ['status' => GuarantorStatusEnum::EndedViaDispute, 'party' => 'requester'],
        ['status' => GuarantorStatusEnum::CancelledViaDispute, 'party' => 'counterparty'],
    ] as $case) {
        ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeTerminalStatusContext([
            'type' => GuarantorTypeEnum::Company,
            'status' => $case['status'],
        ]);
        $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
            'order' => 1,
            'amount' => 500,
            'status' => InstallmentStatusEnum::Paid,
        ]);

        expect($case['status']->isTerminal())->toBeTrue()
            ->and(Gate::forUser($requester)->denies('end', $request))->toBeTrue()
            ->and(Gate::forUser($counterparty)->denies('dispute', $request))->toBeTrue();

        expect(fn () => app(EndGuarantorAction::class)->handle($request->fresh(), $requester, 'requester'))
            ->toThrow(GuarantorException::class);
        expect(fn () => app(CancelGuarantorAction::class)->handle($request->fresh(), 'Too late', null, $admin))
            ->toThrow(GuarantorException::class);
        expect(fn () => app(OpenGuarantorDisputeAction::class)->handle($request->fresh(), $requester, 'requester', 'Too late'))
            ->toThrow(GuarantorException::class);

        Sanctum::actingAs($counterparty);
        expect(fn () => app(PayInstallmentAction::class)->handle($request->fresh(), $installment->fresh(), $counterparty))
            ->toThrow(GuarantorException::class);
    }
});

test('GuarantorStatusEnum::color() and label handle both new cases without an UnhandledMatchError', function () {
    foreach ([GuarantorStatusEnum::EndedViaDispute, GuarantorStatusEnum::CancelledViaDispute] as $status) {
        expect($status->color())->toBeString()->not->toBe('')
            ->and($status->toString())->toBeString()->not->toBe('')
            ->and($status->toArray())->toMatchArray([
                'value' => $status->value,
                'label' => $status->toString(),
                'color' => $status->color(),
            ]);
    }
});

test('scopeActive, chat eligibility, and overdue-installment queries correctly exclude EndedViaDispute/CancelledViaDispute as terminal, same as they exclude Ended/Cancelled', function () {
    $active = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::InProgress]);
    $endedViaDispute = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::EndedViaDispute]);
    $cancelledViaDispute = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::CancelledViaDispute]);

    expect(GuarantorRequest::query()->active()->pluck('id')->all())
        ->toContain($active->id)
        ->not->toContain($endedViaDispute->id)
        ->not->toContain($cancelledViaDispute->id);

    $overdueInstallment = GuarantorInstallment::factory()->for($active, 'guarantorRequest')->create([
        'status' => InstallmentStatusEnum::Pending,
        'due_date' => now()->subDay(),
    ]);
    $terminalOverdueInstallment = GuarantorInstallment::factory()->for($endedViaDispute, 'guarantorRequest')->create([
        'status' => InstallmentStatusEnum::Pending,
        'due_date' => now()->subDay(),
    ]);

    $overdueIds = app(InstallmentRepositoryInterface::class)
        ->getOverdue()
        ->pluck('id')
        ->all();

    expect($overdueIds)->toContain($overdueInstallment->id)
        ->not->toContain($terminalOverdueInstallment->id);
});

test('the admin dashboard status filter can filter specifically by EndedViaDispute or CancelledViaDispute', function () {
    withoutDisputeTerminalStatusLocaleMiddleware();
    $admin = disputeTerminalStatusContext()['admin'];

    $endedViaDispute = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::EndedViaDispute]);
    $cancelledViaDispute = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::CancelledViaDispute]);
    GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::InProgress]);

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardGuarantorController::class, 'index'], ['status' => GuarantorStatusEnum::EndedViaDispute->value]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $endedViaDispute->id)
        );

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardGuarantorController::class, 'index'], ['status' => GuarantorStatusEnum::CancelledViaDispute->value]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $cancelledViaDispute->id)
        );
});

test('dashboard aggregate stats count EndedViaDispute together with Ended, and CancelledViaDispute together with Cancelled, for general ended/cancelled count purposes — while still being individually filterable', function () {
    GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::Ended]);
    GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::EndedViaDispute]);
    GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::Cancelled]);
    GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::CancelledViaDispute]);
    GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::InProgress]);

    LookupCache::forget('stats:guarantor:dashboard');

    $stats = app(GuarantorRepositoryInterface::class)->getDashboardStats();

    expect($stats['ended'])->toBe(2)
        ->and($stats['cancelled'])->toBe(2)
        ->and($stats['total'])->toBe(5);
});

test('the Dispute tab and dispute-resolution notification continue to work correctly for these new terminal statuses — regression against Batches 1-3', function () {
    withoutDisputeTerminalStatusLocaleMiddleware();

    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = disputeTerminalStatusContext();
    completeDisputeTerminalStatusPayment($counterparty, $request, 1010);
    openDisputeForTerminalStatusTests($request, $requester);

    app(ResolveDisputeFullToPartyAction::class)->handle($request->fresh(), $admin, 'requester', 'Resolved for requester');

    $resolved = $request->fresh();

    expect($resolved->status)->toBe(GuarantorStatusEnum::EndedViaDispute);

    Notification::assertSentTo($requester, GuarantorDisputeResolvedNotification::class, function ($notification) {
        return $notification->resolution === GuarantorDisputeResolutionEnum::FullRequester;
    });
    Notification::assertSentTo($counterparty, GuarantorDisputeResolvedNotification::class);

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardGuarantorController::class, 'show'], $resolved))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Guarantor/Show')
            ->where('guarantorRequest.status.value', GuarantorStatusEnum::EndedViaDispute->value)
            ->has('guarantorRequest.status_histories')
        );

    $histories = $resolved->statusHistories()->get();
    expect($histories->contains(
        fn ($history) => $history->to_status === GuarantorStatusEnum::Disputed->value
            && $history->getRawOriginal('reason') === 'Dispute opened for terminal status tests'
    ))->toBeTrue();
});
