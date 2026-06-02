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

it('lists root device categories with children count', function (): void {
    /** @var TestCase $this */
    $parent = createDeviceCategory();
    createDeviceCategory(['parent_id' => $parent->id]);
    createDeviceCategory();

    $response = $this->getJson(action([DeviceCategoryController::class, 'index']));

    $response->assertOk()
        ->assertJsonPath('data.total', 2)
        ->assertJsonStructure([
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'title',
                        'icon',
                        'parent_id',
                        'children_count',
                    ],
                ],
            ],
        ]);
});

it('shows a device category with its children', function (): void {
    /** @var TestCase $this */
    $parent = createDeviceCategory();
    $child = createDeviceCategory(['parent_id' => $parent->id]);

    $response = $this->getJson(action([DeviceCategoryController::class, 'show'], ['deviceCategory' => $parent->id]));

    $response->assertOk()
        ->assertJsonPath('data.id', $parent->id)
        ->assertJsonPath('data.children_count', 1)
        ->assertJsonPath('data.children.0.id', $child->id);
});

it('lists only active electronic brands', function (): void {
    /** @var TestCase $this */
    createElectronicBrand(isActive: true);
    createElectronicBrand(isActive: false);

    $response = $this->getJson(action([ElectronicBrandController::class, 'index']));

    $response->assertOk()
        ->assertJsonPath('data.total', 1)
        ->assertJsonStructure([
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'name',
                        'image_url',
                        'is_active',
                    ],
                ],
            ],
        ])
        ->assertJsonPath('data.items.0.is_active', true);
});

it('shows a single electronic brand', function (): void {
    /** @var TestCase $this */
    $brand = createElectronicBrand();

    $response = $this->getJson(action([ElectronicBrandController::class, 'show'], ['electronicBrand' => $brand->id]));

    $response->assertOk()
        ->assertJsonPath('data.id', $brand->id)
        ->assertJsonPath('data.name', 'Samsung');
});

it('lists root specializations with children count', function (): void {
    /** @var TestCase $this */
    $parent = Specialization::factory()->create();
    Specialization::factory()->create(['parent_id' => $parent->id]);
    Specialization::factory()->create();

    $response = $this->getJson(action([SpecializationController::class, 'index']));

    $response->assertOk()
        ->assertJsonPath('data.total', 2)
        ->assertJsonStructure([
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'title',
                        'icon',
                        'parent_id',
                        'children_count',
                    ],
                ],
            ],
        ]);
});

it('shows a specialization with its children', function (): void {
    /** @var TestCase $this */
    $parent = Specialization::factory()->create();
    $child = Specialization::factory()->create(['parent_id' => $parent->id]);

    $response = $this->getJson(action([SpecializationController::class, 'show'], ['specialization' => $parent->id]));

    $response->assertOk()
        ->assertJsonPath('data.id', $parent->id)
        ->assertJsonPath('data.children_count', 1)
        ->assertJsonPath('data.children.0.id', $child->id);
});
