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
use Modules\Guarantor\Actions\Installment\PayInstallmentAction;
use Modules\Guarantor\Actions\Installment\ReleaseInstallmentAction;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Http\Controllers\Dashboard\GuarantorController as DashboardGuarantorController;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Models\Payment;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    Notification::fake();
    config(['app.payment.driver' => 'testing']);
});

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest}
 */
function installmentPolicySplitContext(array $requestAttributes = []): array
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
    ], $requestAttributes));

    return compact('requester', 'counterparty', 'request');
}

function withoutInstallmentPolicySplitLocaleMiddleware(): void
{
    test()->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    test()->withoutVite();
}

function createInstallmentPolicySplitAdmin(): Admin
{
    Permission::firstOrCreate(['name' => 'manage guarantors', 'guard_name' => 'admin']);
    Permission::firstOrCreate(['name' => 'show guarantors', 'guard_name' => 'admin']);
    $admin = Admin::query()->create([
        'name' => 'Installment Split Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
    $admin->givePermissionTo(['manage guarantors', 'show guarantors']);

    return $admin;
}

test('a non-counterparty attempting to pay an installment gets 403 with the unauthorized message — Policy-level, unchanged', function () {
    ['requester' => $requester, 'request' => $request] = installmentPolicySplitContext([
        'status' => GuarantorStatusEnum::Accepted,
    ]);
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);

    Sanctum::actingAs($requester);

    $this->postJson(route('api.v1.guarantor.guarantor.installments.pay', [
        'guarantorRequest' => $request,
        'installment' => $installment,
    ]))
        ->assertForbidden()
        ->assertJson([
            'success' => false,
            'message' => __('guarantor.unauthorized'),
        ]);

    expect(Payment::query()->where('product_id', $installment->id)->exists())->toBeFalse();
});

test('a counterparty attempting to pay a voided installment gets 422 with the specific message — now via GuarantorException, not Policy deny', function () {
    ['counterparty' => $counterparty, 'request' => $request] = installmentPolicySplitContext([
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
            'success' => false,
            'message' => __('guarantor.pay_denied_installment_voided'),
        ]);
});

test('a counterparty attempting to pay during an active dispute gets 422 — same', function () {
    ['counterparty' => $counterparty, 'request' => $request] = installmentPolicySplitContext([
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
            'success' => false,
            'message' => __('guarantor.pay_denied_active_dispute'),
        ]);
});

test('a counterparty attempting to pay a terminal guarantor gets 422 — same', function () {
    ['counterparty' => $counterparty, 'request' => $request] = installmentPolicySplitContext([
        'status' => GuarantorStatusEnum::Ended,
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
            'success' => false,
            'message' => __('guarantor.pay_denied_already_resolved'),
        ]);
});

test('a counterparty attempting to pay an installment on an Individual-type guarantor gets 422 — same (was previously a Policy deny)', function () {
    ['counterparty' => $counterparty, 'request' => $request] = installmentPolicySplitContext([
        'type' => GuarantorTypeEnum::Individual,
        'status' => GuarantorStatusEnum::Accepted,
    ]);
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);

    expect(Gate::forUser($counterparty)->allows('pay', [$installment, $request]))->toBeTrue();

    Sanctum::actingAs($counterparty);

    $this->postJson(route('api.v1.guarantor.guarantor.installments.pay', [
        'guarantorRequest' => $request,
        'installment' => $installment,
    ]))
        ->assertUnprocessable()
        ->assertJson([
            'success' => false,
            'message' => __('guarantor.pay_denied_individual_use_lump_sum'),
        ]);
});

