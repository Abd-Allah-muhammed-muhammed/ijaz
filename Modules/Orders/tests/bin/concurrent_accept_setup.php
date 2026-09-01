<?php

/**
 * One-shot setup for concurrent order-offer accept race tests.
 *
 * Boots Laravel against a shared SQLite file, migrates, seeds one order with
 * two pending offers, prints IDs on success.
 */

declare(strict_types=1);

use App\Enums\Providers\ProviderStatusEnum;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Modules\Marketplace\Enums\CategoryFeesTypeEnum;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\ProviderType;
use Modules\Orders\Database\Factories\OrderOfferFactory;
use Modules\Orders\Enums\OfferStatusEnum;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;
use Modules\Settings\Models\Setting;

require dirname(__DIR__, 4).DIRECTORY_SEPARATOR.'vendor'.DIRECTORY_SEPARATOR.'autoload.php';

$app = require dirname(__DIR__, 4).DIRECTORY_SEPARATOR.'bootstrap'.DIRECTORY_SEPARATOR.'app.php';
$app->make(Kernel::class)->bootstrap();

function createConcurrentAcceptProvider(): Provider
{
    $providerType = ProviderType::query()->create(['image' => 'media/test-type.png']);
    $providerType->translations()->create([
        'locale' => 'en',
        'name' => 'Concurrent Accept Provider Type',
    ]);
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);

    return Provider::query()->create([
        'name' => fake()->company(),
        'iban' => fake()->unique()->iban('SA'),
        'logo' => 'media/test-logo.png',
        'provider_type_id' => $providerType->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
        'password' => 'password',
        'status' => ProviderStatusEnum::Approved,
        'language' => 'en',
    ]);
}

try {
    Artisan::call('migrate', ['--force' => true]);

    DB::statement('PRAGMA journal_mode=WAL;');
    DB::statement('PRAGMA busy_timeout=10000;');

    Setting::query()->updateOrCreate(
        ['key' => 'testing_fees'],
        ['content' => '20'],
    );

    $owner = User::factory()->create();
    $providerA = createConcurrentAcceptProvider();
    $providerB = createConcurrentAcceptProvider();
    $category = Category::factory()->create([
        'fees' => 10.0,
        'fees_type' => CategoryFeesTypeEnum::FIXED,
    ]);

    $order = Order::factory()->create([
        'user_id' => $owner->id,
        'category_id' => $category->id,
        'status' => OrderStatusEnum::New,
        'provider_id' => null,
        'accepted_offer_id' => null,
        'price' => 0,
        'user_fees' => 0,
        'provider_fees' => 0,
    ]);

    $offerA = OrderOfferFactory::new()
        ->forOrder($order)
        ->forProvider($providerA)
        ->create([
            'price' => 200.0,
            'description' => 'Offer A',
            'status' => OfferStatusEnum::Pending,
        ]);

    $offerB = OrderOfferFactory::new()
        ->forOrder($order)
        ->forProvider($providerB)
        ->create([
            'price' => 210.0,
            'description' => 'Offer B',
            'status' => OfferStatusEnum::Pending,
        ]);

    fwrite(STDOUT, 'USER_ID='.$owner->id."\n");
    fwrite(STDOUT, 'ORDER_ID='.$order->id."\n");
    fwrite(STDOUT, 'OFFER_A_ID='.$offerA->id."\n");
    fwrite(STDOUT, 'OFFER_B_ID='.$offerB->id."\n");
    exit(0);
} catch (Throwable $e) {
    fwrite(STDOUT, 'ERR:'.$e->getMessage()."\n");
    exit(1);
}
