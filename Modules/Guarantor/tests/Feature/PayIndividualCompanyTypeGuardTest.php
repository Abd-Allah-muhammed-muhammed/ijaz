<?php

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Actions\Payment\PayIndividualGuarantorAction;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Exceptions\GuarantorException;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Models\Payment;

beforeEach(function (): void {
    config(['app.payment.driver' => 'testing']);
});

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest}
 */
function payIndividualTypeGuardContext(array $requestAttributes = []): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create();
    $request = GuarantorRequest::factory()->accepted()->create(array_merge([
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'counterparty_id' => $counterparty->id,
        'counterparty_type' => User::class,
        'amount' => 1000,
        'fees' => 10,
        'total' => 1010,
    ], $requestAttributes));

    return compact('requester', 'counterparty', 'request');
}

test('PayIndividualGuarantorAction rejects a Company-type guarantor with a clear error, before any wallet/status change', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request] = payIndividualTypeGuardContext([
        'type' => GuarantorTypeEnum::Company,
    ]);
    GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
    ]);
    GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => 2,
        'amount' => 500,
    ]);

    $requesterWalletBefore = (float) ($requester->wallet->fresh()->pending_credit ?? 0);
    $counterpartyWalletBefore = (float) ($counterparty->wallet->fresh()->pending_debit ?? 0);

    expect(fn () => app(PayIndividualGuarantorAction::class)->handle($request->fresh(), $counterparty))
        ->toThrow(function (GuarantorException $e): void {
            expect($e->getTranslationKey())->toBe('guarantor.pay_denied_company_use_installments')
                ->and($e->getHttpStatusCode())->toBe(422)
                ->and(__($e->getTranslationKey()))->not->toBe('This action is unauthorized.')
                ->and(__($e->getTranslationKey()))->toContain('installment');
        });

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Accepted)
        ->and(Payment::query()->where('product_id', $request->id)->exists())->toBeFalse()
        ->and((float) ($requester->wallet->fresh()->pending_credit ?? 0))->toBe($requesterWalletBefore)
        ->and((float) ($counterparty->wallet->fresh()->pending_debit ?? 0))->toBe($counterpartyWalletBefore)
        ->and($request->installments()->where('status', 'pending')->count())->toBe(2);
});

test('PayIndividualGuarantorAction still works correctly for Individual-type guarantors — regression', function () {
    ['counterparty' => $counterparty, 'request' => $request] = payIndividualTypeGuardContext([
        'type' => GuarantorTypeEnum::Individual,
    ]);

    $response = app(PayIndividualGuarantorAction::class)->handle($request->fresh(), $counterparty);

    expect($response)->toHaveKey('url')
        ->and(Payment::query()
            ->where('product_type', GuarantorRequest::class)
            ->where('product_id', $request->id)
            ->exists())->toBeTrue()
        ->and($request->fresh()->status)->toBe(GuarantorStatusEnum::Accepted);
});

test('the policy/route layer also rejects this at the earliest point (defense in depth), not just deep inside the Action', function () {
    ['counterparty' => $counterparty, 'request' => $request] = payIndividualTypeGuardContext([
        'type' => GuarantorTypeEnum::Company,
    ]);

    $gate = Gate::forUser($counterparty)->inspect('pay', $request);

    expect($gate->denied())->toBeTrue()
        ->and($gate->message())->toBe(__('guarantor.pay_denied_company_use_installments'))
        ->and($gate->message())->not->toBe('This action is unauthorized.')
        ->and($gate->message())->not->toBe(__('guarantor.unauthorized'));
});

test('attempting to pay a Company guarantor via the individual /pay endpoint returns a clear, specific error message (not a generic one) directing to the correct installment payment flow', function () {
    ['counterparty' => $counterparty, 'request' => $request] = payIndividualTypeGuardContext([
        'type' => GuarantorTypeEnum::Company,
    ]);

    Sanctum::actingAs($counterparty);

    $this->postJson(route('api.v1.guarantor.guarantor.pay', $request))
        ->assertForbidden()
        ->assertJson([
            'message' => __('guarantor.pay_denied_company_use_installments'),
        ]);

    expect(Payment::query()->where('product_id', $request->id)->exists())->toBeFalse()
        ->and($request->fresh()->status)->toBe(GuarantorStatusEnum::Accepted);
});
