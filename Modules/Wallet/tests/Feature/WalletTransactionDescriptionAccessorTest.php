<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Enums\WalletTransactionEntryKindEnum;
use Modules\Wallet\Models\WalletTransaction;
use Modules\Wallet\Services\WalletService;
use Modules\Wallet\Services\WithdrawRequestService;

test('a withdraw_requested row stores "wallet.entry_kind.withdraw_requested" as its raw description column value', function () {
    $user = createWalletUser();
    fundWallet($user, 400);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 200, userNotes: null),
    );

    $row = WalletTransaction::query()
        ->where('operation_id', $withdraw->id)
        ->where('entry_kind', WalletTransactionEntryKindEnum::WithdrawRequested)
        ->sole();

    expect($row->getRawOriginal('description'))->toBe('wallet.entry_kind.withdraw_requested');
});

test('reading ->description on that model returns the translated string in the current app locale, with the correct ref number interpolated', function () {
    $user = createWalletUser();
    fundWallet($user, 400);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 200, userNotes: null),
    );

    $row = WalletTransaction::query()
        ->where('operation_id', $withdraw->id)
        ->where('entry_kind', WalletTransactionEntryKindEnum::WithdrawRequested)
        ->sole();

    $ref = strtoupper(substr((string) $withdraw->id, -8));
    app()->setLocale('en');

    expect($row->fresh()->description)
        ->toBe(__('wallet.entry_kind.withdraw_requested', ['ref' => $ref], 'en'))
        ->toContain($ref)
        ->not->toBe('wallet.entry_kind.withdraw_requested');
});

test('the same model returns a different translated string when the locale changes', function () {
    $user = createWalletUser();
    fundWallet($user, 400);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 200, userNotes: null),
    );

    $row = WalletTransaction::query()
        ->where('operation_id', $withdraw->id)
        ->where('entry_kind', WalletTransactionEntryKindEnum::WithdrawRequested)
        ->sole();

    $ref = strtoupper(substr((string) $withdraw->id, -8));

    app()->setLocale('en');
    $english = $row->fresh()->description;

    app()->setLocale('ar');
    $arabic = $row->fresh()->description;

    expect($english)->toBe(__('wallet.entry_kind.withdraw_requested', ['ref' => $ref], 'en'))
        ->and($arabic)->toBe(__('wallet.entry_kind.withdraw_requested', ['ref' => $ref], 'ar'))
        ->and($english)->not->toBe($arabic)
        ->and($english)->toContain($ref)
        ->and($arabic)->toContain($ref);
});

test('a row with entry_kind null (Orders/Guarantor/bonus) — whose description column holds an ordinary human-readable string, not a translation key — returns that string unchanged via the accessor (must not attempt to trans() a non-key string)', function () {
    $user = createWalletUser();
    fundWallet($user, 100);

    $orderRow = WalletTransaction::query()->create([
        'wallet_id' => $user->wallet->id,
        'user_id' => $user->id,
        'user_type' => $user::class,
        'credit' => 25,
        'description' => 'Order settlement for completed job',
        'operation_type' => 'Modules\\Orders\\Models\\Order',
        'operation_id' => (string) Str::uuid(),
        'entry_kind' => null,
    ]);

    // Real JSON key: if the accessor wrongly called trans(), this would become "Withdraw Request".
    $looksLikeAKey = WalletTransaction::query()->create([
        'wallet_id' => $user->wallet->id,
        'user_id' => $user->id,
        'user_type' => $user::class,
        'credit' => 5,
        'description' => 'withdraw_request',
        'operation_type' => 'Modules\\Orders\\Models\\Order',
        'operation_id' => (string) Str::uuid(),
        'entry_kind' => null,
    ]);

    app()->setLocale('en');

    expect($orderRow->fresh()->description)->toBe('Order settlement for completed job')
        ->and($looksLikeAKey->fresh()->description)->toBe('withdraw_request')
        ->and($looksLikeAKey->fresh()->description)->not->toBe(__('withdraw_request', [], 'en'));
});

test('WithdrawHoldReleased rows still work correctly (internal only, never shown on mobile — confirm the accessor doesn\'t break anything for them)', function () {
    $user = createWalletUser();
    fundWallet($user, 400);
    $withdraw = app(WithdrawRequestService::class)->create(
        $user,
        new CreateWithdrawData(amount: 200, userNotes: null),
    );

    DB::transaction(fn () => app(WalletService::class)->finalizeWithdraw($user, $withdraw, approved: true));

    $holdReleased = WalletTransaction::query()
        ->where('operation_id', $withdraw->id)
        ->where('entry_kind', WalletTransactionEntryKindEnum::WithdrawHoldReleased)
        ->sole();

    $raw = $holdReleased->getRawOriginal('description');

    expect($holdReleased->entry_kind)->toBe(WalletTransactionEntryKindEnum::WithdrawHoldReleased)
        ->and($holdReleased->description)->toBe($raw)
        ->and($holdReleased->description)->not->toContain('Ref #')
        ->and($holdReleased->description)->not->toContain(strtoupper(substr((string) $withdraw->id, -8)));
});
