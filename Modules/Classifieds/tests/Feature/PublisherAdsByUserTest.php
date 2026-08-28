<?php

use App\Http\Resources\Api\V1\PublisherResource;
use App\Http\Resources\Api\V1\User\UserResource;
use App\Models\User;
use Laravel\Sanctum\Sanctum;
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
use Modules\Classifieds\Http\Controllers\Api\V1\InstituteAdvisementController;
use Modules\Classifieds\Http\Controllers\Api\V1\PropertyAdvisementController;
use Modules\Classifieds\Http\Resources\Api\CarAdvisementResource;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

test('GET classifieds/cars/by-user/{user} returns only that user\'s published car ads, paginated', function () {
    $publisher = User::factory()->create();
    $other = User::factory()->create();

    CarAdvisement::factory()->published()->count(2)->create([
        'user_type' => User::class,
        'user_id' => $publisher->id,
    ]);
    CarAdvisement::factory()->pending()->create([
        'user_type' => User::class,
        'user_id' => $publisher->id,
    ]);
    CarAdvisement::factory()->published()->count(3)->create([
        'user_type' => User::class,
        'user_id' => $other->id,
    ]);

    $this->getJson(action([CarAdvisementController::class, 'byUser'], $publisher))
        ->assertOk()
        ->assertJsonPath('data.total', 2)
        ->assertJsonCount(2, 'data.items')
        ->assertJsonStructure([
            'data' => [
                'items',
                'total',
                'count',
                'per_page',
                'current_page',
                'last_page',
                'has_more_pages',
            ],
        ]);
});

test('the endpoint is public (no auth required), matching the existing */all pattern', function () {
    $publisher = User::factory()->create();
    CarAdvisement::factory()->published()->create([
        'user_type' => User::class,
        'user_id' => $publisher->id,
    ]);

    $this->getJson(action([CarAdvisementController::class, 'byUser'], $publisher))
        ->assertOk();
});

test('an empty result (user has no other car ads) returns items: [] with total: 0, not an error', function () {
    $publisher = User::factory()->create();

    $this->getJson(action([CarAdvisementController::class, 'byUser'], $publisher))
        ->assertOk()
        ->assertJsonPath('data.total', 0)
        ->assertJsonPath('data.items', []);
});

test('the same endpoint pattern works correctly for properties, electronics, and institutes', function () {
    $publisher = User::factory()->create();
    $other = User::factory()->create();

    PropertyAdvisement::factory()->published()->count(2)->create([
        'user_type' => User::class,
        'user_id' => $publisher->id,
    ]);
    PropertyAdvisement::factory()->published()->create([
        'user_type' => User::class,
        'user_id' => $other->id,
    ]);

    publisherCreateElectronic($publisher, 2);
    publisherCreateElectronic($other, 1);

    publisherCreateInstitute($publisher, 2);
    publisherCreateInstitute($other, 1);

    $this->getJson(action([PropertyAdvisementController::class, 'byUser'], $publisher))
        ->assertOk()
        ->assertJsonPath('data.total', 2);

    $this->getJson(action([ElectronicAdvisementController::class, 'byUser'], $publisher))
        ->assertOk()
        ->assertJsonPath('data.total', 2);

    $this->getJson(action([InstituteAdvisementController::class, 'byUser'], $publisher))
        ->assertOk()
        ->assertJsonPath('data.total', 2);
});

test('the publisher card data on ad-details uses a new slim resource (id, name, image) distinct from the existing full user field, which remains unchanged', function () {
    $publisher = User::factory()->create([
        'f_name' => 'Ada',
        'l_name' => 'Lovelace',
        'phone' => '966501111111',
        'email' => 'ada@example.com',
    ]);

    $car = CarAdvisement::factory()->published()->create([
        'user_type' => User::class,
        'user_id' => $publisher->id,
    ]);
    $car->load('user');

    $payload = CarAdvisementResource::make($car)->response()->getData(true);

    expect($payload)->toHaveKeys(['user', 'publisher'])
        ->and(array_keys($payload['publisher']))->toBe(['id', 'name', 'image'])
        ->and($payload['publisher']['id'])->toBe($publisher->id)
        ->and($payload['publisher']['name'])->toBe('Ada Lovelace')
        ->and($payload['publisher']['image'])->toBe($publisher->image_url)
        ->and($payload['user'])->toHaveKeys(['id', 'name', 'phone', 'email', 'image'])
        ->and($payload['user']['phone'])->toBe('966501111111')
        ->and($payload['user']['email'])->toBe('ada@example.com');
});

test('the slim publisher resource never includes phone or email', function () {
    $user = User::factory()->create([
        'phone' => '966509999999',
        'email' => 'secret@example.com',
    ]);

    $payload = PublisherResource::make($user)->response()->getData(true);

    expect(array_keys($payload))->toBe(['id', 'name', 'image'])
        ->and($payload)->not->toHaveKey('phone')
        ->and($payload)->not->toHaveKey('email');
});

