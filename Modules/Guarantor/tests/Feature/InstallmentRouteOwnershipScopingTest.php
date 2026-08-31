<?php

use App\Models\Admin;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Http\Controllers\Dashboard\GuarantorController as DashboardGuarantorController;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Modules\Payment\Models\Payment;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    config(['app.payment.driver' => 'testing']);
});

/**
 * @return array{
 *     requester: User,
 *     counterparty: User,
 *     ownerRequest: GuarantorRequest,
 *     otherRequest: GuarantorRequest,
 *     installment: GuarantorInstallment
 * }
 */
function installmentOwnershipContext(): array
{
    $requester = User::factory()->create();
    $counterparty = User::factory()->create();

    $ownerRequest = GuarantorRequest::factory()->accepted()->create([
        'type' => GuarantorTypeEnum::Company,
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'counterparty_id' => $counterparty->id,
        'counterparty_type' => User::class,
        'amount' => 1000,
        'fees' => 10,
    ]);

    $otherRequest = GuarantorRequest::factory()->accepted()->create([
        'type' => GuarantorTypeEnum::Company,
        'requester_id' => $requester->id,
        'requester_type' => User::class,
        'counterparty_id' => $counterparty->id,
        'counterparty_type' => User::class,
        'amount' => 2000,
        'fees' => 20,
    ]);

    $installment = GuarantorInstallment::factory()->for($ownerRequest, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 500,
        'status' => InstallmentStatusEnum::Pending,
    ]);

    GuarantorInstallment::factory()->for($otherRequest, 'guarantorRequest')->create([
        'order' => 1,
        'amount' => 1000,
        'status' => InstallmentStatusEnum::Pending,
    ]);

    return compact('requester', 'counterparty', 'ownerRequest', 'otherRequest', 'installment');
}

test('attempting to pay an installment using a guarantorRequest UUID in the URL that does NOT own that installment is rejected (403 or 404), not processed', function () {
    ['counterparty' => $counterparty, 'otherRequest' => $otherRequest, 'installment' => $installment] = installmentOwnershipContext();

    Sanctum::actingAs($counterparty);

    $this->postJson(route('api.v1.guarantor.guarantor.installments.pay', [
        'guarantorRequest' => $otherRequest,
        'installment' => $installment,
    ]))
        ->assertNotFound();

    expect(Payment::query()->where('product_id', $installment->id)->exists())->toBeFalse()
        ->and($installment->fresh()->status)->toBe(InstallmentStatusEnum::Pending)
        ->and($otherRequest->fresh()->status)->toBe(GuarantorStatusEnum::Accepted);
});

test('paying an installment via its own correct parent guarantorRequest still works normally — regression', function () {
    ['counterparty' => $counterparty, 'ownerRequest' => $ownerRequest, 'installment' => $installment] = installmentOwnershipContext();

    Sanctum::actingAs($counterparty);

    $this->postJson(route('api.v1.guarantor.guarantor.installments.pay', [
        'guarantorRequest' => $ownerRequest,
        'installment' => $installment,
    ]))
        ->assertSuccessful()
        ->assertJsonPath('data.url', fn ($url) => is_string($url) && $url !== '');

    expect(Payment::query()
        ->where('product_type', GuarantorInstallment::class)
        ->where('product_id', $installment->id)
        ->exists())->toBeTrue()
        ->and($ownerRequest->fresh()->status)->toBe(GuarantorStatusEnum::Accepted);
});

test('the same protection applies to any other installment-scoped endpoint using this route pattern (e.g. release, if it shares the same nested route shape) — check and cover if applicable', function () {
    Permission::findOrCreate('manage guarantors', 'admin');
    Permission::findOrCreate('show guarantors', 'admin');

    $admin = Admin::query()->create([
        'name' => 'Scoped Release Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);
    $admin->givePermissionTo(['manage guarantors', 'show guarantors']);

    ['requester' => $requester, 'ownerRequest' => $ownerRequest, 'otherRequest' => $otherRequest] = installmentOwnershipContext();

    $paidInstallment = GuarantorInstallment::factory()->for($ownerRequest, 'guarantorRequest')->paid()->create([
        'order' => 2,
        'amount' => 500,
    ]);
    $ownerRequest->update(['status' => GuarantorStatusEnum::InProgress]);
    $requester->wallet->update(['pending_credit' => 500, 'balance' => 0]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardGuarantorController::class, 'show'], $otherRequest))
        ->post(action([DashboardGuarantorController::class, 'releaseInstallment'], [
            'guarantorRequest' => $otherRequest,
            'installment' => $paidInstallment,
        ]))
        ->assertNotFound();

    expect($paidInstallment->fresh()->status)->toBe(InstallmentStatusEnum::Paid);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardGuarantorController::class, 'show'], $ownerRequest))
        ->post(action([DashboardGuarantorController::class, 'releaseInstallment'], [
            'guarantorRequest' => $ownerRequest,
            'installment' => $paidInstallment,
        ]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($paidInstallment->fresh()->status)->toBe(InstallmentStatusEnum::Released);
});
