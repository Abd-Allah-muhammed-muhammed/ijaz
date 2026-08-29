<?php

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\CarType;
use Modules\Classifieds\Enums\FuelTypeEnum;
use Modules\Classifieds\Enums\OperationEnum;
use Modules\Classifieds\Enums\TransmissionEnum;
use Modules\Classifieds\Enums\UsageStatusEnum;
use Modules\Classifieds\Http\Controllers\Api\V1\CarAdvisementController;
use Modules\Classifieds\Http\Controllers\Dashboard\CarAdvisementController as DashboardCarAdvisementController;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->carBrand = CarBrand::factory()->create();
    $this->carType = CarType::factory()->create();
    $this->carCategory = CarCategory::factory()->create();
    $this->region = Region::factory()->create();
    $this->city = City::factory()->create(['region_id' => $this->region->id]);
});

/**
 * @return array<string, mixed>
 */
function validCarAdPayload(object $ctx, array $overrides = []): array
{
    return array_merge([
        'title' => 'Enum Car',
        'description' => 'Valid car payload',
        'operation' => OperationEnum::SALE->value,
        'usage_status' => UsageStatusEnum::NEW->value,
        'car_brand_id' => $ctx->carBrand->id,
        'car_type_id' => $ctx->carType->id,
        'car_category_id' => $ctx->carCategory->id,
        'city_id' => $ctx->city->id,
        'region_id' => $ctx->region->id,
        'year' => 2020,
        'price' => 50000,
        'show_price' => true,
        'phone' => '966501234567',
    ], $overrides);
}

test('car ad creation accepts valid transmission values (e.g. automatic, manual) and rejects arbitrary strings like "test"', function () {
    Sanctum::actingAs($this->user);

    foreach (TransmissionEnum::cases() as $transmission) {
        $this->postJson(action([CarAdvisementController::class, 'store']), validCarAdPayload($this, [
            'transmission' => $transmission->value,
        ]))
            ->assertOk()
            ->assertJsonPath('data.transmission.value', $transmission->value)
            ->assertJsonPath('data.transmission.label', $transmission->label());
    }

    $this->postJson(action([CarAdvisementController::class, 'store']), validCarAdPayload($this, [
        'transmission' => 'test',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['transmission']);
});

test('car ad creation accepts valid fuel_type values (e.g. petrol, diesel, electric, hybrid) and rejects arbitrary strings', function () {
    Sanctum::actingAs($this->user);

    foreach (FuelTypeEnum::cases() as $fuelType) {
        $this->postJson(action([CarAdvisementController::class, 'store']), validCarAdPayload($this, [
            'fuel_type' => $fuelType->value,
        ]))
            ->assertOk()
            ->assertJsonPath('data.fuel_type.value', $fuelType->value)
            ->assertJsonPath('data.fuel_type.label', $fuelType->label());
    }

    $this->postJson(action([CarAdvisementController::class, 'store']), validCarAdPayload($this, [
        'fuel_type' => 'test',
    ]))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['fuel_type']);
});

test('both fields remain nullable/optional — omitting them still works, matching current behavior', function () {
    Sanctum::actingAs($this->user);

    $this->postJson(action([CarAdvisementController::class, 'store']), validCarAdPayload($this))
        ->assertOk()
        ->assertJsonPath('data.transmission', null)
        ->assertJsonPath('data.fuel_type', null);

    $this->assertDatabaseHas('car_advisements', [
        'user_id' => $this->user->id,
        'title' => 'Enum Car',
        'transmission' => null,
        'fuel_type' => null,
    ]);
});

test('existing car ads with old free-text junk values are unaffected by this validation change — this only applies to NEW submissions, no retroactive rejection/migration of existing rows', function () {
    $advisement = CarAdvisement::factory()->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'transmission' => TransmissionEnum::AUTOMATIC->value,
        'fuel_type' => FuelTypeEnum::PETROL->value,
    ]);

    DB::table('car_advisements')->where('id', $advisement->id)->update([
        'transmission' => 'test',
        'fuel_type' => 'test',
        'color' => 'red',
    ]);

    $advisement->refresh();

    expect($advisement->transmission)->toBe('test')
        ->and($advisement->fuel_type)->toBe('test')
        ->and($advisement->color)->toBe('red');

    Sanctum::actingAs($this->user);

    $this->getJson(action([CarAdvisementController::class, 'show'], $advisement))
        ->assertOk()
        ->assertJsonPath('data.transmission.value', 'test')
        ->assertJsonPath('data.transmission.label', 'test')
        ->assertJsonPath('data.fuel_type.value', 'test')
        ->assertJsonPath('data.fuel_type.label', 'test');
});

test('admin show page correctly displays the translated enum label for transmission/fuel_type, not a raw value', function () {
    withoutClassifiedsDashboardLocaleMiddleware();

    $admin = createClassifiedsDashboardAdmin(['show carAdvisements']);

    $advisement = CarAdvisement::factory()->create([
        'transmission' => TransmissionEnum::AUTOMATIC->value,
        'fuel_type' => FuelTypeEnum::ELECTRIC->value,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardCarAdvisementController::class, 'show'], $advisement))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/CarAdvisement/Show')
            ->where('row.transmission.value', TransmissionEnum::AUTOMATIC->value)
            ->where('row.transmission.label', TransmissionEnum::AUTOMATIC->label())
            ->where('row.fuel_type.value', FuelTypeEnum::ELECTRIC->value)
            ->where('row.fuel_type.label', FuelTypeEnum::ELECTRIC->label()));
});
