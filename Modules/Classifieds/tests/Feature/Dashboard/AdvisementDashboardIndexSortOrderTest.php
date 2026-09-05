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

test('car advisement dashboard index orders by created_at desc with filters', function () {
    $admin = createClassifiedsDashboardAdmin(['show carAdvisements']);

    $middle = CarAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PENDING,
        'created_at' => now()->subDays(2),
    ]);
    $newest = CarAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PENDING,
        'created_at' => now()->subDay(),
    ]);
    $oldest = CarAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PENDING,
        'created_at' => now()->subDays(5),
    ]);
    CarAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PUBLISHED,
        'created_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([CarAdvisementController::class, 'index'], ['status' => 'pending']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/CarAdvisement/Index')
            ->has('rows.data', 3)
            ->where('rows.data.0.id', $newest->id)
            ->where('rows.data.1.id', $middle->id)
            ->where('rows.data.2.id', $oldest->id));
});

test('electronic advisement dashboard index orders by created_at desc with filters', function () {
    $admin = createClassifiedsDashboardAdmin(['show electronicAdvisements']);

    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $category = DeviceCategory::query()->create(['icon' => 'icons/test.png']);
    $category->translateOrNew('en')->title = 'Phones';
    $category->save();
    $brand = ElectronicBrand::query()->create(['image' => 'brands/test.png', 'is_active' => true]);
    $brand->translateOrNew('en')->name = 'Test Brand';
    $brand->save();
    $user = User::factory()->create();

    $base = [
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
    ];

    $middle = ElectronicAdvisement::query()->create([
        ...$base,
        'title' => 'Middle device',
        'normalized_title' => 'middle-device',
    ]);
    $newest = ElectronicAdvisement::query()->create([
        ...$base,
        'title' => 'Newest device',
        'normalized_title' => 'newest-device',
    ]);
    $oldest = ElectronicAdvisement::query()->create([
        ...$base,
        'title' => 'Oldest device',
        'normalized_title' => 'oldest-device',
    ]);
    $published = ElectronicAdvisement::query()->create([
        ...$base,
        'title' => 'Published device',
        'normalized_title' => 'published-device',
        'status' => AdvisementStatusEnum::PUBLISHED,
    ]);

    // created_at is not fillable — stamp after insert so order ≠ insertion/id order.
    $middle->forceFill(['created_at' => now()->subDays(2)])->saveQuietly();
    $newest->forceFill(['created_at' => now()->subDay()])->saveQuietly();
    $oldest->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();
    $published->forceFill(['created_at' => now()])->saveQuietly();

    $this->actingAs($admin, 'admin')
        ->get(action([ElectronicAdvisementController::class, 'index'], ['status' => 'pending']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/ElectronicAdvisement/Index')
            ->has('rows.data', 3)
            ->where('rows.data.0.id', $newest->id)
            ->where('rows.data.1.id', $middle->id)
            ->where('rows.data.2.id', $oldest->id));
});

test('institute advisement dashboard index orders by created_at desc with filters', function () {
    $admin = createClassifiedsDashboardAdmin(['show instituteAdvisements']);

    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $user = User::factory()->create();
    $specialization = Specialization::factory()->create();

    $base = [
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
    ];

    $middle = InstituteAdvisement::query()->create([
        ...$base,
        'title' => 'Middle course',
        'normalized_title' => 'middle-course',
    ]);
    $newest = InstituteAdvisement::query()->create([
        ...$base,
        'title' => 'Newest course',
        'normalized_title' => 'newest-course',
    ]);
    $oldest = InstituteAdvisement::query()->create([
        ...$base,
        'title' => 'Oldest course',
        'normalized_title' => 'oldest-course',
    ]);
    $published = InstituteAdvisement::query()->create([
        ...$base,
        'title' => 'Published course',
        'normalized_title' => 'published-course',
        'status' => AdvisementStatusEnum::PUBLISHED,
    ]);

    // created_at is not fillable — stamp after insert so order ≠ insertion/id order.
    $middle->forceFill(['created_at' => now()->subDays(2)])->saveQuietly();
    $newest->forceFill(['created_at' => now()->subDay()])->saveQuietly();
    $oldest->forceFill(['created_at' => now()->subDays(5)])->saveQuietly();
    $published->forceFill(['created_at' => now()])->saveQuietly();

    $this->actingAs($admin, 'admin')
        ->get(action([InstituteAdvisementController::class, 'index'], ['status' => 'pending']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/InstituteAdvisement/Index')
            ->has('rows.data', 3)
            ->where('rows.data.0.id', $newest->id)
            ->where('rows.data.1.id', $middle->id)
            ->where('rows.data.2.id', $oldest->id));
});

test('property advisement dashboard index orders by created_at desc with filters', function () {
    $admin = createClassifiedsDashboardAdmin(['show propertyAdvisements']);

    $middle = PropertyAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PENDING,
        'created_at' => now()->subDays(2),
    ]);
    $newest = PropertyAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PENDING,
        'created_at' => now()->subDay(),
    ]);
    $oldest = PropertyAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PENDING,
        'created_at' => now()->subDays(5),
    ]);
    PropertyAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PUBLISHED,
        'created_at' => now(),
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([PropertyAdvisementController::class, 'index'], ['status' => 'pending']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/PropertyAdvisement/Index')
            ->has('rows.data', 3)
            ->where('rows.data.0.id', $newest->id)
            ->where('rows.data.1.id', $middle->id)
            ->where('rows.data.2.id', $oldest->id));
});
