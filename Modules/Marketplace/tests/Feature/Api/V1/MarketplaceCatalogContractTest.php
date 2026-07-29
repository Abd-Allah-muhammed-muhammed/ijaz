<?php

use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\ProviderType;
use Modules\Marketplace\Models\Skill;

/**
 * Response-shape contract lock for Catalog marketplace V1 APIs.
 */
test('catalog categories response shape contract', function () {
    $category = Category::factory()->create();
    $category->translations()->where('locale', 'en')->update(['title' => 'Plumbing Contract']);

    $response = $this->getJson('/api/v1/catalog/categories');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toHaveKeys([
            'items', 'total', 'count', 'per_page', 'current_page', 'last_page', 'has_more_pages',
        ])
        ->and($json['data']['items'])->toBeArray()->not->toBeEmpty();

    $item = $json['data']['items'][0];
    expect($item)->toHaveKeys(['id', 'icon', 'parent_id', 'title', 'description']);
});

test('catalog categories with-no-children response shape contract', function () {
    $parent = Category::factory()->create();
    $parent->translations()->where('locale', 'en')->update(['title' => 'Parent Contract']);

    Category::factory()->create(['parent_id' => $parent->id]);

    $leaf = Category::factory()->create();
    $leaf->translations()->where('locale', 'en')->update(['title' => 'Leaf Contract']);

    $response = $this->getJson('/api/v1/catalog/categories/with-no-children');
    $response->assertSuccessful();

    expect($response->json('data.items'))->toBeArray()->not->toBeEmpty();
});

test('catalog category skills response shape contract', function () {
    $category = Category::factory()->create();
    $skill = Skill::query()->create([
        'category_id' => $category->id,
        'translations' => [
            'en' => ['title' => 'Skill Contract EN'],
            'ar' => ['title' => 'Skill Contract AR'],
            'ur' => ['title' => 'Skill Contract UR'],
            'hi' => ['title' => 'Skill Contract HI'],
        ],
    ]);

    $response = $this->getJson('/api/v1/catalog/categories/'.$category->id.'/skills');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json['data']['items'])->toBeArray()->not->toBeEmpty();
    expect($json['data']['items'][0])->toHaveKeys(['id', 'category_id', 'title']);
});

test('catalog provider-types response shape contract', function () {
    $providerType = ProviderType::query()->create([
        'files' => [
            'id_image' => true,
            'commercial_record' => true,
            'freelancer_certification' => false,
            'iban_certification' => false,
            'license_to_practice_law' => false,
        ],
        'image' => 'provider-types/test.png',
        'translations' => [
            'en' => ['name' => 'Individual EN', 'description' => 'Desc EN'],
            'ar' => ['name' => 'Individual AR', 'description' => 'Desc AR'],
            'ur' => ['name' => 'Individual UR', 'description' => 'Desc UR'],
            'hi' => ['name' => 'Individual HI', 'description' => 'Desc HI'],
        ],
    ]);

    $response = $this->getJson('/api/v1/catalog/provider-types');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toHaveKeys([
            'items', 'total', 'count', 'per_page', 'current_page', 'last_page', 'has_more_pages',
        ])
        ->and($json['data']['items'])->toBeArray()->not->toBeEmpty();

    $item = collect($json['data']['items'])->firstWhere('id', $providerType->id);
    expect($item)->toHaveKeys(['id', 'files', 'image', 'name', 'description', 'translations']);
});
