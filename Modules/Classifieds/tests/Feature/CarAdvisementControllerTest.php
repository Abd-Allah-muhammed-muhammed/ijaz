<?php

use App\Models\City;
use App\Models\Region;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\CarType;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\OperationEnum;
use Modules\Classifieds\Enums\UsageStatusEnum;
use Modules\Classifieds\Http\Controllers\V1\CarAdvisementController;
use Modules\Classifieds\Models\CarAdvisement;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->carBrand = CarBrand::factory()->create();
    $this->carType = CarType::factory()->create();
    $this->carCategory = CarCategory::factory()->create();
    $this->region = Region::factory()->create();
    $this->city = City::factory()->create(['region_id' => $this->region->id]);
});

it('returns 401 for unauthenticated user accessing own advisements', function () {
    $this->getJson(action([CarAdvisementController::class, 'index']))
        ->assertUnauthorized();
});

it('allows unauthenticated user to access all published advisements', function () {
    CarAdvisement::factory()->published()->count(3)->create();
    CarAdvisement::factory()->pending()->count(2)->create();

    $this->getJson(action([CarAdvisementController::class, 'all']))
        ->assertOk()
        ->assertJsonPath('data.total', 3);
});

it('can list own advisements', function () {
    Sanctum::actingAs($this->user);

    CarAdvisement::factory()->count(3)->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
    ]);

    $otherUser = User::factory()->create();
    CarAdvisement::factory()->count(2)->create([
        'user_type' => User::class,
        'user_id' => $otherUser->id,
    ]);

    $this->getJson(action([CarAdvisementController::class, 'index']))
        ->assertOk()
        ->assertJsonPath('data.total', 3)
        ->assertJsonStructure([
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'title',
                        'description',
                        'status',
                        'operation',
                        'usage_status',
                        'price',
                        'car_brand_id',
                        'car_type_id',
                        'car_category_id',
                        'city_id',
                        'region_id',
                    ],
                ],
                'total',
                'count',
                'per_page',
                'current_page',
                'last_page',
            ],
        ]);
});

it('can list all published advisements', function () {
    CarAdvisement::factory()->published()->count(5)->create();
    CarAdvisement::factory()->pending()->count(3)->create();
    CarAdvisement::factory()->create(['status' => AdvisementStatusEnum::REJECTED]);
    CarAdvisement::factory()->create(['status' => AdvisementStatusEnum::CLOSED]);

    $this->getJson(action([CarAdvisementController::class, 'all']))
        ->assertOk()
        ->assertJsonPath('data.total', 5);
});

it('can filter all advisements by operation', function () {
    CarAdvisement::factory()->published()->forSale()->count(3)->create();
    CarAdvisement::factory()->published()->forRent()->count(2)->create();

    $this->getJson(action([CarAdvisementController::class, 'all'], ['operation' => 'sale']))
        ->assertOk()
        ->assertJsonPath('data.total', 3);
});

it('can filter all advisements by usage status', function () {
    CarAdvisement::factory()->published()->newListing()->count(3)->create();
    CarAdvisement::factory()->published()->used()->count(2)->create();

    $this->getJson(action([CarAdvisementController::class, 'all'], ['usage_status' => 'new']))
        ->assertOk()
        ->assertJsonPath('data.total', 3);
});

it('can filter all advisements by car brand', function () {
    $brand1 = CarBrand::factory()->create();
    $brand2 = CarBrand::factory()->create();

    CarAdvisement::factory()->published()->count(3)->create(['car_brand_id' => $brand1->id]);
    CarAdvisement::factory()->published()->count(2)->create(['car_brand_id' => $brand2->id]);

    $this->getJson(action([CarAdvisementController::class, 'all'], ['car_brand_id' => $brand1->id]))
        ->assertOk()
        ->assertJsonPath('data.total', 3);
});

it('can filter all advisements by price range', function () {
    CarAdvisement::factory()->published()->create(['price' => 100000]);
    CarAdvisement::factory()->published()->create(['price' => 200000]);
    CarAdvisement::factory()->published()->create(['price' => 300000]);
    CarAdvisement::factory()->published()->create(['price' => 400000]);

    $this->getJson(action([CarAdvisementController::class, 'all'], ['min_price' => 150000, 'max_price' => 350000]))
        ->assertOk()
        ->assertJsonPath('data.total', 2);
});

it('can filter all advisements by year range', function () {
    CarAdvisement::factory()->published()->create(['year' => 2015]);
    CarAdvisement::factory()->published()->create(['year' => 2018]);
    CarAdvisement::factory()->published()->create(['year' => 2020]);
    CarAdvisement::factory()->published()->create(['year' => 2023]);

    $this->getJson(action([CarAdvisementController::class, 'all'], ['min_year' => 2017, 'max_year' => 2021]))
        ->assertOk()
        ->assertJsonPath('data.total', 2);
});

it('can search advisements by title', function () {
    CarAdvisement::factory()->published()->create(['title' => 'Toyota Camry for Sale']);
    CarAdvisement::factory()->published()->create(['title' => 'Honda Civic']);
    CarAdvisement::factory()->published()->create(['title' => 'Ford Mustang']);

    $this->getJson(action([CarAdvisementController::class, 'all'], ['search' => 'Toyota']))
        ->assertOk()
        ->assertJsonPath('data.total', 1);
});

