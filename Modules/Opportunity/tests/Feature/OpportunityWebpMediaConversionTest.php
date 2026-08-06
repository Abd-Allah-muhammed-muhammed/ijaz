<?php

use App\Http\Resources\Api\V1\MediaResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Modules\Opportunity\Http\Resources\Dashboard\OpportunityDashboardResource;
use Modules\Opportunity\Models\Opportunity;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;

beforeEach(function () {
    config(['media-library.queue_conversions_after_database_commit' => false]);
    Storage::fake('public');
});

test('uploading an opportunity image queues a webp conversion on the media-conversions queue', function () {
    Bus::fake();

    $opportunity = Opportunity::factory()->create();
    $opportunity->addMedia(UploadedFile::fake()->image('opp.jpg', 40, 40))
        ->toMediaCollection('files', 'public');

    Bus::assertDispatched(PerformConversionsJob::class, fn ($job) => $job->queue === 'media-conversions');
});

test('opportunity MediaResource prefers webp url once generated and falls back to original while queued', function () {
    Bus::fake();

    $opportunity = Opportunity::factory()->create();
    $media = $opportunity->addMedia(UploadedFile::fake()->image('opp.jpg', 40, 40))
        ->toMediaCollection('files', 'public');

    $originalUrl = $media->getFullUrl();
    expect(MediaResource::make($media)->resolve()['url'])->toBe($originalUrl);

    $media->markAsConversionGenerated('webp');
    $media->refresh();

    expect(MediaResource::make($media)->resolve()['url'])
        ->toBe($media->getFullUrl('webp'))
        ->not->toBe($originalUrl);
});

test('opportunity dashboard resource prefers webp url once generated', function () {
    Bus::fake();

    $opportunity = Opportunity::factory()->create();
    $media = $opportunity->addMedia(UploadedFile::fake()->image('dash.jpg', 30, 30))
        ->toMediaCollection('files', 'public');

    $opportunity->load('media');
    $before = OpportunityDashboardResource::make($opportunity)->resolve();
    expect($before['media'][0]['url'])->toBe($media->getFullUrl());

    $media->markAsConversionGenerated('webp');
    $media->refresh();
    $opportunity->load('media');

    $after = OpportunityDashboardResource::make($opportunity)->resolve();
    expect($after['media'][0]['url'])->toBe($media->getFullUrl('webp'));
});

test('opportunity PDF attachments never queue webp conversion', function () {
    Bus::fake();

    $opportunity = Opportunity::factory()->create();
    $opportunity->addMedia(UploadedFile::fake()->create('brief.pdf', 80, 'application/pdf'))
        ->toMediaCollection('files', 'public');

    Bus::assertNotDispatched(PerformConversionsJob::class);
});