test('release follows the identical split — admin permission check stays 403 in Policy, all guarantor/installment state checks move to 422 in the Action', function () {
    withoutInstallmentPolicySplitLocaleMiddleware();

    ['request' => $request] = installmentPolicySplitContext([
        'status' => GuarantorStatusEnum::Disputed,
    ]);
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->paid()->create([
        'order' => 1,
        'amount' => 500,
    ]);

    $adminWithoutPermission = createInstallmentPolicySplitAdmin();
    $adminWithoutPermission->revokePermissionTo('manage guarantors');

    expect(Gate::forUser($adminWithoutPermission)->allows('release', [$installment, $request]))->toBeFalse();

    $this->actingAs($adminWithoutPermission, 'admin')
        ->from(action([DashboardGuarantorController::class, 'show'], $request))
        ->post(action([DashboardGuarantorController::class, 'releaseInstallment'], [
            'guarantorRequest' => $request,
            'installment' => $installment,
        ]))
        ->assertForbidden();

    $admin = createInstallmentPolicySplitAdmin();

    expect(Gate::forUser($admin)->allows('release', [$installment->fresh(), $request]))->toBeTrue();

    expect(fn () => app(ReleaseInstallmentAction::class)->handle($installment->fresh(), 'admin', $request))
        ->toThrow(function (GuarantorException $exception): void {
            expect($exception->getTranslationKey())->toBe('guarantor.release_denied_active_dispute')
                ->and($exception->getHttpStatusCode())->toBe(422);
        });

    expect($installment->fresh()->status)->toBe(InstallmentStatusEnum::Paid);
});

test('existing successful pay/release flows are completely unaffected — regression', function () {
    ['counterparty' => $counterparty, 'request' => $request] = installmentPolicySplitContext([
        'status' => GuarantorStatusEnum::Accepted,
        'amount' => 1000,
        'fees' => 10,
    ]);
    $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);

    $payResponse = app(PayInstallmentAction::class)->handle(
        $request->fresh(),
        $installment->fresh(),
        $counterparty,
    );

    expect($payResponse)->toHaveKey('url')
        ->and(Payment::query()->where('product_id', $installment->id)->exists())->toBeTrue();

    $guarantorRequest = GuarantorRequest::factory()->company()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $paidInstallment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->paid()->create([
        'order' => 1,
        'amount' => 500,
    ]);
    $guarantorRequest->requester->wallet->update(['pending_credit' => 500, 'balance' => 0]);

    app(ReleaseInstallmentAction::class)->handle($paidInstallment, 'admin', $guarantorRequest);

    expect($paidInstallment->fresh()->status)->toBe(InstallmentStatusEnum::Released);
});

