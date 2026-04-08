<?php

use App\Enums\Advisements\AdvisementStatusEnum;
use App\Enums\Advisements\OperationEnum;
use App\Http\Controllers\Api\V1\PropertyAdvisementController;
use App\Models\City;
use App\Models\PropertiyCategory;
use App\Models\PropertyAdvisement;
use App\Models\PropertyType;
use App\Models\Region;
use App\Models\User;
use Laravel\Sanctum\Sanctum;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->propertyType = PropertyType::factory()->create();
    $this->region = Region::factory()->create();
    $this->city = City::factory()->create(['region_id' => $this->region->id]);
    $this->category = PropertiyCategory::factory()->create();
});

it('returns 401 for unauthenticated user accessing own advisements', function () {
    $this->getJson(action([PropertyAdvisementController::class, 'index']))
        ->assertUnauthorized();
});

it('allows unauthenticated user to access all published advisements', function () {
    PropertyAdvisement::factory()->published()->count(3)->create();
    PropertyAdvisement::factory()->pending()->count(2)->create();

    $this->getJson(action([PropertyAdvisementController::class, 'all']))
        ->assertOk()
        ->assertJsonPath('data.total', 3);
});

it('can list own advisements', function () {
    Sanctum::actingAs($this->user);

    PropertyAdvisement::factory()->count(3)->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
    ]);

    $otherUser = User::factory()->create();
    PropertyAdvisement::factory()->count(2)->create([
        'user_type' => User::class,
        'user_id' => $otherUser->id,
    ]);

    $this->getJson(action([PropertyAdvisementController::class, 'index']))
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
                        'price',
                        'property_type_id',
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
    PropertyAdvisement::factory()->published()->count(5)->create();
    PropertyAdvisement::factory()->pending()->count(3)->create();
    PropertyAdvisement::factory()->create(['status' => AdvisementStatusEnum::REJECTED]);
    PropertyAdvisement::factory()->create(['status' => AdvisementStatusEnum::CLOSED]);

    $this->getJson(action([PropertyAdvisementController::class, 'all']))
        ->assertOk()
        ->assertJsonPath('data.total', 5);
});

it('can filter all advisements by operation', function () {
    PropertyAdvisement::factory()->published()->forSale()->count(3)->create();
    PropertyAdvisement::factory()->published()->forRent()->count(2)->create();

    $this->getJson(action([PropertyAdvisementController::class, 'all'], ['operation' => 'sale']))
        ->assertOk()
        ->assertJsonPath('data.total', 3);
});

it('can filter all advisements by price range', function () {
    PropertyAdvisement::factory()->published()->create(['price' => 100000]);
    PropertyAdvisement::factory()->published()->create(['price' => 200000]);
    PropertyAdvisement::factory()->published()->create(['price' => 300000]);
    PropertyAdvisement::factory()->published()->create(['price' => 400000]);

    $this->getJson(action([PropertyAdvisementController::class, 'all'], ['min_price' => 150000, 'max_price' => 350000]))
        ->assertOk()
        ->assertJsonPath('data.total', 2);
});

it('can search advisements by title', function () {
    PropertyAdvisement::factory()->published()->create(['title' => 'Beautiful Villa for Sale']);
    PropertyAdvisement::factory()->published()->create(['title' => 'Modern Apartment']);
    PropertyAdvisement::factory()->published()->create(['title' => 'Cozy Studio']);

    $this->getJson(action([PropertyAdvisementController::class, 'all'], ['search' => 'Villa']))
        ->assertOk()
        ->assertJsonPath('data.total', 1);
});

it('can create advisement', function () {
    Sanctum::actingAs($this->user);

    $data = [
        'title' => 'Test Property',
        'description' => 'A beautiful property for sale',
        'operation' => OperationEnum::SALE->value,
        'property_type_id' => $this->propertyType->id,
        'city_id' => $this->city->id,
        'region_id' => $this->region->id,
        'category_id' => $this->category->id,
        'price' => 500000,
        'show_price' => true,
        'area' => 200,
        'bedrooms_count' => 4,
        'bathrooms_count' => 2,
        'phone' => '966501234567',
    ];

    $this->postJson(action([PropertyAdvisementController::class, 'store']), $data)
        ->assertOk()
        ->assertJsonPath('data.title', 'Test Property')
        ->assertJsonPath('data.status', AdvisementStatusEnum::PENDING->value)
        ->assertJsonPath('data.operation', OperationEnum::SALE->value);

    $this->assertDatabaseHas('property_advisements', [
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'title' => 'Test Property',
        'status' => AdvisementStatusEnum::PENDING->value,
    ]);
});

