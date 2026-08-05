<?php

use App\Models\User;
use Illuminate\Process\Pool;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Process;
use Modules\Wallet\Models\Wallet;
use Modules\Wallet\Models\WithdrawRequest;

/**
 * Genuine multi-process concurrency test.
 *
 * Setup + workers run as separate OS processes sharing one SQLite *file* DB so
 * the balance check / pending-debit write windows can overlap. The suite default
 * `:memory:` connection is never switched.
 */
test('concurrent withdrawal requests cannot cumulatively exceed available balance', function () {
    $dbPath = sys_get_temp_dir().DIRECTORY_SEPARATOR.'ijaz_withdraw_race_'.uniqid('', true).'.sqlite';
    $monitoringPath = $dbPath.'.monitoring';

    foreach ([$dbPath, $monitoringPath] as $path) {
        if (is_file($path)) {
            unlink($path);
        }
        touch($path);
    }

    $sharedEnv = [
        'APP_ENV' => 'local',
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => $dbPath,
        'DB_MONITORING_DRIVER' => 'sqlite',
        'DB_MONITORING_DATABASE' => $monitoringPath,
        'CACHE_STORE' => 'array',
        'QUEUE_CONNECTION' => 'sync',
    ];

    // Wait on SQLITE_BUSY instead of failing immediately under contention.
    config([
        'database.connections.sqlite.busy_timeout' => 10000,
        'database.connections.sqlite.journal_mode' => 'WAL',
    ]);

    try {
        $setup = Process::path(base_path())
            ->timeout(120)
            ->env($sharedEnv)
            ->run([
                PHP_BINARY,
                base_path('Modules/Wallet/tests/bin/concurrent_withdraw_setup.php'),
                '600',
            ]);

        expect($setup->successful())->toBeTrue(
            'Setup failed: '.$setup->output().$setup->errorOutput()
        );

        preg_match('/USER_ID=(\d+)/', $setup->output(), $matches);
        $userId = (int) ($matches[1] ?? 0);
        expect($userId)->toBeGreaterThan(0);

        $worker = base_path('Modules/Wallet/tests/bin/concurrent_withdraw_worker.php');

        $poolResults = Process::pool(function (Pool $pool) use ($worker, $sharedEnv, $userId): void {
            foreach (range(1, 5) as $i) {
                $pool->as("w{$i}")
                    ->path(base_path())
                    ->timeout(60)
                    ->env($sharedEnv)
                    ->command([PHP_BINARY, $worker, (string) $userId, '200']);
            }
        })->start()->wait();

        $ok = 0;
        $insufficient = 0;
        $errors = [];

        foreach ($poolResults->collect() as $name => $result) {
            $output = trim($result->output()."\n".$result->errorOutput());

            if (str_contains($output, 'OK') && ! str_contains($output, 'ERR:')) {
                $ok++;
            } elseif (str_contains($output, 'INSUFFICIENT')) {
                $insufficient++;
            } else {
                $errors[] = "{$name}: exit={$result->exitCode()} output=".json_encode($output);
            }
        }

        expect($errors)->toBeEmpty('Worker errors: '.implode('; ', $errors));

        expect($ok)->toBe(3)
            ->and($insufficient)->toBe(2)
            ->and($ok + $insufficient)->toBe(5);

        config([
            'database.connections.race' => [
                'driver' => 'sqlite',
                'database' => $dbPath,
                'prefix' => '',
                'foreign_key_constraints' => true,
            ],
        ]);

        $requestCount = WithdrawRequest::on('race')->where('user_id', $userId)->count();
        $pendingFromRequests = (float) WithdrawRequest::on('race')->where('user_id', $userId)->sum('amount');
        $wallet = Wallet::on('race')
            ->where('user_id', $userId)
            ->where('user_type', User::class)
            ->firstOrFail();

        expect($requestCount)->toBe(3)
            ->and($pendingFromRequests)->toBe(600.0)
            ->and((float) $wallet->pending_debit)->toBe(600.0)
            ->and((float) $wallet->balance)->toBe(600.0);
    } finally {
        DB::purge('race');
        foreach ([$dbPath, $monitoringPath] as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }
    }
});
