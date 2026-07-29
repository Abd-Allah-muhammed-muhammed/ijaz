<?php

use Modules\Catalog\Http\Controllers\Dashboard\PropertyTypeController;
use Modules\Catalog\Models\PropertyType;

/**
 * Regression: PropertyTypeRequest unique-ignore must target `property_type_id`
 * (not the translation row's own `id`). Without that, updating a type while
 * keeping its own translated name is falsely rejected as "already taken".
 */
it('allows updating a property type while keeping its existing translated name', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit propertyTypes']);

    $propertyType = PropertyType::factory()->create();
    $names = [
        'en' => 'Keep Me EN',
        'ar' => 'Keep Me AR',
        'ur' => 'Keep Me UR',
        'hi' => 'Keep Me HI',
    ];

    foreach ($names as $locale => $name) {
        $propertyType->translations()->updateOrCreate(
            ['locale' => $locale],
            ['name' => $name],
        );
    }

    $this->actingAs($admin, 'admin')
        ->put(action([PropertyTypeController::class, 'update'], $propertyType), [
            'translations' => [
                'en' => ['name' => $names['en']],
                'ar' => ['name' => $names['ar']],
                'ur' => ['name' => $names['ur']],
                'hi' => ['name' => $names['hi']],
            ],
            'is_active' => false,
        ])
        ->assertRedirect(route('dashboard.property-types.index'))
        ->assertSessionHasNoErrors();

    $fresh = $propertyType->fresh();
    expect($fresh->translate('en')->name)->toBe('Keep Me EN')
        ->and($fresh->is_active)->toBeFalse();
});
