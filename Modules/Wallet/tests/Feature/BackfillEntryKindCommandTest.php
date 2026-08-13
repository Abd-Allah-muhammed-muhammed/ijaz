<?php

use App\Models\User;
use Illuminate\Support\Str;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Models\TopUpRequest;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Models\WithdrawRequest;

test('wallet:backfill-entry-kind command classifies existing rows correctly in --dry-run mode without writing anything', function () {
    $rows = seedUnstampedEntryKindShapes();

    $this->artisan('wallet:backfill-entry-kind', ['--dry-run' => true])
        ->expectsOutputToContain('withdraw_requested')
        ->expectsOutputToContain('withdraw_approved')
        ->expectsOutputToContain('withdraw_hold_released')
        ->expectsOutputToContain('withdraw_rejected')
        ->expectsOutputToContain('withdraw_cancelled')
        ->expectsOutputToContain('topup_credited')
        ->assertSuccessful();

    expect($rows['requested']->fresh()->entry_kind)->toBeNull()
        ->and($rows['approved']->fresh()->entry_kind)->toBeNull()
        ->and($rows['holdReleased']->fresh()->entry_kind)->toBeNull()
        ->and($rows['rejected']->fresh()->entry_kind)->toBeNull()
        ->and($rows['cancelled']->fresh()->entry_kind)->toBeNull()
        ->and($rows['topup']->fresh()->entry_kind)->toBeNull()
        ->and($rows['order']->fresh()->entry_kind)->toBeNull()
        ->and($rows['alreadyStamped']->fresh()->entry_kind)->toBe(WalletTransactionEntryKindEnum::WithdrawRequested);
});

test('wallet:backfill-entry-kind command writes entry_kind when run for real, and is safe to run twice (idempotent)', function () {
    $rows = seedUnstampedEntryKindShapes();

    $this->artisan('wallet:backfill-entry-kind')
        ->assertSuccessful();

    expect($rows['requested']->fresh()->entry_kind)->toBe(WalletTransactionEntryKindEnum::WithdrawRequested)
        ->and($rows['approved']->fresh()->entry_kind)->toBe(WalletTransactionEntryKindEnum::WithdrawApproved)
        ->and($rows['holdReleased']->fresh()->entry_kind)->toBe(WalletTransactionEntryKindEnum::WithdrawHoldReleased)
        ->and($rows['rejected']->fresh()->entry_kind)->toBe(WalletTransactionEntryKindEnum::WithdrawRejected)
        ->and($rows['cancelled']->fresh()->entry_kind)->toBe(WalletTransactionEntryKindEnum::WithdrawCancelled)
        ->and($rows['topup']->fresh()->entry_kind)->toBe(WalletTransactionEntryKindEnum::TopupCredited)
        ->and($rows['order']->fresh()->entry_kind)->toBeNull()
        ->and($rows['alreadyStamped']->fresh()->entry_kind)->toBe(WalletTransactionEntryKindEnum::WithdrawRequested);

    $this->artisan('wallet:backfill-entry-kind')
        ->assertSuccessful();

    expect($rows['requested']->fresh()->entry_kind)->toBe(WalletTransactionEntryKindEnum::WithdrawRequested)
        ->and($rows['approved']->fresh()->entry_kind)->toBe(WalletTransactionEntryKindEnum::WithdrawApproved)
        ->and($rows['holdReleased']->fresh()->entry_kind)->toBe(WalletTransactionEntryKindEnum::WithdrawHoldReleased)
        ->and($rows['rejected']->fresh()->entry_kind)->toBe(WalletTransactionEntryKindEnum::WithdrawRejected)
        ->and($rows['cancelled']->fresh()->entry_kind)->toBe(WalletTransactionEntryKindEnum::WithdrawCancelled)
        ->and($rows['topup']->fresh()->entry_kind)->toBe(WalletTransactionEntryKindEnum::TopupCredited)
        ->and($rows['order']->fresh()->entry_kind)->toBeNull()
        ->and($rows['alreadyStamped']->fresh()->entry_kind)->toBe(WalletTransactionEntryKindEnum::WithdrawRequested);
});

