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

test('admin can delete a region', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete regions']);
    $region = Region::factory()->create();

    $this->actingAs($admin, 'admin')
        ->delete(action([RegionController::class, 'destroy'], $region))
        ->assertRedirect(route('dashboard.regions.index'));

    expect(Region::query()->whereKey($region->id)->exists())->toBeFalse();
});
