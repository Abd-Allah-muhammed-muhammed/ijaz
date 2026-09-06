<?php

use App\Support\Media\ReplaceMediaOnModel;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Opportunity\Models\Opportunity;

beforeEach(function (): void {
    Storage::fake('public');
    Storage::fake('local');
});

test('ReplaceMediaOnModel attaches a file to a media collection', function (): void {
    $opportunity = Opportunity::factory()->create();
    $file = UploadedFile::fake()->create('brief.pdf', 100, 'application/pdf');

    $media = app(ReplaceMediaOnModel::class)->attach(
        model: $opportunity,
        collection: 'files',
        file: $file,
        disk: 'public',
        replace: true,
    );

    expect($media->collection_name)->toBe('files')
        ->and($opportunity->fresh()->getMedia('files'))->toHaveCount(1)
        ->and($opportunity->fresh()->getFirstMedia('files')?->file_name)->toBe('brief.pdf');
});

test('ReplaceMediaOnModel clears existing media when replace is true', function (): void {
    $opportunity = Opportunity::factory()->create();
    $helper = app(ReplaceMediaOnModel::class);

    $helper->attach(
        model: $opportunity,
        collection: 'files',
        file: UploadedFile::fake()->create('old.pdf', 50, 'application/pdf'),
        disk: 'public',
        replace: true,
    );

    $helper->attach(
        model: $opportunity,
        collection: 'files',
        file: UploadedFile::fake()->create('new.pdf', 50, 'application/pdf'),
        disk: 'public',
        replace: true,
    );

    expect($opportunity->fresh()->getMedia('files'))->toHaveCount(1)
        ->and($opportunity->fresh()->getFirstMedia('files')?->file_name)->toBe('new.pdf');
});

test('ReplaceMediaOnModel can append without clearing when replace is false', function (): void {
    $opportunity = Opportunity::factory()->create();
    $helper = app(ReplaceMediaOnModel::class);

    $helper->attach(
        model: $opportunity,
        collection: 'files',
        file: UploadedFile::fake()->create('one.pdf', 50, 'application/pdf'),
        disk: 'public',
        replace: false,
    );

    $helper->attach(
        model: $opportunity,
        collection: 'files',
        file: UploadedFile::fake()->create('two.pdf', 50, 'application/pdf'),
        disk: 'public',
        replace: false,
    );

    expect($opportunity->fresh()->getMedia('files'))->toHaveCount(2);
});
