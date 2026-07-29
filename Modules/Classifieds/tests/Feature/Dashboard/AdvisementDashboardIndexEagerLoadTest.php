<?php

use App\Models\User;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Catalog\Models\ElectronicBrand;
use Modules\Catalog\Models\Specialization;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\ElectronicConditionEnum;
use Modules\Classifieds\Enums\InstituteTypeEnum;
use Modules\Classifieds\Enums\StudyLevelEnum;
use Modules\Classifieds\Enums\StudyTypeEnum;
use Modules\Classifieds\Http\Controllers\Dashboard\CarAdvisementController;
use Modules\Classifieds\Http\Controllers\Dashboard\ElectronicAdvisementController;
use Modules\Classifieds\Http\Controllers\Dashboard\InstituteAdvisementController;
use Modules\Classifieds\Http\Controllers\Dashboard\PropertyAdvisementController;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;

beforeEach(function () {
    withoutClassifiedsDashboardLocaleMiddleware();
});

test('electronic advisement dashboard index eager-loads card relations', function () {
    $admin = createClassifiedsDashboardAdmin(['show electronicAdvisements']);

    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $category = DeviceCategory::query()->create(['icon' => 'icons/test.png']);
    $category->translateOrNew('en')->title = 'Phones';
    $category->save();
    $brand = ElectronicBrand::query()->create(['image' => 'brands/test.png', 'is_active' => true]);
    $brand->translateOrNew('en')->name = 'Test Brand';
    $brand->save();
    $user = User::factory()->create(['f_name' => 'Ada', 'l_name' => 'Lovelace']);

    $advisement = ElectronicAdvisement::query()->create([
        'title' => 'Eager load phone',
        'normalized_title' => 'eager-load-phone',
        'description' => 'A device',
        'normalized_description' => 'a-device',
        'status' => AdvisementStatusEnum::PENDING,
        'condition' => ElectronicConditionEnum::NEW,
        'price' => 100,
        'show_price' => true,
        'user_type' => User::class,
        'user_id' => $user->id,
        'device_category_id' => $category->id,
        'electronic_brand_id' => $brand->id,
        'city_id' => $city->id,
        'region_id' => $region->id,
        'options' => [],
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([ElectronicAdvisementController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/ElectronicAdvisement/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $advisement->id)
            ->where('rows.data.0.device_category.id', $category->id)
            ->where('rows.data.0.electronic_brand.id', $brand->id)
            ->where('rows.data.0.city.id', $city->id)
            ->where('rows.data.0.region.id', $region->id)
            ->where('rows.data.0.user.id', $user->id)
            ->missing('rows.data.0.media'));
});

test('property advisement dashboard index eager-loads card relations', function () {
    $admin = createClassifiedsDashboardAdmin(['show propertyAdvisements']);
    $advisement = PropertyAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PENDING,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([PropertyAdvisementController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/PropertyAdvisement/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $advisement->id)
            ->has('rows.data.0.property_type.id')
            ->has('rows.data.0.city.id')
            ->has('rows.data.0.region.id')
            ->has('rows.data.0.category.id')
            ->has('rows.data.0.user.id')
            ->missing('rows.data.0.media'));
});

test('car advisement dashboard index eager-loads card relations', function () {
    $admin = createClassifiedsDashboardAdmin(['show carAdvisements']);
    $advisement = CarAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PENDING,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([CarAdvisementController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/CarAdvisement/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $advisement->id)
            ->has('rows.data.0.car_brand.id')
            ->has('rows.data.0.car_type.id')
            ->has('rows.data.0.car_category.id')
            ->has('rows.data.0.city.id')
            ->has('rows.data.0.region.id')
            ->has('rows.data.0.user.id')
            ->missing('rows.data.0.media'));
});

test('institute advisement dashboard index eager-loads card relations', function () {
    $admin = createClassifiedsDashboardAdmin(['show instituteAdvisements']);

    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $user = User::factory()->create();
    $specialization = Specialization::factory()->create();

    $advisement = InstituteAdvisement::query()->create([
        'title' => 'Eager load course',
        'normalized_title' => 'eager-load-course',
        'description' => 'A course',
        'normalized_description' => 'a-course',
        'status' => AdvisementStatusEnum::PENDING,
        'price' => 50,
        'type' => InstituteTypeEnum::INSTITUTE,
        'study_type' => StudyTypeEnum::ONSITE,
        'study_level' => StudyLevelEnum::CERTIFICATE,
        'user_type' => User::class,
        'user_id' => $user->id,
        'specialization_id' => $specialization->id,
        'city_id' => $city->id,
        'region_id' => $region->id,
        'options' => [],
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([InstituteAdvisementController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/InstituteAdvisement/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $advisement->id)
            ->where('rows.data.0.specialization.id', $specialization->id)
            ->where('rows.data.0.city.id', $city->id)
            ->where('rows.data.0.region.id', $region->id)
            ->where('rows.data.0.user.id', $user->id)
            ->missing('rows.data.0.media'));
});

test('error_loading_data translation key exists in all locales', function () {
    foreach (['en', 'ar', 'ur', 'hi'] as $locale) {
        $translations = json_decode(
            file_get_contents(lang_path("{$locale}.json")),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        expect($translations)->toHaveKey('error_loading_data')
            ->and($translations['error_loading_data'])->not->toBeEmpty();
    }
});