it('validates required fields on create', function () {
    Sanctum::actingAs($this->user);

    $this->postJson(action([PropertyAdvisementController::class, 'store']), [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors([
            'title',
            'description',
            'operation',
            'property_type_id',
            'city_id',
            'region_id',
            'price',
        ]);
});

it('can show advisement', function () {
    Sanctum::actingAs($this->user);

    $advisement = PropertyAdvisement::factory()->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
    ]);

    $this->getJson(action([PropertyAdvisementController::class, 'show'], $advisement))
        ->assertOk()
        ->assertJsonPath('data.id', $advisement->id)
        ->assertJsonPath('data.title', $advisement->title);
});

it('can update own advisement', function () {
    Sanctum::actingAs($this->user);

    $advisement = PropertyAdvisement::factory()->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'title' => 'Original Title',
    ]);

    $data = [
        'title' => 'Updated Title',
        'description' => 'Updated description',
        'operation' => OperationEnum::RENT->value,
        'property_type_id' => $this->propertyType->id,
        'city_id' => $this->city->id,
        'region_id' => $this->region->id,
        'price' => 1000,
    ];

    $this->postJson(action([PropertyAdvisementController::class, 'edit'], $advisement), $data)
        ->assertOk()
        ->assertJsonPath('data.title', 'Updated Title')
        ->assertJsonPath('data.operation', OperationEnum::RENT->value);

    $this->assertDatabaseHas('property_advisements', [
        'id' => $advisement->id,
        'title' => 'Updated Title',
    ]);
});

it('cannot update others advisement', function () {
    Sanctum::actingAs($this->user);

    $otherUser = User::factory()->create();
    $advisement = PropertyAdvisement::factory()->create([
        'user_type' => User::class,
        'user_id' => $otherUser->id,
    ]);

    $data = [
        'title' => 'Hacked Title',
        'description' => 'Hacked description',
        'operation' => OperationEnum::SALE->value,
        'property_type_id' => $this->propertyType->id,
        'city_id' => $this->city->id,
        'region_id' => $this->region->id,
        'price' => 1000,
    ];

    $this->postJson(action([PropertyAdvisementController::class, 'edit'], $advisement), $data)
        ->assertForbidden()
        ->assertJson([
            'message' => 'forbidden !!',
        ]);
});

it('can delete own advisement', function () {
    Sanctum::actingAs($this->user);

    $advisement = PropertyAdvisement::factory()->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
    ]);

    $this->deleteJson(action([PropertyAdvisementController::class, 'destroy'], $advisement))
        ->assertOk()
        ->assertJson([
            'message' => 'data deleted successfully',
        ]);

    $this->assertDatabaseMissing('property_advisements', [
        'id' => $advisement->id,
    ]);
});

it('cannot delete others advisement', function () {
    Sanctum::actingAs($this->user);

    $otherUser = User::factory()->create();
    $advisement = PropertyAdvisement::factory()->create([
        'user_type' => User::class,
        'user_id' => $otherUser->id,
    ]);

    $this->deleteJson(action([PropertyAdvisementController::class, 'destroy'], $advisement))
        ->assertForbidden()
        ->assertJson([
            'message' => 'forbidden !!',
        ]);

    $this->assertDatabaseHas('property_advisements', [
        'id' => $advisement->id,
    ]);
});

it('can filter own advisements by status', function () {
    Sanctum::actingAs($this->user);

    PropertyAdvisement::factory()->published()->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
    ]);
    PropertyAdvisement::factory()->pending()->count(2)->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
    ]);

    $this->getJson(action([PropertyAdvisementController::class, 'index'], ['status' => 'pending']))
        ->assertOk()
        ->assertJsonPath('data.total', 2);
});

it('paginates advisements', function () {
    Sanctum::actingAs($this->user);

    PropertyAdvisement::factory()->count(25)->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
    ]);

    $this->getJson(action([PropertyAdvisementController::class, 'index'], ['per_page' => 10]))
        ->assertOk()
        ->assertJsonPath('data.per_page', 10)
        ->assertJsonPath('data.total', 25)
        ->assertJsonCount(10, 'data.items');
});

it('orders advisements by latest first', function () {
    Sanctum::actingAs($this->user);

    $oldAdvisement = PropertyAdvisement::factory()->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'created_at' => now()->subDays(2),
    ]);

    $newAdvisement = PropertyAdvisement::factory()->create([
        'user_type' => User::class,
        'user_id' => $this->user->id,
        'created_at' => now(),
    ]);

    $response = $this->getJson(action([PropertyAdvisementController::class, 'index']));

    $response->assertOk();

    $items = $response->json('data.items');
    expect($items[0]['id'])->toBe($newAdvisement->id)
        ->and($items[1]['id'])->toBe($oldAdvisement->id);
});
