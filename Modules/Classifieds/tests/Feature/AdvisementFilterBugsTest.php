<?php

use App\Models\User;
use Illuminate\Http\Request;
use Laravel\Sanctum\Sanctum;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Catalog\Models\ElectronicBrand;
use Modules\Catalog\Models\Specialization;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\ElectronicConditionEnum;
use Modules\Classifieds\Enums\InstituteTypeEnum;
use Modules\Classifieds\Enums\StudyLevelEnum;
use Modules\Classifieds\Enums\StudyTypeEnum;
use Modules\Classifieds\Http\Controllers\Api\V1\CarAdvisementController;
use Modules\Classifieds\Http\Controllers\Api\V1\ElectronicAdvisementController;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Classifieds\QueryFilters\CarAdvisementFilters;
use Modules\Classifieds\QueryFilters\ElectronicAdvisementFilters;
use Modules\Classifieds\QueryFilters\InstituteAdvisementFilters;
use Modules\Classifieds\QueryFilters\PropertyAdvisementFilters;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

/**
 * Car's own-list / filter layer accepts $includeStatus but never applied
 * StatusFilter — Property/Electronic/Institute do. Dashboard repositories
 * filter status inline separately; this covers the dead QueryFilters flag
 * that left API status filtering silently ignored for cars.
 */
test('car advisement dashboard filter by status works', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    CarAdvisement::factory()->published()->create([
        'user_type' => User::class,
        'user_id' => $user->id,
    ]);
    CarAdvisement::factory()->pending()->count(2)->create([
        'user_type' => User::class,
        'user_id' => $user->id,
    ]);

    $this->getJson(action([CarAdvisementController::class, 'index'], ['status' => 'pending']))
        ->assertOk()
        ->assertJsonPath('data.total', 2);
});

/**
 * Property's PriceFilter already uses !== null. Car/Electronic/Institute use
 * PriceRangeFilter with truthy checks, so min_price=0 / max_price=0 were
 * treated as absent. Bounding both to 0 proves the bound was applied (only the
 * free listing remains) rather than ignored (both listings would remain).
 */
test('car/property/electronic/institute advisement filter accepts min_price=0', function (string $type) {
    $zeroId = createAdvisementWithPrice($type, 0.0)->id;
    $highId = createAdvisementWithPrice($type, 100.0)->id;

    $request = Request::create('/', 'GET', ['min_price' => 0, 'max_price' => 0]);
    $filters = match ($type) {
        'car' => new CarAdvisementFilters($request),
        'property' => new PropertyAdvisementFilters($request),
        'electronic' => new ElectronicAdvisementFilters($request),
        'institute' => new InstituteAdvisementFilters($request),
    };

    $model = match ($type) {
        'car' => CarAdvisement::class,
        'property' => PropertyAdvisement::class,
        'electronic' => ElectronicAdvisement::class,
        'institute' => InstituteAdvisement::class,
    };

    $ids = $filters->apply($model::query()->whereIn('id', [$zeroId, $highId]))
        ->pluck('id')
        ->all();

    expect($ids)->toHaveCount(1)
        ->and($ids)->toContain($zeroId)
        ->and($ids)->not->toContain($highId);
})->with(['car', 'property', 'electronic', 'institute']);

/**
 * Mobile car marketplace sends the generic `category_id` query param (same name
 * Property already uses), but CarAdvisementFilters historically only read
 * `car_category_id` — so category filtering silently had no effect.
 */
test('car advisements list filters by category_id as an alias for car_category_id', function () {
    $categoryA = CarCategory::factory()->create();
    $categoryB = CarCategory::factory()->create();

    CarAdvisement::factory()->published()->create(['car_category_id' => $categoryA->id]);
    CarAdvisement::factory()->published()->create(['car_category_id' => $categoryB->id]);

    $this->getJson(action([CarAdvisementController::class, 'all'], ['category_id' => $categoryA->id]))
        ->assertOk()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.items.0.car_category_id', $categoryA->id);
});

test('car advisements list still filters correctly when car_category_id is sent directly', function () {
    $categoryA = CarCategory::factory()->create();
    $categoryB = CarCategory::factory()->create();

    CarAdvisement::factory()->published()->create(['car_category_id' => $categoryA->id]);
    CarAdvisement::factory()->published()->create(['car_category_id' => $categoryB->id]);

    $this->getJson(action([CarAdvisementController::class, 'all'], ['car_category_id' => $categoryA->id]))
        ->assertOk()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.items.0.car_category_id', $categoryA->id);
});

