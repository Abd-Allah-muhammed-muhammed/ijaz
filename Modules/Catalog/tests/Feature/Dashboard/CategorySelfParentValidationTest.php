<?php

use Modules\Catalog\Http\Controllers\Dashboard\CarCategoryController;
use Modules\Catalog\Http\Controllers\Dashboard\PropertyCategoryController;
use Modules\Catalog\Models\CarCategory;
use Modules\Catalog\Models\PropertyCategory;

/**
 * @return array<string, array{title: string}>
 */
function catalogSelfParentTitleTranslations(string $prefix): array
{
    return [
        'en' => ['title' => "{$prefix} EN"],
        'ar' => ['title' => "{$prefix} AR"],
        'ur' => ['title' => "{$prefix} UR"],
        'hi' => ['title' => "{$prefix} HI"],
    ];
}

test('property category cannot be set as its own parent', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit propertyCategories']);

    $category = PropertyCategory::factory()->create();
    foreach (catalogSelfParentTitleTranslations('Property Self') as $locale => $attrs) {
        $category->translations()->updateOrCreate(['locale' => $locale], $attrs);
    }

    $this->actingAs($admin, 'admin')
        ->put(action([PropertyCategoryController::class, 'update'], $category), [
            'translations' => catalogSelfParentTitleTranslations('Property Self'),
            'parent_id' => $category->id,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('parent_id');

    expect($category->fresh()->parent_id)->toBeNull();
});

test('car category cannot be set as its own parent', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit carCategories']);

    $category = CarCategory::factory()->create();
    foreach (catalogSelfParentTitleTranslations('Car Self') as $locale => $attrs) {
        $category->translations()->updateOrCreate(['locale' => $locale], $attrs);
    }

    $this->actingAs($admin, 'admin')
        ->put(action([CarCategoryController::class, 'update'], $category), [
            'translations' => catalogSelfParentTitleTranslations('Car Self'),
            'parent_id' => $category->id,
        ])
        ->assertSessionHasErrors('parent_id');

    expect($category->fresh()->parent_id)->toBeNull();
});
