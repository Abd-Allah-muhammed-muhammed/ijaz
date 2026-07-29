<?php

use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Http\Controllers\Dashboard\CarAdvisementController;
use Modules\Classifieds\Http\Controllers\Dashboard\PropertyAdvisementController;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\Models\PropertyAdvisement;

beforeEach(function () {
    withoutClassifiedsDashboardLocaleMiddleware();
});

test('admin can delete a property advisement', function (): void {
    $admin = createClassifiedsDashboardAdmin([
        'show propertyAdvisements',
        'delete propertyAdvisements',
    ]);
    $propertyAdvisement = PropertyAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PENDING,
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([PropertyAdvisementController::class, 'index']))
        ->delete(action([PropertyAdvisementController::class, 'destroy'], $propertyAdvisement))
        ->assertRedirect(route('dashboard.property-advisements.index'))
        ->assertSessionHas('success');

    expect(PropertyAdvisement::query()->whereKey($propertyAdvisement->id)->exists())->toBeFalse();
});

test('admin can delete a car advisement', function (): void {
    $admin = createClassifiedsDashboardAdmin([
        'show carAdvisements',
        'delete carAdvisements',
    ]);
    $carAdvisement = CarAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PENDING,
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([CarAdvisementController::class, 'index']))
        ->delete(action([CarAdvisementController::class, 'destroy'], $carAdvisement))
        ->assertRedirect(route('dashboard.car-advisements.index'))
        ->assertSessionHas('success');

    expect(CarAdvisement::query()->whereKey($carAdvisement->id)->exists())->toBeFalse();
});

test('admin without permission cannot delete a property advisement', function (): void {
    $admin = createClassifiedsDashboardAdmin(['show propertyAdvisements']);
    $propertyAdvisement = PropertyAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PENDING,
    ]);

    $this->actingAs($admin, 'admin')
        ->delete(action([PropertyAdvisementController::class, 'destroy'], $propertyAdvisement))
        ->assertForbidden();

    expect(PropertyAdvisement::query()->whereKey($propertyAdvisement->id)->exists())->toBeTrue();
});
