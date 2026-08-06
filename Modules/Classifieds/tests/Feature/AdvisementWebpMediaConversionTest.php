<?php

use App\Http\Resources\Api\V1\MediaResource;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Modules\Catalog\Models\DeviceCategory;
use Modules\Catalog\Models\ElectronicBrand;
use Modules\Catalog\Models\Specialization;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Enums\ElectronicConditionEnum;
use Modules\Classifieds\Enums\InstituteTypeEnum;
use Modules\Classifieds\Enums\StudyLevelEnum;
use Modules\Classifieds\Enums\StudyTypeEnum;
use Modules\Classifieds\Models\CarAdvisement;
use Modules\Classifieds\Models\ElectronicAdvisement;
use Modules\Classifieds\Models\InstituteAdvisement;
use Modules\Classifieds\Models\PropertyAdvisement;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;
use Spatie\MediaLibrary\HasMedia;

beforeEach(function () {
    config(['media-library.queue_conversions_after_database_commit' => false]);
    Storage::fake('public');
});

function createAdvisementForWebp(string $type): HasMedia
{
    return match ($type) {
        'car' => CarAdvisement::factory()->create(),
        'property' => PropertyAdvisement::factory()->create(),
        'electronic' => (function () {
            $region = Region::factory()->create();
            $city = City::factory()->create(['region_id' => $region->id]);
            $category = DeviceCategory::query()->create(['icon' => 'icons/test.png']);
            $category->translateOrNew('en')->title = 'Phones';
            $category->save();
            $brand = ElectronicBrand::query()->create(['image' => 'brands/test.png', 'is_active' => true]);
            $brand->translateOrNew('en')->name = 'Test Brand';
            $brand->save();

            return ElectronicAdvisement::query()->create([
                'title' => 'Electronic item',
                'normalized_title' => 'electronic-item',
                'description' => 'A device',
                'normalized_description' => 'a-device',
                'status' => AdvisementStatusEnum::PUBLISHED,
                'condition' => ElectronicConditionEnum::NEW,
                'price' => 50,
                'show_price' => true,
                'phone' => '966501234567',
                'user_type' => User::class,
                'user_id' => User::factory()->create()->id,
                'device_category_id' => $category->id,
                'electronic_brand_id' => $brand->id,
                'city_id' => $city->id,
                'region_id' => $region->id,
                'options' => [],
            ]);
        })(),
        'institute' => (function () {
            $region = Region::factory()->create();
            $city = City::factory()->create(['region_id' => $region->id]);
            $specialization = Specialization::factory()->create();

            return InstituteAdvisement::query()->create([
                'title' => 'Institute course',
                'normalized_title' => 'institute-course',
                'description' => 'A course',
                'normalized_description' => 'a-course',
                'status' => AdvisementStatusEnum::PUBLISHED,
                'price' => 50,
                'type' => InstituteTypeEnum::INSTITUTE,
                'study_type' => StudyTypeEnum::ONSITE,
                'study_level' => StudyLevelEnum::CERTIFICATE,
                'phone' => '966501234567',
                'user_type' => User::class,
                'user_id' => User::factory()->create()->id,
                'specialization_id' => $specialization->id,
                'city_id' => $city->id,
                'region_id' => $region->id,
                'options' => [],
            ]);
        })(),
        default => throw new InvalidArgumentException($type),
    };
}

dataset('advisement_types', [
    'car' => ['car'],
    'property' => ['property'],
    'electronic' => ['electronic'],
    'institute' => ['institute'],
]);

test('uploading advisement image queues a webp conversion on the media-conversions queue', function (string $type) {
    Bus::fake();

    $model = createAdvisementForWebp($type);
    $model->addMedia(UploadedFile::fake()->image('ad.jpg', 40, 40))
        ->toMediaCollection('default', 'public');

    Bus::assertDispatched(PerformConversionsJob::class, fn ($job) => $job->queue === 'media-conversions');
})->with('advisement_types');

test('advisement MediaResource prefers webp url once generated and falls back to original while queued', function (string $type) {
    Bus::fake();

    $model = createAdvisementForWebp($type);
    $media = $model->addMedia(UploadedFile::fake()->image('ad.jpg', 40, 40))
        ->toMediaCollection('default', 'public');

    $originalUrl = $media->getFullUrl();
    expect(MediaResource::make($media)->resolve()['url'])->toBe($originalUrl);

    $media->markAsConversionGenerated('webp');
    $media->refresh();

    expect(MediaResource::make($media)->resolve()['url'])
        ->toBe($media->getFullUrl('webp'))
        ->not->toBe($originalUrl);
})->with('advisement_types');

test('institute advisement PDF attachments never queue webp conversion', function () {
    Bus::fake();

    $model = createAdvisementForWebp('institute');
    $model->addMedia(UploadedFile::fake()->create('license.pdf', 80, 'application/pdf'))
        ->toMediaCollection('default', 'public');

    Bus::assertNotDispatched(PerformConversionsJob::class);
});