test('the exact message text for every check is unchanged — only the layer and status code move, not the wording', function (string $translationKey, callable $setupPayExpectation) {
    $setupPayExpectation($translationKey);
})->with([
    'voided installment pay' => [
        'guarantor.pay_denied_installment_voided',
        function (string $translationKey): void {
            ['counterparty' => $counterparty, 'request' => $request] = installmentPolicySplitContext([
                'status' => GuarantorStatusEnum::Accepted,
            ]);
            $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
                'status' => InstallmentStatusEnum::Voided,
            ]);

            expect(fn () => app(PayInstallmentAction::class)->handle($request->fresh(), $installment->fresh(), $counterparty))
                ->toThrow(function (GuarantorException $exception) use ($translationKey): void {
                    expect($exception->getTranslationKey())->toBe($translationKey)
                        ->and(__($exception->getTranslationKey()))->toBe(__($translationKey));
                });
        },
    ],
    'active dispute pay' => [
        'guarantor.pay_denied_active_dispute',
        function (string $translationKey): void {
            ['counterparty' => $counterparty, 'request' => $request] = installmentPolicySplitContext([
                'status' => GuarantorStatusEnum::Disputed,
            ]);
            $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create();

            expect(fn () => app(PayInstallmentAction::class)->handle($request->fresh(), $installment->fresh(), $counterparty))
                ->toThrow(function (GuarantorException $exception) use ($translationKey): void {
                    expect($exception->getTranslationKey())->toBe($translationKey)
                        ->and(__($exception->getTranslationKey()))->toBe(__($translationKey));
                });
        },
    ],
    'terminal guarantor pay' => [
        'guarantor.pay_denied_already_resolved',
        function (string $translationKey): void {
            ['counterparty' => $counterparty, 'request' => $request] = installmentPolicySplitContext([
                'status' => GuarantorStatusEnum::Cancelled,
            ]);
            $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create();

            expect(fn () => app(PayInstallmentAction::class)->handle($request->fresh(), $installment->fresh(), $counterparty))
                ->toThrow(function (GuarantorException $exception) use ($translationKey): void {
                    expect($exception->getTranslationKey())->toBe($translationKey)
                        ->and(__($exception->getTranslationKey()))->toBe(__($translationKey));
                });
        },
    ],
    'individual type pay' => [
        'guarantor.pay_denied_individual_use_lump_sum',
        function (string $translationKey): void {
            ['counterparty' => $counterparty, 'request' => $request] = installmentPolicySplitContext([
                'type' => GuarantorTypeEnum::Individual,
                'status' => GuarantorStatusEnum::Accepted,
            ]);
            $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create();

            expect(fn () => app(PayInstallmentAction::class)->handle($request->fresh(), $installment->fresh(), $counterparty))
                ->toThrow(function (GuarantorException $exception) use ($translationKey): void {
                    expect($exception->getTranslationKey())->toBe($translationKey)
                        ->and(__($exception->getTranslationKey()))->toBe(__($translationKey));
                });
        },
    ],
    'reversed installment pay' => [
        'guarantor.release_denied_installment_reversed',
        function (string $translationKey): void {
            ['counterparty' => $counterparty, 'request' => $request] = installmentPolicySplitContext([
                'status' => GuarantorStatusEnum::InProgress,
            ]);
            $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
                'status' => InstallmentStatusEnum::Reversed,
            ]);

            expect(fn () => app(PayInstallmentAction::class)->handle($request->fresh(), $installment->fresh(), $counterparty))
                ->toThrow(function (GuarantorException $exception) use ($translationKey): void {
                    expect($exception->getTranslationKey())->toBe($translationKey)
                        ->and(__($exception->getTranslationKey()))->toBe(__($translationKey));
                });
        },
    ],
    'disputed release' => [
        'guarantor.release_denied_active_dispute',
        function (string $translationKey): void {
            ['request' => $request] = installmentPolicySplitContext([
                'status' => GuarantorStatusEnum::Disputed,
            ]);
            $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->paid()->create();

            expect(fn () => app(ReleaseInstallmentAction::class)->handle($installment->fresh(), 'admin', $request))
                ->toThrow(function (GuarantorException $exception) use ($translationKey): void {
                    expect($exception->getTranslationKey())->toBe($translationKey)
                        ->and(__($exception->getTranslationKey()))->toBe(__($translationKey));
                });
        },
    ],
    'terminal guarantor release' => [
        'guarantor.release_denied_guarantor_terminal',
        function (string $translationKey): void {
            ['request' => $request] = installmentPolicySplitContext([
                'status' => GuarantorStatusEnum::Ended,
            ]);
            $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->paid()->create();

            expect(fn () => app(ReleaseInstallmentAction::class)->handle($installment->fresh(), 'admin', $request))
                ->toThrow(function (GuarantorException $exception) use ($translationKey): void {
                    expect($exception->getTranslationKey())->toBe($translationKey)
                        ->and(__($exception->getTranslationKey()))->toBe(__($translationKey));
                });
        },
    ],
    'unpaid installment release' => [
        'guarantor.status_transition_not_allowed',
        function (string $translationKey): void {
            ['request' => $request] = installmentPolicySplitContext([
                'status' => GuarantorStatusEnum::InProgress,
            ]);
            $installment = GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
                'status' => InstallmentStatusEnum::Pending,
            ]);

            expect(fn () => app(ReleaseInstallmentAction::class)->handle($installment->fresh(), 'admin', $request))
                ->toThrow(function (GuarantorException $exception) use ($translationKey): void {
                    expect($exception->getTranslationKey())->toBe($translationKey)
                        ->and(__($exception->getTranslationKey()))->toBe(__($translationKey));
                });
        },
    ],
]);
