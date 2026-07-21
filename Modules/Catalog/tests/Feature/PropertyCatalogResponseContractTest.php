<?php

use Modules\Catalog\Models\PropertiyCategory;
use Modules\Catalog\Models\PropertyType;

/**
 * Response-shape contract lock for Catalog property V1 APIs.
 * Must keep passing after PropertyType/PropertyCategory layering.
 */
test('catalog property-types response shape contract', function () {
    $type = PropertyType::factory()->create(['is_active' => true]);
    $type->translations()->where('locale', 'en')->update(['name' => 'Villa Contract']);

    $response = $this->getJson('/api/v1/catalog/property-types');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toHaveKeys([
            'items', 'total', 'count', 'per_page', 'current_page', 'last_page', 'has_more_pages',
        ])
        ->and($json['data']['items'])->toBeArray()->not->toBeEmpty();

    $item = $json['data']['items'][0];
    expect($item)->toHaveKeys(['id', 'name', 'is_active', 'translations']);
});

test('catalog property-categories response shape contract with parent_id filter', function () {
    $parent = PropertiyCategory::factory()->create();
    $parent->translations()->where('locale', 'en')->update(['title' => 'Residential']);

    $child = PropertiyCategory::factory()->create(['parent_id' => $parent->id]);
    $child->translations()->where('locale', 'en')->update(['title' => 'Apartment']);

    $topLevel = $this->getJson('/api/v1/catalog/property-categories');
    $topLevel->assertSuccessful();

    $topJson = $topLevel->json();
    expect($topJson)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($topJson['data'])->toHaveKeys([
            'items', 'total', 'count', 'per_page', 'current_page', 'last_page', 'has_more_pages',
        ])
        ->and($topJson['data']['items'])->toBeArray()->not->toBeEmpty();

    $item = $topJson['data']['items'][0];
    expect($item)->toHaveKeys(['id', 'title', 'is_active', 'parent_id', 'children_count', 'translations']);

    $filtered = $this->getJson('/api/v1/catalog/property-categories?parent_id='.$parent->id);
    $filtered->assertSuccessful();
    expect($filtered->json('data.items'))->toBeArray()->not->toBeEmpty()
        ->and($filtered->json('data.items.0.parent_id'))->toBe($parent->id);
});