test('existing ad-details/list endpoints (show, */all, index) are completely unaffected — regression, full user field unchanged', function () {
    $user = User::factory()->create([
        'f_name' => 'Grace',
        'l_name' => 'Hopper',
        'phone' => '966502222222',
        'email' => 'grace@example.com',
    ]);

    $own = CarAdvisement::factory()->published()->create([
        'user_type' => User::class,
        'user_id' => $user->id,
    ]);
    CarAdvisement::factory()->published()->create([
        'user_type' => User::class,
        'user_id' => User::factory(),
    ]);

    Sanctum::actingAs($user);

    $show = $this->getJson(action([CarAdvisementController::class, 'show'], $own))
        ->assertOk()
        ->json('data');

    expect($show)->toHaveKey('user')
        ->and($show['user'])->toHaveKeys(['id', 'name', 'phone', 'email', 'image', 'f_name', 'l_name'])
        ->and($show['user']['phone'])->toBe('966502222222')
        ->and($show)->toHaveKey('publisher')
        ->and(array_keys($show['publisher']))->toBe(['id', 'name', 'image']);

    $all = $this->getJson(action([CarAdvisementController::class, 'all']))
        ->assertOk()
        ->json('data');

    expect($all)->toHaveKeys(['items', 'total'])
        ->and($all['total'])->toBeGreaterThanOrEqual(2)
        ->and($all['items'][0])->toHaveKey('user')
        ->and($all['items'][0]['user'])->toHaveKey('phone');

    $index = $this->getJson(action([CarAdvisementController::class, 'index']))
        ->assertOk()
        ->json('data');

    expect($index)->toHaveKeys(['items', 'total'])
        ->and($index['total'])->toBe(1);

    // Full UserResource contract keys (nationality not loaded) stay intact on nested user.
    $car = $own->fresh()->load('user');
    $resourceUser = UserResource::make($car->user)->response()->getData(true);
    expect(array_keys($resourceUser))->toBe([
        'id', 'socket_id', 'name', 'f_name', 'l_name', 'phone', 'image',
        'language', 'latitude', 'longitude', 'email', 'nationality_id',
    ]);
});

/**
 * @return list<ElectronicAdvisement>
 */
function publisherCreateElectronic(User $user, int $count): array
{
    $created = [];
    for ($i = 0; $i < $count; $i++) {
        $region = Region::factory()->create();
        $city = City::factory()->create(['region_id' => $region->id]);
        $category = DeviceCategory::query()->create(['icon' => 'icons/test.png']);
        $category->translateOrNew('en')->title = 'Phones '.$user->id.'-'.$i;
        $category->save();
        $brand = ElectronicBrand::query()->create(['image' => 'brands/test.png', 'is_active' => true]);
        $brand->translateOrNew('en')->name = 'Brand '.$user->id.'-'.$i;
        $brand->save();

        $created[] = ElectronicAdvisement::query()->create([
            'title' => 'Electronic item',
            'normalized_title' => 'electronic-item',
            'description' => 'A device',
            'normalized_description' => 'a-device',
            'image' => 'media/test.png',
            'status' => AdvisementStatusEnum::PUBLISHED,
            'condition' => ElectronicConditionEnum::NEW,
            'price' => 50,
            'show_price' => true,
            'phone' => '966501234567',
            'user_type' => User::class,
            'user_id' => $user->id,
            'device_category_id' => $category->id,
            'electronic_brand_id' => $brand->id,
            'city_id' => $city->id,
            'region_id' => $region->id,
            'options' => [],
        ]);
    }

    return $created;
}

/**
 * @return list<InstituteAdvisement>
 */
function publisherCreateInstitute(User $user, int $count): array
{
    $created = [];
    for ($i = 0; $i < $count; $i++) {
        $region = Region::factory()->create();
        $city = City::factory()->create(['region_id' => $region->id]);
        $specialization = Specialization::factory()->create();

        $created[] = InstituteAdvisement::query()->create([
            'title' => 'Institute course',
            'normalized_title' => 'institute-course',
            'description' => 'A course',
            'normalized_description' => 'a-course',
            'image' => 'media/test.png',
            'status' => AdvisementStatusEnum::PUBLISHED,
            'price' => 50,
            'type' => InstituteTypeEnum::INSTITUTE,
            'study_type' => StudyTypeEnum::ONSITE,
            'study_level' => StudyLevelEnum::CERTIFICATE,
            'phone' => '966501234567',
            'user_type' => User::class,
            'user_id' => $user->id,
            'specialization_id' => $specialization->id,
            'city_id' => $city->id,
            'region_id' => $region->id,
            'options' => [],
        ]);
    }

    return $created;
}
