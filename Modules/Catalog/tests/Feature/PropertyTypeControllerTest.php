<?php

use Modules\Catalog\Http\Controllers\V1\PropertyTypeController;
use Modules\Catalog\Models\PropertyType;
use Tests\TestCase;

it('lists property types with translations', function (): void {
    /** @var TestCase $this */
    PropertyType::factory()->count(3)->create();

    $response = $this->getJson(action([PropertyTypeController::class, 'index']));

    $response->assertOk()
        ->assertJsonPath('data.total', 3)
        ->assertJsonStructure([
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'name',
                        'is_active',
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

it('filters property types by search', function (): void {
    /** @var TestCase $this */
    $matching = PropertyType::factory()->create();
    $matching->translations()->where('locale', 'en')->update(['name' => 'Villa']);

    $nonMatching = PropertyType::factory()->create();
    $nonMatching->translations()->where('locale', 'en')->update(['name' => 'Apartment']);

    $response = $this->getJson(action([PropertyTypeController::class, 'index'], ['search' => 'Villa']));

    $response->assertOk()->assertJsonPath('data.total', 1);
});