it('can create advisement', function () {
    Sanctum::actingAs($this->user);

    $data = [
        'title' => 'Test Car',
        'description' => 'A beautiful car for sale',
        'operation' => OperationEnum::SALE->value,
        'usage_status' => UsageStatusEnum::NEW->value,
        'car_brand_id' => $this->carBrand->id,
        'car_type_id' => $this->carType->id,
        'car_category_id' => $this->carCategory->id,
        'city_id' => $this->city->id,
        'region_id' => $this->region->id,
        'year' => 2020,
        'price' => 500000,
        'show_price' => true,
        'phone' => '966501234567',
    ];

    $this->postJson(action([CarAdvisementController::class, 'store']), $data)
        ->assertOk()
        ->assertJsonPath('data.title', 'Test Car')
        ->assertJsonPath('data.status.value', AdvisementStatusEnum::PENDING->value)
        ->assertJsonPath('data.operation.value', OperationEnum::SALE->value)
        ->assertJsonPath('data.usage_status.value', UsageStatusEnum::NEW->value);

    $this->assertDatabaseHas('car_advisements', [
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'title' => 'Test Car',
        'status' => AdvisementStatusEnum::PENDING->value,
    ]);
});

it('validates required fields on create', function () {
    Sanctum::actingAs($this->user);

    $this->postJson(action([CarAdvisementController::class, 'store']), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'title',
            'description',
            'operation',
            'usage_status',
            'car_brand_id',
            'car_type_id',
            'city_id',
            'region_id',
            'year',
            'price',
        ]);
});

it('can show advisement', function () {
    $advisement = CarAdvisement::factory()->create();

    $this->getJson(action([CarAdvisementController::class, 'show'], $advisement))
        ->assertOk()
        ->assertJsonPath('data.id', $advisement->id)
        ->assertJsonPath('data.title', $advisement->title);
});

it('can update own advisement', function () {
    Sanctum::actingAs($this->user);

    $advisement = CarAdvisement::factory()->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'title' => 'Original Title',
    ]);

    $data = [
        'title' => 'Updated Title',
        'description' => 'Updated description',
        'operation' => OperationEnum::RENT->value,
        'usage_status' => UsageStatusEnum::USED->value,
        'car_brand_id' => $this->carBrand->id,
        'car_type_id' => $this->carType->id,
        'city_id' => $this->city->id,
        'region_id' => $this->region->id,
        'year' => 2019,
        'price' => 1000,
    ];

    $this->postJson(action([CarAdvisementController::class, 'edit'], $advisement), $data)
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated Title')
        ->assertJsonPath('data.operation.value', OperationEnum::RENT->value)
        ->assertJsonPath('data.usage_status.value', UsageStatusEnum::USED->value);

    $this->assertDatabaseHas('car_advisements', [
        'id' => $advisement->id,
        'title' => 'Updated Title',
    ]);
});

it('cannot update others advisement', function () {
    Sanctum::actingAs($this->user);

    $otherUser = User::factory()->create();
    $advisement = CarAdvisement::factory()->create([
        'user_type' => User::class,
        'user_id' => $otherUser->id,
    ]);

    $data = [
        'title' => 'Hacked Title',
        'description' => 'Hacked description',
        'operation' => OperationEnum::SALE->value,
        'usage_status' => UsageStatusEnum::NEW->value,
        'car_brand_id' => $this->carBrand->id,
        'car_type_id' => $this->carType->id,
        'city_id' => $this->city->id,
        'region_id' => $this->region->id,
        'year' => 2020,
        'price' => 1000,
    ];

    $this->postJson(action([CarAdvisementController::class, 'edit'], $advisement), $data)
        ->assertForbidden()
        ->assertJson([
            'message' => 'forbidden !!',
        ]);
});

it('can delete own advisement', function () {
    Sanctum::actingAs($this->user);

    $advisement = CarAdvisement::factory()->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
    ]);

    $this->deleteJson(action([CarAdvisementController::class, 'destroy'], $advisement))
        ->assertOk()
        ->assertJson([
            'message' => 'data deleted successfully',
        ]);

    $this->assertDatabaseMissing('car_advisements', [
        'id' => $advisement->id,
    ]);
});

it('cannot delete others advisement', function () {
    Sanctum::actingAs($this->user);

    $otherUser = User::factory()->create();
    $advisement = CarAdvisement::factory()->create([
        'user_type' => User::class,
        'user_id' => $otherUser->id,
    ]);

    $this->deleteJson(action([CarAdvisementController::class, 'destroy'], $advisement))
        ->assertForbidden()
        ->assertJson([
            'message' => 'forbidden !!',
        ]);

    $this->assertDatabaseHas('car_advisements', [
        'id' => $advisement->id,
    ]);
});

it('paginates advisements', function () {
    Sanctum::actingAs($this->user);

    CarAdvisement::factory()->count(25)->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
    ]);

    $this->getJson(action([CarAdvisementController::class, 'index'], ['per_page' => 10]))
        ->assertOk()
        ->assertJsonPath('data.per_page', 10)
        ->assertJsonPath('data.count', 10)
        ->assertJsonPath('data.total', 25)
        ->assertJsonPath('data.last_page', 3);
});
