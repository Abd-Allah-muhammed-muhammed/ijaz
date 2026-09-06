<?php

use App\Enums\ProviderTypeFilesEnum;
use App\Http\Controllers\Provider\AuthController;
use App\Models\Provider;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Modules\Marketplace\Models\ProviderType;

beforeEach(function (): void {
    withoutOrdersLocaleMiddleware();
    Storage::fake('public');
    Storage::fake('local');
});

/**
 * @return array{provider: Provider, providerType: ProviderType}
 */
function createApprovedProviderForFileUpload(): array
{
    $providerType = ProviderType::query()->create([
        'image' => 'provider-types/test.png',
        'files' => [
            ProviderTypeFilesEnum::ID_IMAGE->value => true,
            ProviderTypeFilesEnum::IBAN_CERTIFICATION->value => true,
            ProviderTypeFilesEnum::COMMERCIAL_RECORD->value => false,
            ProviderTypeFilesEnum::FREELANCER_CERTIFICATION->value => false,
            ProviderTypeFilesEnum::LICENSE_TO_PRACTICE_LAW->value => false,
        ],
        'translations' => [
            'en' => ['name' => 'Individual EN', 'description' => 'Desc EN'],
            'ar' => ['name' => 'Individual AR', 'description' => 'Desc AR'],
            'ur' => ['name' => 'Individual UR', 'description' => 'Desc UR'],
            'hi' => ['name' => 'Individual HI', 'description' => 'Desc HI'],
        ],
    ]);

    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);

    $provider = createWalletProvider([
        'name' => 'File Upload Provider',
        'provider_type_id' => $providerType->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
        'address' => 'Address',
        'phone' => Phone::make('512345671')->toString(),
        'email' => 'profile-file-upload@example.com',
        'iban' => 'SA0380000000608010167519',
        'about' => 'About',
        'logo' => 'providers/logo.jpg',
    ]);

    return compact('provider', 'providerType');
}

function fakeProfilePdf(string $name = 'doc.pdf', int $kilobytes = 100): UploadedFile
{
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'profile-upload-'.Str::random(8).'-'.$name;
    $padding = max(0, ($kilobytes * 1024) - 32);
    file_put_contents($path, "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n".str_repeat('A', $padding)."\n%%EOF\n");

    return new UploadedFile($path, $name, 'application/pdf', null, true);
}

test('authenticated provider can upload a required profile file', function (): void {
    $deps = createApprovedProviderForFileUpload();
    $field = ProviderTypeFilesEnum::ID_IMAGE->value;
    $file = fakeProfilePdf('id.pdf');

    $this->actingAs($deps['provider'], 'provider')
        ->post(action([AuthController::class, 'uploadProfileFile']), [
            'field' => $field,
            'file' => $file,
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.field', $field)
        ->assertJsonPath('data.file_name', 'id.pdf');

    $deps['provider']->refresh();

    expect($deps['provider']->getMedia($field))->toHaveCount(1)
        ->and($deps['provider']->getFirstMedia($field)?->file_name)->toBe('id.pdf');
});

test('profile file upload rejects invalid mime type', function (): void {
    $deps = createApprovedProviderForFileUpload();

    $this->actingAs($deps['provider'], 'provider')
        ->post(action([AuthController::class, 'uploadProfileFile']), [
            'field' => ProviderTypeFilesEnum::ID_IMAGE->value,
            'file' => UploadedFile::fake()->image('not-pdf.png'),
        ])
        ->assertSessionHasErrors('file');
});

test('profile file upload rejects oversized pdf', function (): void {
    $deps = createApprovedProviderForFileUpload();

    $this->actingAs($deps['provider'], 'provider')
        ->post(action([AuthController::class, 'uploadProfileFile']), [
            'field' => ProviderTypeFilesEnum::ID_IMAGE->value,
            'file' => fakeProfilePdf('big.pdf', 9000),
        ])
        ->assertSessionHasErrors('file');
});

test('profile file upload rejects fields not required for provider type', function (): void {
    $deps = createApprovedProviderForFileUpload();

    $this->actingAs($deps['provider'], 'provider')
        ->post(action([AuthController::class, 'uploadProfileFile']), [
            'field' => ProviderTypeFilesEnum::COMMERCIAL_RECORD->value,
            'file' => fakeProfilePdf(),
        ])
        ->assertSessionHasErrors('field');
});

test('profile file upload replaces existing media in the collection', function (): void {
    $deps = createApprovedProviderForFileUpload();
    $field = ProviderTypeFilesEnum::ID_IMAGE->value;

    $deps['provider']
        ->addMedia(fakeProfilePdf('old.pdf'))
        ->toMediaCollection($field, 'local');

    expect($deps['provider']->getMedia($field))->toHaveCount(1)
        ->and($deps['provider']->getFirstMedia($field)?->file_name)->toBe('old.pdf');

    $this->actingAs($deps['provider'], 'provider')
        ->post(action([AuthController::class, 'uploadProfileFile']), [
            'field' => $field,
            'file' => fakeProfilePdf('new.pdf'),
        ])
        ->assertSuccessful();

    $deps['provider']->refresh();

    expect($deps['provider']->getMedia($field))->toHaveCount(1)
        ->and($deps['provider']->getFirstMedia($field)?->file_name)->toBe('new.pdf');
});
