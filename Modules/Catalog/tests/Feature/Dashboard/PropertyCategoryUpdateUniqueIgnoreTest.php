<?php

use Modules\Catalog\Http\Controllers\Dashboard\PropertyCategoryController;
use Modules\Catalog\Models\PropertyCategory;

/**
 * Regression: PropertyCategoryRequest unique-ignore must use the route param
 * `property_category` (not the misspelled `property_category`). Without that,
 * updating a category while keeping its own translated title is falsely rejected.
 */
it('allows updating a property category while keeping its existing translated title', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit propertyCategories']);

    $category = PropertyCategory::factory()->create();
    $titles = [
        'en' => 'Keep Me EN',
        'ar' => 'Keep Me AR',
        'ur' => 'Keep Me UR',
        'hi' => 'Keep Me HI',
    ];

    foreach ($titles as $locale => $title) {
        $category->translations()->updateOrCreate(
            ['locale' => $locale],
            ['title' => $title],
        );
    }

    $this->actingAs($admin, 'admin')
        ->put(action([PropertyCategoryController::class, 'update'], $category), [
            'translations' => [
                'en' => ['title' => $titles['en']],
                'ar' => ['title' => $titles['ar']],
                'ur' => ['title' => $titles['ur']],
                'hi' => ['title' => $titles['hi']],
            ],
            'parent_id' => null,
            'is_active' => true,
        ])
        ->assertRedirect(route('dashboard.property-categories.index'))
        ->assertSessionHasNoErrors();

    expect($category->fresh()->translate('en')->title)->toBe('Keep Me EN');
});
