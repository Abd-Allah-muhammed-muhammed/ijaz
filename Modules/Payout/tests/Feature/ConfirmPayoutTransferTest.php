<?php

use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Http\Controllers\Dashboard\PayoutRequestController;
use Modules\Payout\Models\PayoutRequest;

test('a payout can be confirmed by a different admin holding "confirm payouts" permission, moving it to completed with a gateway_reference recorded', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $maker = createPayoutDashboardAdmin(['edit withdrawRequests']);
    $checker = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($checker, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-12345',
        ])
        ->assertRedirect(route('dashboard.payout-requests.index'))
        ->assertSessionHas('success');

    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::Completed)
        ->and($payout->gateway_reference)->toBe('BANK-TXN-12345')
        ->and($payout->processed_by_admin_id)->toBe($checker->id);
});

test('the SAME admin who triggered the payouts source operation (the withdraw approver) cannot confirm that payout — even if they hold "confirm payouts" permission', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $maker = createPayoutDashboardAdmin(['confirm payouts', 'edit withdrawRequests']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($maker, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-99999',
        ])
        ->assertRedirect()
        ->assertSessionHas('error', __('payout.maker_cannot_confirm'));

    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::Pending)
        ->and($payout->gateway_reference)->toBeNull();
});

test('confirming a payout without "confirm payouts" permission is forbidden regardless of who the admin is', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $maker = createPayoutDashboardAdmin(['edit withdrawRequests']);
    $otherAdmin = createPayoutDashboardAdmin(['edit withdrawRequests']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($otherAdmin, 'admin')
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-00001',
        ])
        ->assertForbidden();
});

test('confirming requires a gateway_reference — cannot mark completed without one', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $maker = createPayoutDashboardAdmin(['edit withdrawRequests']);
    $checker = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($checker, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [])
        ->assertSessionHasErrors(['gateway_reference']);

    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::Pending);
});

test('a payout already completed cannot be confirmed again', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $maker = createPayoutDashboardAdmin(['edit withdrawRequests']);
    $checker = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Completed,
        'gateway_reference' => 'BANK-TXN-EXISTING',
        'processed_by_admin_id' => $checker->id,
    ]);

    $anotherChecker = createPayoutDashboardAdmin(['confirm payouts']);

    $this->actingAs($anotherChecker, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-RETRY',
        ])
        ->assertRedirect()
        ->assertSessionHas('error', __('payout.already_completed'));

    expect($payout->fresh()->gateway_reference)->toBe('BANK-TXN-EXISTING');
});

test('an admin can mark a payout as failed with a required failure_reason, which allows it to be retried (confirmed) later by any eligible admin', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $maker = createPayoutDashboardAdmin(['edit withdrawRequests']);
    $checker = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($checker, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'fail'], ['payoutRequest' => $payout->id]), [
            'failure_reason' => 'Bank rejected transfer — invalid IBAN',
        ])
        ->assertRedirect(route('dashboard.payout-requests.index'))
        ->assertSessionHas('success');

    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::Failed)
        ->and($payout->failure_reason)->toBe('Bank rejected transfer — invalid IBAN');

    $this->actingAs($checker, 'admin')
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-RETRY-OK',
        ])
        ->assertRedirect(route('dashboard.payout-requests.index'))
        ->assertSessionHas('success');

    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::Completed)
        ->and($payout->gateway_reference)->toBe('BANK-TXN-RETRY-OK')
        ->and($payout->failure_reason)->toBeNull()
        ->and($payout->processed_by_admin_id)->toBe($checker->id);
});