test('car advisements prefer car_category_id when both category params are sent', function () {
    $categoryA = CarCategory::factory()->create();
    $categoryB = CarCategory::factory()->create();

    CarAdvisement::factory()->published()->create(['car_category_id' => $categoryA->id]);
    CarAdvisement::factory()->published()->create(['car_category_id' => $categoryB->id]);

    $this->getJson(action([CarAdvisementController::class, 'all'], [
        'car_category_id' => $categoryA->id,
        'category_id' => $categoryB->id,
    ]))
        ->assertOk()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.items.0.car_category_id', $categoryA->id);
});

/**
 * Same mobile mismatch risk as cars: electronics historically only read
 * `device_category_id`. Property already accepts `category_id` natively.
 */
test('electronic advisements list filters by category_id as an alias for device_category_id', function () {
    $categoryA = DeviceCategory::query()->create(['icon' => 'icons/a.png']);
    $categoryA->translateOrNew('en')->title = 'Phones';
    $categoryA->save();

    $categoryB = DeviceCategory::query()->create(['icon' => 'icons/b.png']);
    $categoryB->translateOrNew('en')->title = 'Laptops';
    $categoryB->save();

    createPublishedElectronicAdvisement(['device_category_id' => $categoryA->id]);
    createPublishedElectronicAdvisement(['device_category_id' => $categoryB->id]);

    $this->getJson(action([ElectronicAdvisementController::class, 'all'], ['category_id' => $categoryA->id]))
        ->assertOk()
        ->assertJsonPath('data.total', 1)
        ->assertJsonPath('data.items.0.device_category_id', $categoryA->id);
});

/**
 * @return CarAdvisement|PropertyAdvisement|ElectronicAdvisement|InstituteAdvisement
 */
function createAdvisementWithPrice(string $type, float $price): mixed
{
    return match ($type) {
        'car' => CarAdvisement::factory()->published()->create(['price' => $price]),
        'property' => PropertyAdvisement::factory()->published()->create(['price' => $price]),
        'electronic' => createPublishedElectronicAdvisement(['price' => $price]),
        'institute' => createPublishedInstituteAdvisement(['price' => $price]),
    };
}

/**
 * @param  array<string, mixed>  $attributes
 */
function createPublishedElectronicAdvisement(array $attributes = []): ElectronicAdvisement
{
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $category = DeviceCategory::query()->create(['icon' => 'icons/test.png']);
    $category->translateOrNew('en')->title = 'Phones';
    $category->save();
    $brand = ElectronicBrand::query()->create(['image' => 'brands/test.png', 'is_active' => true]);
    $brand->translateOrNew('en')->name = 'Test Brand';
    $brand->save();

    return ElectronicAdvisement::query()->create([
        'title' => 'Electronic item',
        'normalized_title' => 'electronic-item',
        'description' => 'A device',
        'normalized_description' => 'a-device',
        'image' => 'media/test.png',
        'status' => AdvisementStatusEnum::PUBLISHED,
        'condition' => ElectronicConditionEnum::NEW,
        'price' => $attributes['price'] ?? 50,
        'show_price' => true,
        'phone' => '966501234567',
        'user_type' => User::class,
        'user_id' => User::factory()->create()->id,
        'device_category_id' => $category->id,
        'electronic_brand_id' => $brand->id,
        'city_id' => $city->id,
        'region_id' => $region->id,
        'options' => [],
        ...$attributes,
    ]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function createPublishedInstituteAdvisement(array $attributes = []): InstituteAdvisement
{
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $specialization = Specialization::factory()->create();

    return InstituteAdvisement::query()->create([
        'title' => 'Institute course',
        'normalized_title' => 'institute-course',
        'description' => 'A course',
        'normalized_description' => 'a-course',
        'image' => 'media/test.png',
        'status' => AdvisementStatusEnum::PUBLISHED,
        'price' => $attributes['price'] ?? 50,
        'type' => InstituteTypeEnum::INSTITUTE,
        'study_type' => StudyTypeEnum::ONSITE,
        'study_level' => StudyLevelEnum::CERTIFICATE,
        'phone' => '966501234567',
        'user_type' => User::class,
        'user_id' => User::factory()->create()->id,
        'specialization_id' => $specialization->id,
        'city_id' => $city->id,
        'region_id' => $region->id,
        'options' => [],
        ...$attributes,
    ]);
}
