<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Notification;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Http\Controllers\Dashboard\GuarantorController as DashboardGuarantorController;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Notification::fake();
});

function withoutReleaseDuringDisputeLocaleMiddleware(): void
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
 * Mirrors {@see resources/js/apps/admin/pages/Guarantor/Show.tsx} Release button visibility.
 */
function adminShowPageWouldRenderReleaseButton(string $currentStatus, string $installmentStatusValue, bool $canManage): bool
{
    $terminalStatuses = [
        'rejected_by_admin',
        'rejected',
        'ended',
        'ended_via_dispute',
        'cancelled',
        'cancelled_via_dispute',
        'escalated',
        'settled',
    ];

    return $canManage
        && ! in_array($currentStatus, $terminalStatuses, true)
        && $currentStatus !== 'disputed'
        && $installmentStatusValue === 'paid';
}

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest, admin: Admin, installment: GuarantorInstallment}
 */
function releaseDuringDisputeContext(): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create();
    $request = GuarantorRequest::factory()->create([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'counterparty_id' => $counterparty->id,
        'counterparty_type' => User::class,
        'type' => GuarantorTypeEnum::Company,
        'amount' => 1000,
        'fees' => 10,
        'status' => GuarantorStatusEnum::Disputed,
    ]);
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->paid()->create([
        'order' => 1,
        'amount' => 500,
    ]);

    Permission::firstOrCreate(['name' => 'manage guarantors', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'show guarantors', 'guard_name' => 'admin']);
    $admin = Admin::query()->create([
        'name' => 'Release Dispute Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
    $admin->givePermissionTo(['manage guarantors', 'show guarantors']);

    $requester->wallet->update(['pending_credit' => 500, 'balance' => 0]);

    return compact('requester', 'counterparty', 'request', 'admin', 'installment');
}

test('admin Release is rejected with a clear message when the parent guarantor is Disputed', function () {
    ['installment' => $installment] = releaseDuringDisputeContext();

    try {
        app(ReleaseInstallmentAction::class)->handle($installment->fresh(), 'admin');
        expect(false)->toBeTrue('Expected GuarantorException was not thrown');
    } catch (GuarantorException $exception) {
        expect($exception->getTranslationKey())->toBe('guarantor.release_denied_active_dispute');
    }

    expect($installment->fresh()->status)->toBe(InstallmentStatusEnum::Paid);
});

test('Release is rejected regardless of trigger (admin/payment/auto_release/end) when the parent guarantor is Disputed — not just the admin trigger', function (string $trigger) {
    ['installment' => $installment] = releaseDuringDisputeContext();

    expect(fn () => app(ReleaseInstallmentAction::class)->handle($installment->fresh(), $trigger))
        ->toThrow(function (GuarantorException $exception): void {
            expect($exception->getTranslationKey())->toBe('guarantor.release_denied_active_dispute');
        });

    expect($installment->fresh()->status)->toBe(InstallmentStatusEnum::Paid);
})->with([
    'admin' => 'admin',
    'payment' => 'payment',
    'auto_release' => 'auto_release',
    'end' => 'end',
]);

test('Release still works correctly for all non-Disputed, non-terminal-blocking scenarios — regression against ReversedInstallmentTest and existing Release tests', function () {
    $guarantorRequest = GuarantorRequest::factory()->company()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->paid()->create([
        'order' => 1,
        'amount' => 500,
    ]);

    $requester = $guarantorRequest->requester;
    $requester->wallet->update(['pending_credit' => 500, 'balance' => 0]);

    app(ReleaseInstallmentAction::class)->handle($installment, 'admin');

    expect($installment->fresh()->status)->toBe(InstallmentStatusEnum::Released)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $requester->wallet->fresh()->balance)->toBeGreaterThan(0);

    $guarantorRequestViaPayment = GuarantorRequest::factory()->company()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $installmentViaPayment = GuarantorInstallment::factory()->for($guarantorRequestViaPayment, 'guarantorRequest')->paid()->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $guarantorRequestViaPayment->requester->wallet->update(['pending_credit' => 500, 'balance' => 0]);

    app(ReleaseInstallmentAction::class)->handle($installmentViaPayment, 'payment');

    expect($installmentViaPayment->fresh()->status)->toBe(InstallmentStatusEnum::Released);
});

test('the Release button does not render on the admin Show page when currentStatus is disputed', function () {
    withoutReleaseDuringDisputeLocaleMiddleware();

    ['request' => $request, 'admin' => $admin, 'installment' => $installment] = releaseDuringDisputeContext();

    expect(adminShowPageWouldRenderReleaseButton('disputed', 'paid', true))->toBeFalse()
        ->and(adminShowPageWouldRenderReleaseButton('in_progress', 'paid', true))->toBeTrue();

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardGuarantorController::class, 'show'], $request))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Guarantor/Show')
            ->where('guarantorRequest.status.value', GuarantorStatusEnum::Disputed->value)
            ->where('guarantorRequest.installments.0.id', $installment->id)
            ->where('guarantorRequest.installments.0.status.value', InstallmentStatusEnum::Paid->value)
        );
});

test('a direct API/dashboard call to release while Disputed is denied at the Policy layer even if somehow reached, not just the Action layer — defense in depth', function () {
    withoutReleaseDuringDisputeLocaleMiddleware();

    ['request' => $request, 'admin' => $admin, 'installment' => $installment] = releaseDuringDisputeContext();

    expect(Gate::forUser($admin)->allows('release', $installment->fresh()))->toBeFalse();

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardGuarantorController::class, 'show'], $request))
        ->post(action([DashboardGuarantorController::class, 'releaseInstallment'], [
            'guarantorRequest' => $request,
            'installment' => $installment,
        ]))
        ->assertForbidden();

    expect($installment->fresh()->status)->toBe(InstallmentStatusEnum::Paid);
});
