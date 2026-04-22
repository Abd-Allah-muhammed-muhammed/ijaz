<?php

use App\Http\Controllers\Api\V1\PropertyCategoryController;
use App\Models\PropertiyCategory;
use Tests\TestCase;

it('lists top level property categories with children count', function (): void {
    /** @var TestCase $this */
    $parent = PropertiyCategory::factory()->create();
    PropertiyCategory::factory()->count(2)->create(['parent_id' => $parent->id]);
    PropertiyCategory::factory()->create();

    $response = $this->getJson(action([PropertyCategoryController::class, 'index']));

    $response->assertOk()
        ->assertJsonPath('data.total', 2)
        ->assertJsonStructure([
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'title',
                        'is_active',
                        'parent_id',
                        'children_count',
                        'translations',
                    ],
                ],
                'total',
                'count',
                'per_page',
                'current_page',
                'last_page',
                'has_more_pages',
            ],
        ]);
});

it('filters property categories by parent id', function (): void {
    /** @var TestCase $this */
    $parent = PropertiyCategory::factory()->create();
    PropertiyCategory::factory()->create(['parent_id' => $parent->id]);

    $response = $this->getJson(action([PropertyCategoryController::class, 'index'], ['parent_id' => $parent->id]));

    $response->assertOk();
});
