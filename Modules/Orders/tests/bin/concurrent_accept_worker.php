<?php

/**
 * Worker for concurrent order-offer accept race tests.
 *
 * Usage:
 *   php concurrent_accept_worker.php <user_id> <order_id> <offer_id>
 *
 * Exit codes: 0 = accepted, 3 = already accepted, 1 = other error
 * Stdout: OK | ALREADY_ACCEPTED | ERR:...
 */

declare(strict_types=1);

use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Orders\Actions\Offer\UpdateOfferStatusAction;
use Modules\Orders\DTOs\UpdateOfferStatusDTO;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Exceptions\OrdersException;
use Modules\Orders\Models\Order;
use Modules\Orders\Models\OrderOffer;

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
$orderId = (string) ($argv[2] ?? '');
$offerId = (string) ($argv[3] ?? '');

if ($userId <= 0 || $orderId === '' || $offerId === '') {
    fwrite(STDOUT, "ERR:invalid_args\n");
    exit(1);
}

$user = User::query()->findOrFail($userId);
$order = Order::query()->findOrFail($orderId);
$offer = OrderOffer::query()->findOrFail($offerId);

$attempts = 0;
$maxAttempts = 40;

while ($attempts < $maxAttempts) {
    $attempts++;

    try {
        app(UpdateOfferStatusAction::class)->handle(
            $order->fresh(),
            $offer->fresh(),
            $user,
            new UpdateOfferStatusDTO(status: OfferStatusEnum::Accepted),
        );
        fwrite(STDOUT, "OK\n");
        exit(0);
    } catch (OrdersException $e) {
        if ($e->getMessage() === 'order_already_has_accepted_offer') {
            fwrite(STDOUT, "ALREADY_ACCEPTED\n");
            exit(3);
        }

        fwrite(STDOUT, 'ERR:'.$e->getMessage()."\n");
        exit(1);
    } catch (QueryException $e) {
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
