<?php

use Modules\Geo\Contracts\Repositories\RegionRepositoryInterface;
use Modules\Geo\Http\Controllers\Dashboard\RegionController;
use Modules\Geo\Models\Region;

test('admin can list regions with prams and rows props', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['show regions']);
    Region::factory()->count(2)->create();

    $this->actingAs($admin, 'admin')
        ->get(action([RegionController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Regions/Index')
            ->has('prams')
            ->has('rows.data', 2)
            ->missing('params')
        );
});

test('admin can store a region', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['create regions']);

    $this->actingAs($admin, 'admin')
        ->post(action([RegionController::class, 'store']), [
            'translations' => geoTitleTranslations('Riyadh'),
        ])
        ->assertRedirect(route('dashboard.regions.index'));

    expect(Region::query()->whereTranslation('title', 'Riyadh EN')->exists())->toBeTrue();
});

test('admin can update a region', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit regions']);
    $region = app(RegionRepositoryInterface::class)->create(geoTitleTranslations('Old'));

    $this->actingAs($admin, 'admin')
        ->put(action([RegionController::class, 'update'], $region), [
            'translations' => geoTitleTranslations('New'),
        ])
        ->assertRedirect(route('dashboard.regions.index'));

    expect($region->fresh()->translate('en')->title)->toBe('New EN');
});

test('editing a region without changing its Arabic title does not trigger a unique validation error — the current region\'s own translation row must be excluded from the uniqueness check', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit regions']);
    $region = app(RegionRepositoryInterface::class)->create(geoTitleTranslations('Riyadh'));

    $this->actingAs($admin, 'admin')
        ->put(action([RegionController::class, 'update'], $region), [
            'translations' => geoTitleTranslations('Riyadh'),
        ])
        ->assertRedirect(route('dashboard.regions.index'))
        ->assertSessionHasNoErrors();

    $fresh = $region->fresh();
    expect($fresh->translate('ar')->title)->toBe('Riyadh AR')
        ->and($fresh->translate('en')->title)->toBe('Riyadh EN')
        ->and($fresh->translate('hi')->title)->toBe('Riyadh HI')
        ->and($fresh->translate('ur')->title)->toBe('Riyadh UR');
});

test('editing a region and changing its Arabic title to another region\'s existing title STILL correctly fails validation — the exclusion must not disable uniqueness checking entirely', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit regions']);
    app(RegionRepositoryInterface::class)->create(geoTitleTranslations('Riyadh'));
    $region = app(RegionRepositoryInterface::class)->create(geoTitleTranslations('Jeddah'));

    $translations = geoTitleTranslations('Jeddah');
    $translations['ar']['title'] = 'Riyadh AR';

    $this->actingAs($admin, 'admin')
        ->from(action([RegionController::class, 'edit'], $region))
        ->put(action([RegionController::class, 'update'], $region), [
            'translations' => $translations,
        ])
        ->assertSessionHasErrors('translations.ar.title');

    expect($region->fresh()->translate('ar')->title)->toBe('Jeddah AR');
});

test('editing a region and changing a translated title to another region\'s existing title fails uniqueness per locale', function (string $locale) {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit regions']);
    app(RegionRepositoryInterface::class)->create(geoTitleTranslations('Riyadh'));
    $region = app(RegionRepositoryInterface::class)->create(geoTitleTranslations('Jeddah'));

    $translations = geoTitleTranslations('Jeddah');
    $translations[$locale]['title'] = 'Riyadh '.strtoupper($locale);

    $this->actingAs($admin, 'admin')
        ->from(action([RegionController::class, 'edit'], $region))
        ->put(action([RegionController::class, 'update'], $region), [
            'translations' => $translations,
        ])
        ->assertSessionHasErrors("translations.{$locale}.title");

    expect($region->fresh()->translate($locale)->title)->toBe('Jeddah '.strtoupper($locale));
})->with(['ar', 'en', 'hi', 'ur']);

test('admin can delete a region', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete regions']);
    $region = Region::factory()->create();

    $this->actingAs($admin, 'admin')
        ->delete(action([RegionController::class, 'destroy'], $region))
        ->assertRedirect(route('dashboard.regions.index'));

    expect(Region::query()->whereKey($region->id)->exists())->toBeFalse();
});
