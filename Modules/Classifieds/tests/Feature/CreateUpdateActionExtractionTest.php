<?php

use App\Models\User;
use Modules\Catalog\Models\CarBrand;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\CarType;
use Modules\Catalog\Models\PropertyCategory;
use Modules\Catalog\Models\PropertyType;
use Modules\Classifieds\Actions\CarAdvisement\CreateCarAdvisementAction;
use Modules\Classifieds\Actions\PropertyAdvisement\UpdatePropertyAdvisementAction;
use Modules\Classifieds\DTOs\CarAdvisementDTO;
use Modules\Classifieds\DTOs\PropertyAdvisementDTO;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\OperationEnum;
use Modules\Classifieds\Enums\UsageStatusEnum;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Classifieds\Services\CarAdvisementService;
use Modules\Classifieds\Services\PropertyAdvisementService;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

test('creating a car advisement produces identical result via new Action', function () {
    $user = User::factory()->create();
    $carBrand = CarBrand::factory()->create();
    $carType = CarType::factory()->create();
    $carCategory = CarCategory::factory()->create();
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);

    $dto = new CarAdvisementDTO(
        title: 'Toyota Camry',
        description: 'Clean car for sale',
        operation: OperationEnum::SALE->value,
        usageStatus: UsageStatusEnum::USED->value,
        carBrandId: $carBrand->id,
        carTypeId: $carType->id,
        carCategoryId: $carCategory->id,
        year: 2020,
        price: 75000,
        showPrice: true,
        cityId: $city->id,
        regionId: $region->id,
    );

    $viaService = app(CarAdvisementService::class)->create($user, $dto);
    $viaAction = app(CreateCarAdvisementAction::class)->handle($user, $dto);

    expect($viaService->status)->toBe(AdvisementStatusEnum::PENDING)
        ->and($viaService->user_type)->toBe(User::class)
        ->and($viaService->user_id)->toBe($user->id)
        ->and($viaService->relationLoaded('carBrand'))->toBeTrue()
        ->and($viaService->relationLoaded('carType'))->toBeTrue()
        ->and($viaService->relationLoaded('carCategory'))->toBeTrue()
        ->and($viaService->relationLoaded('city'))->toBeTrue()
        ->and($viaService->relationLoaded('region'))->toBeTrue()
        ->and($viaService->relationLoaded('user'))->toBeTrue()
        ->and($viaService->relationLoaded('media'))->toBeTrue()
        ->and($viaAction->status)->toBe(AdvisementStatusEnum::PENDING)
        ->and($viaAction->user_type)->toBe(User::class)
        ->and($viaAction->user_id)->toBe($user->id)
        ->and($viaAction->relationLoaded('carBrand'))->toBeTrue()
        ->and($viaAction->relationLoaded('media'))->toBeTrue();
});

test('updating a property advisement preserves owner authorization order', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $propertyType = PropertyType::factory()->create();
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $category = PropertyCategory::factory()->create();

    $advisement = PropertyAdvisement::factory()->create([
        'user_type' => User::class,
        'user_id' => $owner->id,
        'property_type_id' => $propertyType->id,
        'city_id' => $city->id,
        'region_id' => $region->id,
        'category_id' => $category->id,
        'title' => 'Original Title',
    ]);

    $dto = new PropertyAdvisementDTO(
        title: 'Hacked Title',
        description: 'Should not persist for attacker',
        operation: OperationEnum::SALE->value,
        propertyTypeId: $propertyType->id,
        cityId: $city->id,
        regionId: $region->id,
        categoryId: $category->id,
        price: 100000,
        showPrice: true,
        area: null,
        bedroomsCount: null,
        bathroomsCount: null,
        hallsCount: null,
        age: null,
        facade: null,
        streetWidth: null,
        streetType: null,
        phone: null,
        license: null,
        address: null,
        latitude: null,
        longitude: null,
        options: null,
        files: null,
    );

    // Authorization runs in Service BEFORE Update Action — non-owner never reaches update.
    expect(fn () => app(PropertyAdvisementService::class)->update($attacker, $advisement, $dto))
        ->toThrow(AccessDeniedHttpException::class);

    expect($advisement->fresh()->title)->toBe('Original Title');

    $updated = app(PropertyAdvisementService::class)->update($owner, $advisement, $dto);

    expect($updated->title)->toBe('Hacked Title')
        ->and($updated->relationLoaded('propertyType'))->toBeTrue()
        ->and($updated->relationLoaded('city'))->toBeTrue()
        ->and($updated->relationLoaded('region'))->toBeTrue()
        ->and($updated->relationLoaded('category'))->toBeTrue()
        ->and($updated->relationLoaded('user'))->toBeTrue()
        ->and($updated->relationLoaded('media'))->toBeTrue();

    // Update Action alone still works when called after auth (no auth inside Action).
    $viaAction = app(UpdatePropertyAdvisementAction::class)->handle($advisement, new PropertyAdvisementDTO(
        title: 'Owner Via Action',
        description: $dto->description,
        operation: $dto->operation,
        propertyTypeId: $dto->propertyTypeId,
        cityId: $dto->cityId,
        regionId: $dto->regionId,
        categoryId: $dto->categoryId,
        price: $dto->price,
        showPrice: $dto->showPrice,
        area: null,
        bedroomsCount: null,
        bathroomsCount: null,
        hallsCount: null,
        age: null,
        facade: null,
        streetWidth: null,
        streetType: null,
        phone: null,
        license: null,
        address: null,
        latitude: null,
        longitude: null,
        options: null,
        files: null,
    ));

    expect($viaAction->title)->toBe('Owner Via Action');
});
