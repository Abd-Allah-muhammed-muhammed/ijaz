<?php

/**
 * Worker for concurrent withdraw race tests.
 *
 * Invoked as a separate PHP process so multiple workers share one SQLite file DB.
 *
 * Usage:
 *   php concurrent_withdraw_worker.php <user_id> <amount>
 *
 * Env (required):
 *   DB_CONNECTION=sqlite
 *   DB_DATABASE=/absolute/path/to/shared.sqlite
 *
 * Exit codes: 0 = created, 2 = insufficient balance, 1 = other error
 * Stdout: OK | INSUFFICIENT | ERR:...
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\DTOs\CreateWithdrawData;
use Modules\Wallet\Exceptions\InsufficientBalanceException;
use Modules\Wallet\Services\WithdrawRequestService;

require dirname(__DIR__, 4).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$app = require dirname(__DIR__, 4).DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php';
$app->make(Kernel::class)->bootstrap();

config([
    'database.connections.sqlite.busy_timeout' => 10000,
    'database.connections.sqlite.journal_mode' => 'wal',
]);
DB::purge('sqlite');
DB::reconnect('sqlite');
DB::statement('PRAGMA busy_timeout=10000;');

$userId = (int) ($argv[1] ?? 0);
$amount = (float) ($argv[2] ?? 0);

if ($userId <= 0 || $amount <= 0) {
    fwrite(STDOUT, "ERR:invalid_args\n");
    exit(1);
}

$user = User::query()->findOrFail($userId);
$data = new CreateWithdrawData(amount: $amount, userNotes: 'concurrent-race-worker');

$attempts = 0;
$maxAttempts = 40;

while ($attempts < $maxAttempts) {
    $attempts++;

    try {
        app(WithdrawRequestService::class)->create($user, $data);
        fwrite(STDOUT, "OK\n");
        exit(0);
    } catch (InsufficientBalanceException) {
        fwrite(STDOUT, "INSUFFICIENT\n");
        exit(2);
    } catch (QueryException $e) {
        // SQLite returns SQLITE_BUSY under write contention; retry so the atomic
        // pending_debit CAS can run. Over-withdrawal is still prevented by CAS.
        if (str_contains($e->getMessage(), 'database is locked') || str_contains($e->getMessage(), 'SQLITE_BUSY')) {
            usleep(25_000 * $attempts);

            continue;
        }

        fwrite(STDOUT, 'ERR:'.$e->getMessage()."\n");
        exit(1);
    } catch (Throwable $e) {
        fwrite(STDOUT, 'ERR:'.$e->getMessage()."\n");
        exit(1);
    }
}

fwrite(STDOUT, "ERR:exhausted_retries_database_locked\n");
exit(1);
