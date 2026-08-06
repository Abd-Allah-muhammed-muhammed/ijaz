<?php

use App\Enums\Providers\ProviderStatusEnum;
use App\Http\Resources\Api\V1\MediaResource;
use App\Models\Provider;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Modules\Marketplace\Models\ProviderType;
use Modules\Orders\Models\Order;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;

beforeEach(function () {
    config(['media-library.queue_conversions_after_database_commit' => false]);
    Storage::fake('public');
});

test('uploading an order image queues a webp conversion on the media-conversions queue', function () {
    Bus::fake();

    $order = Order::factory()->create();
    $order->addMedia(UploadedFile::fake()->image('order.jpg', 40, 40))
        ->toMediaCollection('default', 'public');

    Bus::assertDispatched(PerformConversionsJob::class, fn ($job) => $job->queue === 'media-conversions');
});

test('order MediaResource prefers webp url once generated and falls back to original while queued', function () {
    Bus::fake();

    $order = Order::factory()->create();
    $media = $order->addMedia(UploadedFile::fake()->image('order.jpg', 40, 40))
        ->toMediaCollection('default', 'public');

    $originalUrl = $media->getFullUrl();
    expect(MediaResource::make($media)->resolve()['url'])->toBe($originalUrl);

    $media->markAsConversionGenerated('webp');
    $media->refresh();

    expect(MediaResource::make($media)->resolve()['url'])
        ->toBe($media->getFullUrl('webp'))
        ->not->toBe($originalUrl);
});

test('order PDF-like non-images never queue webp conversion', function () {
    Bus::fake();

    $order = Order::factory()->create();
    $order->addMedia(UploadedFile::fake()->create('note.pdf', 50, 'application/pdf'))
        ->toMediaCollection('default', 'public');

    Bus::assertNotDispatched(PerformConversionsJob::class);
});

test('provider KYC documents on the local disk never queue webp conversion', function () {
    Bus::fake();
    Storage::fake('local');

    $providerType = ProviderType::query()->create([
        'image' => 'provider-types/test.png',
    ]);
    $providerType->translateOrNew(app()->getLocale())->name = 'Test Provider Type';
    $providerType->save();

    $provider = Provider::query()->create([
        'name' => 'KYC Provider',
        'iban' => 'SA'.fake()->unique()->numerify('################'),
        'logo' => 'providers/test.png',
        'password' => bcrypt('password'),
        'provider_type_id' => $providerType->id,
        'status' => ProviderStatusEnum::Pending,
    ]);

    $provider->addMedia(UploadedFile::fake()->image('id.jpg', 20, 20))
        ->toMediaCollection('id_image', 'local');

    Bus::assertNotDispatched(PerformConversionsJob::class);
});
