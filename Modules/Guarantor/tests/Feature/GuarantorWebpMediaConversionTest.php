<?php

use App\Http\Resources\Api\V1\MediaResource;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Storage;
use Modules\Guarantor\Enums\AuthorizationTypeEnum;
use Modules\Guarantor\Models\GuarantorCompanyDetail;
use Modules\Guarantor\Models\GuarantorRequest;
use Spatie\MediaLibrary\Conversions\Jobs\PerformConversionsJob;

beforeEach(function () {
    config(['media-library.queue_conversions_after_database_commit' => false]);
    Storage::fake('public');
});

test('uploading a guarantor files image queues a webp conversion on the media-conversions queue', function () {
    Bus::fake();

    $guarantor = GuarantorRequest::factory()->create();
    $guarantor->addMedia(UploadedFile::fake()->image('extra.jpg', 40, 40))
        ->toMediaCollection('files', 'public');

    Bus::assertDispatched(PerformConversionsJob::class, fn ($job) => $job->queue === 'media-conversions');
});

test('guarantor files MediaResource prefers webp url once generated and falls back to original while queued', function () {
    Bus::fake();

    $guarantor = GuarantorRequest::factory()->create();
    $media = $guarantor->addMedia(UploadedFile::fake()->image('extra.jpg', 40, 40))
        ->toMediaCollection('files', 'public');

    $originalUrl = $media->getFullUrl();
    expect(MediaResource::make($media)->resolve()['url'])->toBe($originalUrl);

    $media->markAsConversionGenerated('webp');
    $media->refresh();

    expect(MediaResource::make($media)->resolve()['url'])
        ->toBe($media->getFullUrl('webp'))
        ->not->toBe($originalUrl);
});

test('guarantor files PDF attachments never queue webp conversion', function () {
    Bus::fake();

    $guarantor = GuarantorRequest::factory()->create();
    $guarantor->addMedia(UploadedFile::fake()->create('doc.pdf', 80, 'application/pdf'))
        ->toMediaCollection('files', 'public');

    Bus::assertNotDispatched(PerformConversionsJob::class);
});

test('guarantor signature images are never sent through webp conversion', function () {
    Bus::fake();

    $guarantor = GuarantorRequest::factory()->create();
    $guarantor->addMedia(UploadedFile::fake()->image('signature.png', 20, 20))
        ->toMediaCollection('signature', 'public');

    Bus::assertNotDispatched(PerformConversionsJob::class);
});

test('guarantor company KYC documents are never sent through webp conversion', function () {
    Bus::fake();

    $guarantor = GuarantorRequest::factory()->company()->create();
    $detail = GuarantorCompanyDetail::query()->create([
        'guarantor_request_id' => $guarantor->id,
        'company_name' => 'Acme',
        'commercial_register' => '123',
        'authorized_name' => 'Auth',
        'authorized_id_number' => '1',
        'authorization_type' => AuthorizationTypeEnum::PowerOfAttorney,
        'requester_account_holder' => 'Holder',
        'requester_iban' => 'SA0380000000608010167519',
        'counterparty_account_holder' => 'CP Holder',
    ]);

    $detail->addMedia(UploadedFile::fake()->image('id.jpg', 20, 20))
        ->toMediaCollection('authorized_id', 'public');

    Bus::assertNotDispatched(PerformConversionsJob::class);
});
