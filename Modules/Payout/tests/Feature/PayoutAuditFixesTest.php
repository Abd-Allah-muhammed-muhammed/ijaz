<?php

use App\Enums\OperationStatusEnum;
use App\Http\Controllers\Provider\AuthController;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Payout\Actions\AttachAmountInTransferToWalletAction;
use Modules\Payout\Actions\CreatePayoutRequestAction;
use Modules\Payout\Contracts\Repositories\PayoutRequestRepositoryInterface;
use Modules\Payout\DTOs\CreatePayoutRequestData;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Exceptions\PayoutException;
use Modules\Payout\Http\Controllers\Dashboard\PayoutRequestController;
use Modules\Payout\Models\PayoutRequest;
use Modules\Wallet\Http\Controllers\Dashboard\WithdrawRequestController as DashboardWithdrawRequestController;
use Modules\Wallet\Models\WithdrawRequest;
use Modules\Wallet\Services\WalletService;

test('approving a withdraw creates exactly one PayoutRequest, and existsForOperation correctly reports true afterward', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    fundWallet($user, 250);
    $withdrawRequest = createWithdrawFor($user, [
        'amount' => 100,
        'status' => OperationStatusEnum::Pending,
    ]);

    DB::transaction(fn () => app(WalletService::class)->addPendingDebit($user, 100, $withdrawRequest));

    $repository = app(PayoutRequestRepositoryInterface::class);

    expect($repository->existsForOperation($withdrawRequest))->toBeFalse();

    $this->actingAs($admin, 'admin')
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdrawRequest->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect();

    expect(PayoutRequest::query()
        ->where('operation_type', WithdrawRequest::class)
        ->where('operation_id', $withdrawRequest->id)
        ->count())->toBe(1)
        ->and($repository->existsForOperation($withdrawRequest))->toBeTrue()
        ->and($repository->findForOperation($withdrawRequest))->not->toBeNull();
});

test('CreatePayoutRequestAction refuses to create a second PayoutRequest for the same operation_type/operation_id, even if called directly bypassing the withdraw-approval guard', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    fundWallet($user, 250);
    $withdrawRequest = createWithdrawFor($user, [
        'amount' => 100,
        'status' => OperationStatusEnum::Pending,
    ]);

    DB::transaction(fn () => app(WalletService::class)->addPendingDebit($user, 100, $withdrawRequest));

    $this->actingAs($admin, 'admin')
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdrawRequest->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect();

    $action = app(CreatePayoutRequestAction::class);
    $data = new CreatePayoutRequestData(
        operation: $withdrawRequest->fresh(),
        recipient: $user,
        amount: 100.0,
        makerAdminId: $admin->id,
    );

    expect(fn () => $action->handle($data))
        ->toThrow(PayoutException::class, 'payout.already_exists_for_operation');
});

test('the database itself rejects a duplicate (operation_type, operation_id) pair at the unique-constraint level, independent of application logic', function () {
    $user = createWalletUser();
    $withdrawRequest = createWithdrawFor($user, ['amount' => 50]);

    $repository = app(PayoutRequestRepositoryInterface::class);

    $repository->create([
        'operation_type' => WithdrawRequest::class,
        'operation_id' => (string) $withdrawRequest->id,
        'recipient_type' => $user::class,
        'recipient_id' => $user->id,
        'amount' => 50,
        'status' => PayoutStatusEnum::Pending,
    ]);

    expect(fn () => $repository->create([
        'operation_type' => WithdrawRequest::class,
        'operation_id' => (string) $withdrawRequest->id,
        'recipient_type' => $user::class,
        'recipient_id' => $user->id,
        'amount' => 50,
        'status' => PayoutStatusEnum::Pending,
    ]))->toThrow(UniqueConstraintViolationException::class);
});

test('re-approving an already-approved withdraw still results in exactly one PayoutRequest (existing WalletException guard) — regression, must still pass', function () {
    withoutWalletLocaleMiddleware();
    $admin = createWalletAdmin();
    $user = createWalletUser();
    fundWallet($user, 250);
    $withdrawRequest = createWithdrawFor($user, [
        'amount' => 100,
        'status' => OperationStatusEnum::Pending,
    ]);

    DB::transaction(fn () => app(WalletService::class)->addPendingDebit($user, 100, $withdrawRequest));

    $this->actingAs($admin, 'admin')
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdrawRequest->id]), [
            'status' => OperationStatusEnum::Approved->value,
        ])->assertRedirect();

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardWithdrawRequestController::class, 'show'], ['withdrawRequest' => $withdrawRequest->id]))
        ->put(action([DashboardWithdrawRequestController::class, 'updateStatus'], ['withdrawRequest' => $withdrawRequest->id]), [
            'status' => OperationStatusEnum::Rejected->value,
        ])->assertRedirect()
        ->assertSessionHas('error', __('wallet.cannot_update_withdraw_request_status'));

    expect(PayoutRequest::query()
        ->where('operation_type', WithdrawRequest::class)
        ->where('operation_id', $withdrawRequest->id)
        ->count())->toBe(1);
});

