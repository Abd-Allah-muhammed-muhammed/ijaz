<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Wallet\Actions\ReversePendingDebitAction;
use Modules\Wallet\Models\WithdrawRequest;

/**
 * pending_credit / pending_debit on wallet_transactions store signed ledger
 * deltas (reversals write negative amounts). A prior migration incorrectly
 * changed them to unsignedBigInteger — MySQL then 500s on withdraw approval
 * while SQLite-based Pest never notices (SQLite does not enforce UNSIGNED).
 */
test('wallet_transactions pending columns are signed decimals after migrations', function () {
    foreach (['pending_credit', 'pending_debit'] as $column) {
        $meta = collect(Schema::getColumns('wallet_transactions'))
            ->firstWhere('name', $column);

        expect($meta)->not->toBeNull();

        $type = strtolower((string) ($meta['type'] ?? ''));
        $typeName = strtolower((string) ($meta['type_name'] ?? ''));
        $combined = $type.' '.$typeName;

        expect($combined)->toMatch('/decimal|numeric/')
            ->and($combined)->not->toContain('unsigned');

        if (array_key_exists('unsigned', $meta)) {
            expect($meta['unsigned'])->toBeFalse();
        }
    }

    if (DB::getDriverName() === 'mysql') {
        foreach (['pending_credit', 'pending_debit'] as $column) {
            $row = DB::selectOne('SHOW COLUMNS FROM wallet_transactions LIKE ?', [$column]);

            expect($row)->not->toBeNull();

            $mysqlType = strtolower((string) $row->Type);

            expect($mysqlType)->toStartWith('decimal')
                ->and($mysqlType)->not->toContain('unsigned');
        }
    }
});

test('reverse pending debit persists a negative pending_debit ledger delta', function () {
    $user = createWalletUser();
    $user->wallet->update(['pending_debit' => 40]);
    $operation = WithdrawRequest::factory()->for($user, 'user')->create(['amount' => 40]);

    DB::transaction(fn () => app(ReversePendingDebitAction::class)->handle($user, 40, $operation));

    $pendingDebit = (float) DB::table('wallet_transactions')
        ->where('wallet_id', $user->wallet->id)
        ->value('pending_debit');

    expect($pendingDebit)->toBe(-40.0);
});
