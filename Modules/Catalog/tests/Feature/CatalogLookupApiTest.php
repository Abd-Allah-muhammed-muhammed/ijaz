<?php

use Modules\Catalog\Http\Controllers\V1\DeviceCategoryController;
use Modules\Catalog\Http\Controllers\V1\ElectronicBrandController;
use Modules\Catalog\Http\Controllers\V1\SpecializationController;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Catalog\Models\ElectronicBrand;
use Modules\Catalog\Models\Specialization;
use Tests\TestCase;

function createDeviceCategory(array $attributes = []): DeviceCategory
{
    $deviceCategory = DeviceCategory::create(array_merge([
        'parent_id' => null,
        'icon' => null,
    ], $attributes));

    $deviceCategory->translations()->createMany([
        ['locale' => 'en', 'title' => 'Smartphones'],
        ['locale' => 'ar', 'title' => 'هواتف ذكية'],
    ]);

    return $deviceCategory->fresh();
}

function createElectronicBrand(bool $isActive = true): ElectronicBrand
{
    $electronicBrand = ElectronicBrand::create([
        'image' => null,
        'is_active' => $isActive,
    ]);

    $electronicBrand->translations()->createMany([
        ['locale' => 'en', 'name' => 'Samsung'],
        ['locale' => 'ar', 'name' => 'سامسونج'],
    ]);

    return $electronicBrand->fresh();
}

it('lists root device categories as a flat array', function (): void {
    /** @var TestCase $this */
    $parent = createDeviceCategory();
    createDeviceCategory(['parent_id' => $parent->id]);
    createDeviceCategory();

    $response = $this->getJson(action([DeviceCategoryController::class, 'index']));

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'parent_id',
                ],
            ],
        ]);
});

it('shows a device category', function (): void {
    /** @var TestCase $this */
    $parent = createDeviceCategory();

    $response = $this->getJson(action([DeviceCategoryController::class, 'show'], ['deviceCategory' => $parent->id]));

    $response->assertOk()
        ->assertJsonPath('data.id', $parent->id)
        ->assertJsonPath('data.title', 'Smartphones')
        ->assertJsonPath('data.parent_id', null);
});

it('lists only active electronic brands', function (): void {
    /** @var TestCase $this */
    createElectronicBrand(isActive: true);
    createElectronicBrand(isActive: false);

    $response = $this->getJson(action([ElectronicBrandController::class, 'index']));

    $response->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'name',
                ],
            ],
        ])
        ->assertJsonPath('data.0.name', 'Samsung');
});

it('shows a single electronic brand', function (): void {
    /** @var TestCase $this */
    $brand = createElectronicBrand();

    $response = $this->getJson(action([ElectronicBrandController::class, 'show'], ['electronicBrand' => $brand->id]));

    $response->assertOk()
        ->assertJsonPath('data.id', $brand->id)
        ->assertJsonPath('data.name', 'Samsung');
});

it('lists root specializations as a flat array', function (): void {
    /** @var TestCase $this */
    $parent = Specialization::factory()->create();
    Specialization::factory()->create(['parent_id' => $parent->id]);
    Specialization::factory()->create();

    $response = $this->getJson(action([SpecializationController::class, 'index']));

    $response->assertOk()
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => [
                    'id',
                    'title',
                    'parent_id',
                ],
            ],
        ]);
});

it('shows a specialization', function (): void {
    /** @var TestCase $this */
    $parent = Specialization::factory()->create();

    $response = $this->getJson(action([SpecializationController::class, 'show'], ['specialization' => $parent->id]));

    $response->assertOk()
        ->assertJsonPath('data.id', $parent->id)
        ->assertJsonPath('data.parent_id', null);
});
