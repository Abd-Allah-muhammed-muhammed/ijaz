<?php

use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Http\Controllers\Dashboard\CarAdvisementController;
use Modules\Classifieds\Http\Controllers\Dashboard\PropertyAdvisementController;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\Models\PropertyAdvisement;

beforeEach(function () {
    withoutClassifiedsDashboardLocaleMiddleware();
});

test('admin without carAdvisements permission cannot access car advisement dashboard routes', function () {
    $admin = createClassifiedsDashboardAdmin(['show users']);
    $carAdvisement = CarAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PENDING,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([CarAdvisementController::class, 'index']))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->get(action([CarAdvisementController::class, 'show'], $carAdvisement))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->put(action([CarAdvisementController::class, 'update'], $carAdvisement), [
            'status' => AdvisementStatusEnum::PUBLISHED->value,
        ])
        ->assertForbidden();

    expect($carAdvisement->fresh()->status)->toBe(AdvisementStatusEnum::PENDING);
});

test('admin without propertyAdvisements permission cannot access property advisement dashboard routes', function () {
    $admin = createClassifiedsDashboardAdmin(['show users']);
    $propertyAdvisement = PropertyAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PENDING,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([PropertyAdvisementController::class, 'index']))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->get(action([PropertyAdvisementController::class, 'show'], $propertyAdvisement))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->put(action([PropertyAdvisementController::class, 'update'], $propertyAdvisement), [
            'status' => AdvisementStatusEnum::PUBLISHED->value,
        ])
        ->assertForbidden();

    expect($propertyAdvisement->fresh()->status)->toBe(AdvisementStatusEnum::PENDING);
});

test('admin with correct permission can update car advisement status', function () {
    $admin = createClassifiedsDashboardAdmin([
        'show carAdvisements',
        'edit carAdvisements',
    ]);
    $carAdvisement = CarAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PENDING,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([CarAdvisementController::class, 'index']))
        ->assertSuccessful();

    $this->actingAs($admin, 'admin')
        ->put(action([CarAdvisementController::class, 'update'], $carAdvisement), [
            'status' => AdvisementStatusEnum::PUBLISHED->value,
        ])
        ->assertRedirect();

    expect($carAdvisement->fresh()->status)->toBe(AdvisementStatusEnum::PUBLISHED);
});

test('admin with correct permission can update property advisement status', function () {
    $admin = createClassifiedsDashboardAdmin([
        'show propertyAdvisements',
        'edit propertyAdvisements',
    ]);
    $propertyAdvisement = PropertyAdvisement::factory()->create([
        'status' => AdvisementStatusEnum::PENDING,
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([PropertyAdvisementController::class, 'index']))
        ->assertSuccessful();

    $this->actingAs($admin, 'admin')
        ->put(action([PropertyAdvisementController::class, 'update'], $propertyAdvisement), [
            'status' => AdvisementStatusEnum::PUBLISHED->value,
        ])
        ->assertRedirect();

    expect($propertyAdvisement->fresh()->status)->toBe(AdvisementStatusEnum::PUBLISHED);
});
