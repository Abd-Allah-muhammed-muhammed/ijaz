<?php

use App\Http\Resources\Api\V1\MediaResource;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Models\Region;
use Modules\Jobs\Enums\JobTypeEnum;
use Modules\Jobs\Models\JobOffer;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;

beforeEach(function () {
    config(['media-library.queue_conversions_after_database_commit' => false]);
    Storage::fake('public');
});

function createJobOfferForWebpTest(): JobOffer
{
    $user = User::factory()->create();
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $nationality = Nationality::query()->create([
        'code' => 'KW',
        'is_active' => true,
        'translations' => [
            'en' => ['name' => 'Kuwaiti'],
            'ar' => ['name' => 'كويتي'],
            'ur' => ['name' => 'Kuwaiti UR'],
            'hi' => ['name' => 'Kuwaiti HI'],
        ],
    ]);

    return JobOffer::query()->create([
        'user_id' => $user->id,
        'user_type' => User::class,
        'title' => 'WebP Job',
        'description' => 'Desc',
        'expired_at' => now()->addDays(10),
        'contact_number' => '0501234567',
        'city_id' => $city->id,
        'region_id' => $region->id,
        'nationality_id' => $nationality->id,
        'type' => JobTypeEnum::Private,
        'expected_salary' => 3000,
    ]);
}

test('uploading a job offer image queues a webp conversion on the media-conversions queue', function () {
    Bus::fake();

    $job = createJobOfferForWebpTest();
    $job->addMedia(UploadedFile::fake()->image('job.jpg', 40, 40))
        ->toMediaCollection('default', 'public');

    Bus::assertDispatched(PerformConversionsJob::class, fn ($job) => $job->queue === 'media-conversions');
});

test('job offer MediaResource prefers webp url once generated and falls back to original while queued', function () {
    Bus::fake();

    $job = createJobOfferForWebpTest();
    $media = $job->addMedia(UploadedFile::fake()->image('job.jpg', 40, 40))
        ->toMediaCollection('default', 'public');

    $originalUrl = $media->getFullUrl();
    expect(MediaResource::make($media)->resolve()['url'])->toBe($originalUrl);

    $media->markAsConversionGenerated('webp');
    $media->refresh();

    expect(MediaResource::make($media)->resolve()['url'])
        ->toBe($media->getFullUrl('webp'))
        ->not->toBe($originalUrl);
});

test('job offer PDF attachments never queue webp conversion', function () {
    Bus::fake();

    $job = createJobOfferForWebpTest();
    $job->addMedia(UploadedFile::fake()->create('cv.pdf', 80, 'application/pdf'))
        ->toMediaCollection('default', 'public');

    Bus::assertNotDispatched(PerformConversionsJob::class);
});
