<?php

use Modules\Marketplace\Enums\CategoryFeesTypeEnum;
use Modules\Marketplace\Http\Controllers\Dashboard\CategoryController;
use Modules\Marketplace\Models\Category;

test('marketplace category cannot be set as its own parent', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit categories']);

    $category = Category::factory()->create([
        'fees_type' => CategoryFeesTypeEnum::FIXED,
        'fees' => 10,
    ]);

    $translations = [
        'en' => ['title' => 'Market Self EN', 'description' => null],
        'ar' => ['title' => 'Market Self AR', 'description' => null],
        'ur' => ['title' => 'Market Self UR', 'description' => null],
        'hi' => ['title' => 'Market Self HI', 'description' => null],
    ];

    foreach ($translations as $locale => $attrs) {
        $category->translations()->updateOrCreate(['locale' => $locale], $attrs);
    }

    $this->actingAs($admin, 'admin')
        ->put(action([CategoryController::class, 'update'], $category), [
            'translations' => $translations,
            'parent_id' => $category->id,
            'fees_type' => CategoryFeesTypeEnum::FIXED->value,
            'fees' => 10,
        ])
        ->assertSessionHasErrors('parent_id');

    expect($category->fresh()->parent_id)->toBeNull();
});