/**
 * @return array{
 *     requested: WalletTransaction,
 *     approved: WalletTransaction,
 *     holdReleased: WalletTransaction,
 *     rejected: WalletTransaction,
 *     cancelled: WalletTransaction,
 *     topup: WalletTransaction,
 *     order: WalletTransaction,
 *     alreadyStamped: WalletTransaction
 * }
 */
function seedUnstampedEntryKindShapes(): array
{
    $user = createWalletUser();
    fundWallet($user, 1);

    $requestedWithdraw = WithdrawRequest::factory()->for($user, 'user')->create();
    $approvedWithdraw = WithdrawRequest::factory()->for($user, 'user')->create();
    $rejectedWithdraw = WithdrawRequest::factory()->for($user, 'user')->create();
    $cancelledWithdraw = WithdrawRequest::factory()->for($user, 'user')->create();
    $topUp = TopUpRequest::factory()->for($user, 'user')->create();
    $stampedWithdraw = WithdrawRequest::factory()->for($user, 'user')->create();

    return [
        'requested' => insertUnstampedWalletLedger($user, [
            'pending_debit' => 50,
            'description' => 'Withdraw Request Created #'.$requestedWithdraw->id,
            'operation_id' => $requestedWithdraw->id,
        ]),
        'approved' => insertUnstampedWalletLedger($user, [
            'debit' => 80,
            'description' => 'Wallet withdraw for '.WithdrawRequest::class.' #'.$approvedWithdraw->id,
            'operation_id' => $approvedWithdraw->id,
        ]),
        'holdReleased' => insertUnstampedWalletLedger($user, [
            'pending_debit' => -80,
            'description' => 'Wallet withdraw for '.WithdrawRequest::class.' #'.$approvedWithdraw->id,
            'operation_id' => $approvedWithdraw->id,
        ]),
        'rejected' => insertUnstampedWalletLedger($user, [
            'pending_debit' => -40,
            'description' => 'Wallet withdraw for '.WithdrawRequest::class.' #'.$rejectedWithdraw->id,
            'operation_id' => $rejectedWithdraw->id,
        ]),
        'cancelled' => insertUnstampedWalletLedger($user, [
            'pending_debit' => -25,
            'description' => 'Withdraw Request Cancelled #'.$cancelledWithdraw->id,
            'operation_id' => $cancelledWithdraw->id,
        ]),
        'topup' => insertUnstampedWalletLedger($user, [
            'credit' => 150,
            'description' => 'Online top-up approved — TopUpRequest#'.$topUp->id,
            'operation_type' => TopUpRequest::class,
            'operation_id' => (string) $topUp->id,
        ]),
        'order' => insertUnstampedWalletLedger($user, [
            'credit' => 10,
            'description' => 'Order settlement #legacy-order',
            'operation_type' => 'Modules\\Orders\\Models\\Order',
            'operation_id' => (string) Str::uuid(),
        ]),
        'alreadyStamped' => insertUnstampedWalletLedger($user, [
            'pending_debit' => 15,
            'description' => 'Withdraw Request Created #'.$stampedWithdraw->id,
            'operation_id' => $stampedWithdraw->id,
            'entry_kind' => WalletTransactionEntryKindEnum::WithdrawRequested,
        ]),
    ];
}

/**
 * @param  array<string, mixed>  $attributes
 */
function insertUnstampedWalletLedger(User $user, array $attributes): WalletTransaction
{
    return WalletTransaction::query()->create([
        'wallet_id' => $user->wallet->id,
        'user_id' => $user->id,
        'user_type' => $user::class,
        'credit' => 0,
        'debit' => 0,
        'pending_credit' => 0,
        'pending_debit' => 0,
        'balance_before' => 0,
        'balance_after' => 0,
        'description' => null,
        'operation_type' => WithdrawRequest::class,
        'operation_id' => (string) Str::uuid(),
        'entry_kind' => null,
        ...$attributes,
    ]);
}
