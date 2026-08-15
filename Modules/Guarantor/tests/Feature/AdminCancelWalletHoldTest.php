<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Queue;
use Modules\Guarantor\Actions\Dashboard\AdminCancelGuarantorAction;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Jobs\ReleaseInstallmentJob;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Enums\PaymentStatusEnum;
use Modules\Payment\Events\PaymentCompleted;
use Modules\Wallet\Models\WalletTransaction;

beforeEach(function (): void {
    Notification::fake();
});

/**
 * @return array{requester: User, counterparty: User, request: GuarantorRequest, admin: Admin}
 */
function adminCancelHoldContext(array $requestAttributes = []): array
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
    ], $requestAttributes));
    $admin = Admin::query()->create([
        'name' => 'Admin Cancel Hold Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    return compact('requester', 'counterparty', 'request', 'admin');
}

function completeAdminCancelHoldPayment($owner, $product, float $amount): void
{
    $payment = createPaymentFor($owner, $product, [
        'amount' => $amount,
        'driver' => 'testing',
        'status' => PaymentStatusEnum::Accepted,
    ]);

    event(new PaymentCompleted($payment->load('product')));
}

function cancelGuarantorAsAdmin(GuarantorRequest $request, Admin $admin): void
{
    app(AdminCancelGuarantorAction::class)->handle(
        $request->fresh(),
        'Admin cancelled',
        null,
        $admin,
    );
}

test('admin cancelling a Company guarantor at status "accepted" with only the first installment paid actually reverses BOTH parties wallet holds — counterparty pending_debit and requester pending_credit both return to zero for the held amount', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = adminCancelHoldContext([
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

    completeAdminCancelHoldPayment($counterparty, $first, 500);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Accepted)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(500.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(500.0);

    cancelGuarantorAsAdmin($request, $admin);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Cancelled)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $requester->wallet->fresh()->balance)->toBe(0.0)
        ->and((float) $counterparty->wallet->fresh()->balance)->toBe(0.0);
});

test('admin cancelling a Company guarantor at status "in_progress" with only 2 of 4 installments worth of holds present (partial, not full contract) still correctly reverses exactly what is actually held — not blocked by a full-contract-amount check', function () {
    Queue::fake([ReleaseInstallmentJob::class]);

    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = adminCancelHoldContext([
        'type' => GuarantorTypeEnum::Company,
        'status' => GuarantorStatusEnum::InProgress,
    ]);

    $installments = collect([250, 250, 250, 250])->map(fn (int $amount, int $index) => GuarantorInstallment::factory()->for($request, 'guarantorRequest')->create([
        'order' => $index + 1,
        'amount' => $amount,
    ]));

    completeAdminCancelHoldPayment($counterparty, $installments[0], 250);
    completeAdminCancelHoldPayment($counterparty, $installments[1], 250);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::InProgress)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(500.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(500.0);

    cancelGuarantorAsAdmin($request, $admin);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Cancelled)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $requester->wallet->fresh()->balance)->toBe(0.0)
        ->and((float) $counterparty->wallet->fresh()->balance)->toBe(0.0);
});

test('admin cancelling an Individual guarantor still works exactly as before — no regression for the full amount+fees case', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = adminCancelHoldContext([
        'status' => GuarantorStatusEnum::Accepted,
    ]);

    completeAdminCancelHoldPayment($counterparty, $request, 1010);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::InProgress)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(1010.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(1010.0);

    cancelGuarantorAsAdmin($request, $admin);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Cancelled)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $requester->wallet->fresh()->balance)->toBe(0.0)
        ->and((float) $counterparty->wallet->fresh()->balance)->toBe(0.0);
});

test('cancelling a guarantor with NO payment yet (no holds exist at all) remains a safe no-op, as today', function () {
    ['requester' => $requester, 'counterparty' => $counterparty, 'request' => $request, 'admin' => $admin] = adminCancelHoldContext([
        'type' => GuarantorTypeEnum::Company,
        'status' => GuarantorStatusEnum::Accepted,
    ]);

    expect((float) $requester->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and(WalletTransaction::query()->count())->toBe(0);

    cancelGuarantorAsAdmin($request, $admin);

    expect($request->fresh()->status)->toBe(GuarantorStatusEnum::Cancelled)
        ->and((float) $requester->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $requester->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $counterparty->wallet->fresh()->pending_credit)->toBe(0.0)
        ->and((float) $counterparty->wallet->fresh()->pending_debit)->toBe(0.0)
        ->and((float) $requester->wallet->fresh()->balance)->toBe(0.0)
        ->and((float) $counterparty->wallet->fresh()->balance)->toBe(0.0)
        ->and(WalletTransaction::query()->count())->toBe(0);
});
