<?php

/**
 * One-shot setup for concurrent withdraw race tests.
 *
 * Boots Laravel against the shared SQLite file, migrates, seeds a funded user,
 * prints USER_ID=<id> on success.
 *
 * Usage: php concurrent_withdraw_setup.php <amount>
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Settings\Models\Setting;
use Modules\Wallet\Services\WalletService;

require dirname(__DIR__, 4).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$app = require dirname(__DIR__, 4).DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php';
$app->make(Kernel::class)->bootstrap();

$amount = (float) ($argv[1] ?? 600);

try {
    Artisan::call('migrate', ['--force' => true]);

    DB::statement('PRAGMA journal_mode=WAL;');
    DB::statement('PRAGMA busy_timeout=10000;');

    // Mirror helpers without depending on Pest helpers.php
    $user = User::factory()->create();

    DB::transaction(function () use ($user, $amount): void {
        app(WalletService::class)->credit($user, $amount, $user, 'concurrent-race fund');
    });

    Setting::query()->updateOrCreate(
        ['key' => 'min_withdraw_amount'],
        ['content' => '50'],
    );

    fwrite(STDOUT, 'USER_ID='.$user->id."\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDOUT, 'ERR:'.$e->getMessage()."\n");
    exit(1);
}
