<?php

use App\Models\User;
use Modules\Geo\Http\Controllers\Dashboard\NationalityController;
use Modules\Geo\Models\Nationality;

test('admin can list nationalities with params prop', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['show nationalities']);
    Nationality::query()->create(['translations' => geoNameTranslations('Saudi')]);
    Nationality::query()->create(['translations' => geoNameTranslations('Egyptian')]);

    $this->actingAs($admin, 'admin')
        ->get(action([NationalityController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Nationalities/Index')
            ->has('params')
            ->has('rows.data', 2)
            ->missing('prams')
        );
});

test('admin can store update and delete a nationality', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['create nationalities', 'edit nationalities', 'delete nationalities']);

    $this->actingAs($admin, 'admin')
        ->post(action([NationalityController::class, 'store']), [
            'translations' => geoNameTranslations('Kuwaiti'),
        ])
        ->assertRedirect(route('dashboard.nationalities.index'));

    $nationality = Nationality::query()->whereTranslation('name', 'Kuwaiti EN')->firstOrFail();

    $this->actingAs($admin, 'admin')
        ->put(action([NationalityController::class, 'update'], $nationality), [
            'translations' => geoNameTranslations('Updated Nationality'),
        ])
        ->assertRedirect(route('dashboard.nationalities.index'));

    expect($nationality->fresh()->translate('en')->name)->toBe('Updated Nationality EN');

    $this->actingAs($admin, 'admin')
        ->delete(action([NationalityController::class, 'destroy'], $nationality))
        ->assertRedirect(route('dashboard.nationalities.index'));

    expect(Nationality::query()->whereKey($nationality->id)->exists())->toBeFalse();
});

test('nationality destroy is blocked when users exist', function () {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete nationalities']);
    $nationality = Nationality::query()->create(['translations' => geoNameTranslations('Blocked')]);

    User::factory()->create(['nationality_id' => $nationality->id]);

    $this->actingAs($admin, 'admin')
        ->from(route('dashboard.nationalities.index'))
        ->delete(action([NationalityController::class, 'destroy'], $nationality))
        ->assertRedirect();

    expect(Nationality::query()->whereKey($nationality->id)->exists())->toBeTrue();
});
