<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Actions\CarBrand\StoreCarBrandAction;
use Modules\Catalog\Actions\CarBrand\UpdateCarBrandAction;
use Modules\Catalog\Actions\CarCategory\StoreCarCategoryAction;
use Modules\Catalog\Actions\DeviceCategory\StoreDeviceCategoryAction;
use Modules\Catalog\Contracts\Repositories\CarBrandRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\CarCategoryRepositoryInterface;
use Modules\Catalog\Contracts\Repositories\DeviceCategoryRepositoryInterface;
use Modules\Catalog\DTOs\StoreCarBrandDTO;
use Modules\Catalog\DTOs\StoreCarCategoryDTO;
use Modules\Catalog\DTOs\StoreDeviceCategoryDTO;
use Modules\Catalog\DTOs\UpdateCarBrandDTO;
use Modules\Catalog\Models\CarBrand;

/**
 * Regression lock for Catalog transactional file uploads.
 *
 * Before the fix, Store*DTO::fromRequest() wrote files to disk before the DB
 * transaction began. A repository failure left orphaned files with no DB row.
 * These tests assert the fixed behavior: no orphan on store failure, and Update
 * keeps the old file while discarding the new upload when the transaction fails.
 */
test('failed car brand store does not leave an orphaned image on disk', function (): void {
    Storage::fake('public');

    $this->mock(CarBrandRepositoryInterface::class, function ($mock): void {
        $mock->shouldReceive('create')
            ->once()
            ->andThrow(new RuntimeException('forced store failure'));
    });

    $dto = new StoreCarBrandDTO(
        translations: [
            'en' => ['name' => 'Toyota'],
            'ar' => ['name' => 'تويوتا'],
        ],
        image: UploadedFile::fake()->image('brand.png'),
        isActive: true,
    );

    expect(fn () => app(StoreCarBrandAction::class)->handle($dto))
        ->toThrow(RuntimeException::class, 'forced store failure');

    expect(Storage::disk('public')->allFiles('car_brands'))->toBeEmpty();
});

test('failed car category store does not leave an orphaned icon on disk', function (): void {
    Storage::fake();

    $this->mock(CarCategoryRepositoryInterface::class, function ($mock): void {
        $mock->shouldReceive('create')
            ->once()
            ->andThrow(new RuntimeException('forced store failure'));
    });

    $dto = new StoreCarCategoryDTO(
        translations: [
            'en' => ['title' => 'Sedan'],
            'ar' => ['title' => 'سيدان'],
        ],
        icon: UploadedFile::fake()->image('icon.png'),
        parentId: null,
    );

    expect(fn () => app(StoreCarCategoryAction::class)->handle($dto))
        ->toThrow(RuntimeException::class, 'forced store failure');

    expect(Storage::allFiles('car_categories'))->toBeEmpty();
});

test('failed device category store does not leave an orphaned icon on disk', function (): void {
    Storage::fake();

    $this->mock(DeviceCategoryRepositoryInterface::class, function ($mock): void {
        $mock->shouldReceive('create')
            ->once()
            ->andThrow(new RuntimeException('forced store failure'));
    });

    $dto = new StoreDeviceCategoryDTO(
        translations: [
            ['locale' => 'en', 'title' => 'Phones'],
            ['locale' => 'ar', 'title' => 'هواتف'],
        ],
        icon: UploadedFile::fake()->image('icon.png'),
        parentId: null,
    );

    expect(fn () => app(StoreDeviceCategoryAction::class)->handle($dto))
        ->toThrow(RuntimeException::class, 'forced store failure');

    expect(Storage::allFiles('device_categories'))->toBeEmpty();
});

test('failed car brand update keeps the old image file, discards new upload', function (): void {
    Storage::fake('public');

    $oldPath = UploadedFile::fake()->image('old-brand.png')->store('car_brands', 'public');
    expect(Storage::disk('public')->exists($oldPath))->toBeTrue();

    $carBrand = CarBrand::factory()->create(['image' => $oldPath]);

    $this->mock(CarBrandRepositoryInterface::class, function ($mock) use ($carBrand): void {
        $mock->shouldReceive('update')
            ->once()
            ->withArgs(fn (CarBrand $model): bool => $model->is($carBrand))
            ->andThrow(new RuntimeException('forced update failure'));
    });

    $dto = new UpdateCarBrandDTO(
        translations: [
            'en' => ['name' => 'Updated'],
            'ar' => ['name' => 'محدث'],
        ],
        image: UploadedFile::fake()->image('new-brand.png'),
        isActive: true,
    );

    expect(fn () => app(UpdateCarBrandAction::class)->handle($carBrand, $dto))
        ->toThrow(RuntimeException::class, 'forced update failure');

    expect(Storage::disk('public')->exists($oldPath))->toBeTrue()
        ->and(Storage::disk('public')->allFiles('car_brands'))->toHaveCount(1)
        ->and(Storage::disk('public')->allFiles('car_brands')[0])->toBe($oldPath);

    expect($carBrand->fresh()->image)->toBe($oldPath);
});