test('a payout cannot be confirmed by the same admin who submitted it (shared guard)', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $submitter = createPayoutDashboardAdmin(['request payouts', 'confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Submitted,
        'gateway_reference' => 'BANK-TXN-SHARED-GUARD',
        'submitted_by_admin_id' => $submitter->id,
    ]);

    $this->actingAs($submitter, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]))
        ->assertRedirect()
        ->assertSessionHas('error', __('payout.submitter_cannot_review'));

    expect($payout->fresh()->status)->toBe(PayoutStatusEnum::Submitted);
});

test('a payout cannot be rejected by the same admin who submitted it (shared guard) — same underlying check as confirm, not a copy', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $submitter = createPayoutDashboardAdmin(['request payouts', 'confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Submitted,
        'gateway_reference' => 'BANK-TXN-SHARED-REJECT',
        'submitted_by_admin_id' => $submitter->id,
    ]);

    $this->actingAs($submitter, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'reject'], ['payoutRequest' => $payout->id]), [
            'failure_reason' => 'Should not be allowed',
        ])
        ->assertRedirect()
        ->assertSessionHas('error', __('payout.submitter_cannot_review'));

    expect($payout->fresh()->status)->toBe(PayoutStatusEnum::Submitted);
});

test('confirming a payout in a non-submitted status fails with cannot_confirm_status', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $reviewer = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($reviewer, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]))
        ->assertRedirect()
        ->assertSessionHas('error', __('payout.cannot_confirm_status'));
});

test('rejecting a payout in a non-submitted status fails with cannot_reject_status', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $reviewer = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($reviewer, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'reject'], ['payoutRequest' => $payout->id]), [
            'failure_reason' => 'Not submitted yet',
        ])
        ->assertRedirect()
        ->assertSessionHas('error', __('payout.cannot_reject_status'));
});

test('failing a payout in a non-pending status fails with cannot_fail_status', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $reviewer = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Submitted,
        'gateway_reference' => 'BANK-TXN-FAIL-GUARD',
    ]);

    $this->actingAs($reviewer, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'fail'], ['payoutRequest' => $payout->id]), [
            'failure_reason' => 'Cannot fail submitted payout',
        ])
        ->assertRedirect()
        ->assertSessionHas('error', __('payout.cannot_fail_status'));
});

test('submitting a payout in a non-pending/non-failed status fails with cannot_submit_status', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $admin = createPayoutDashboardAdmin(['request payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Submitted,
        'gateway_reference' => 'BANK-TXN-SUBMIT-GUARD',
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'submit'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-RETRY',
            'proof_image' => payoutTransferProofImage(),
        ])
        ->assertRedirect()
        ->assertSessionHas('error', __('payout.cannot_submit_status'));
});

test('amount_in_transfer is computed identically whether attached via Profile (AuthController) or Home (HomeController), via one shared Action', function () {
    withoutWalletLocaleMiddleware();

    $provider = createWalletProvider();
    $withdraw = createWithdrawFor($provider, ['amount' => 175]);

    PayoutRequest::factory()->create([
        'amount' => 100,
        'status' => PayoutStatusEnum::Pending,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => $withdraw->id,
        'recipient_type' => $provider::class,
        'recipient_id' => $provider->id,
    ]);

    PayoutRequest::factory()->create([
        'amount' => 75,
        'status' => PayoutStatusEnum::Submitted,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => WithdrawRequest::factory()->for($provider, 'user')->create()->id,
        'recipient_type' => $provider::class,
        'recipient_id' => $provider->id,
    ]);

    $provider->load('wallet');
    app(AttachAmountInTransferToWalletAction::class)->handle($provider);
    $expected = (float) $provider->wallet->amount_in_transfer;

    expect($expected)->toBe(175.0);

    $this->actingAs($provider, 'provider')
        ->get(action([AuthController::class, 'profile']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('provider.wallet.amount_in_transfer', 175)
        );

    $this->actingAs($provider, 'provider')
        ->get(route('provider.home'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('wallet.amount_in_transfer', 175)
        );
});

test('processing payouts appear in the dashboard processing filter tab', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $admin = createPayoutDashboardAdmin(['confirm payouts']);

    $processing = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Processing,
    ]);

    PayoutRequest::factory()->create(['status' => PayoutStatusEnum::Completed]);

    $this->actingAs($admin, 'admin')
        ->get(action([PayoutRequestController::class, 'index'], ['status' => 'processing']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/PayoutRequests/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $processing->id)
        );
});
