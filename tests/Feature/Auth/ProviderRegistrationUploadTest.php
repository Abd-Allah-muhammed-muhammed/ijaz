<?php

use App\Enums\Auth\OtpPurposeEnum;
use App\Enums\ProviderTypeFilesEnum;
use App\Models\Otp;
use App\Models\Provider;
use App\Models\ProviderRegistrationUpload;
use App\Support\Auth\ProviderRegistrationFileRules;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\ProviderType;

beforeEach(function () {
    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    $this->withoutVite();

    Storage::fake('public');
    Storage::fake('local');
    Storage::fake(config('provider_registration.temp_disk'));
});

function registrationUploadToken(): string
{
    return (string) Str::uuid();
}

function fakePdfUploadedFile(string $name = 'doc.pdf', int $kilobytes = 100): UploadedFile
{
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'reg-upload-'.Str::random(8).'-'.$name;
    $padding = max(0, ($kilobytes * 1024) - 32);
    file_put_contents($path, "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n".str_repeat('A', $padding)."\n%%EOF\n");

    return new UploadedFile($path, $name, 'application/pdf', null, true);
}

/**
 * @return array{token: string, upload: ProviderRegistrationUpload}
 */
function storeRegistrationUploadViaHttp(string $field, ?UploadedFile $file = null, ?string $token = null): array
{
    $token ??= registrationUploadToken();
    $file ??= $field === ProviderRegistrationFileRules::LOGO_FIELD
        ? UploadedFile::fake()->image('logo.png', 200, 200)
        : fakePdfUploadedFile();

    $response = test()->post(
        route('provider.register.uploads.store', ['token' => $token]),
        [
            'field' => $field,
            'file' => $file,
        ],
    );

    $response->assertSuccessful();

    $id = (int) $response->json('data.id');

    return [
        'token' => $token,
        'upload' => ProviderRegistrationUpload::query()->findOrFail($id),
    ];
}

test('upload endpoint accepts a valid logo', function () {
    $result = storeRegistrationUploadViaHttp(ProviderRegistrationFileRules::LOGO_FIELD);

    expect($result['upload']->field)->toBe('logo')
        ->and($result['upload']->token)->toBe($result['token'])
        ->and(Storage::disk(config('provider_registration.temp_disk'))->exists($result['upload']->path))->toBeTrue();
});

test('upload endpoint accepts a valid certificate pdf', function () {
    $result = storeRegistrationUploadViaHttp(ProviderTypeFilesEnum::ID_IMAGE->value);

    expect($result['upload']->field)->toBe(ProviderTypeFilesEnum::ID_IMAGE->value);
});

test('upload endpoint rejects oversized files', function () {
    $token = registrationUploadToken();
    $file = fakePdfUploadedFile('big.pdf', 9000);

    test()->post(route('provider.register.uploads.store', ['token' => $token]), [
        'field' => ProviderTypeFilesEnum::ID_IMAGE->value,
        'file' => $file,
    ])->assertSessionHasErrors('file');
});

test('upload endpoint rejects content sniffing mismatches', function () {
    $token = registrationUploadToken();
    $path = sys_get_temp_dir().DIRECTORY_SEPARATOR.'fake-pdf-'.Str::random(8).'.pdf';
    file_put_contents($path, '<?php echo "not a pdf";');
    $file = new UploadedFile($path, 'spoof.pdf', 'application/pdf', null, true);

    test()->post(route('provider.register.uploads.store', ['token' => $token]), [
        'field' => ProviderTypeFilesEnum::IBAN_CERTIFICATION->value,
        'file' => $file,
    ])->assertSessionHasErrors('file');

    @unlink($path);
});

test('upload endpoint rejects invalid uuid tokens before file io', function () {
    test()->post('/en/provider/register/uploads/not-a-uuid', [
        'field' => 'logo',
        'file' => UploadedFile::fake()->image('logo.png'),
    ])->assertNotFound();
});

test('upload endpoint enforces max uploads per token', function () {
    config(['provider_registration.max_uploads_per_token' => 2]);
    $token = registrationUploadToken();

    storeRegistrationUploadViaHttp('logo', null, $token);
    storeRegistrationUploadViaHttp(ProviderTypeFilesEnum::ID_IMAGE->value, null, $token);

    test()->post(route('provider.register.uploads.store', ['token' => $token]), [
        'field' => ProviderTypeFilesEnum::COMMERCIAL_RECORD->value,
        'file' => fakePdfUploadedFile('cr.pdf', 100),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('uploads');
});

test('upload endpoint enforces max bytes per token', function () {
    config(['provider_registration.max_bytes_per_token' => 50_000]);
    $token = registrationUploadToken();

    storeRegistrationUploadViaHttp(
        'logo',
        UploadedFile::fake()->image('logo.png')->size(40),
        $token,
    );

    test()->post(route('provider.register.uploads.store', ['token' => $token]), [
        'field' => ProviderTypeFilesEnum::ID_IMAGE->value,
        'file' => fakePdfUploadedFile('id.pdf', 40),
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('uploads');
});

test('delete endpoint removes temp file and db row', function () {
    $result = storeRegistrationUploadViaHttp('logo');
    $path = $result['upload']->path;

    test()->delete(route('provider.register.uploads.destroy', [
        'token' => $result['token'],
        'upload' => $result['upload']->id,
    ]))->assertSuccessful();

    expect(ProviderRegistrationUpload::query()->find($result['upload']->id))->toBeNull()
        ->and(Storage::disk(config('provider_registration.temp_disk'))->exists($path))->toBeFalse();
});

test('final registration resolves upload references including license_to_practice_law', function () {
    $type = ProviderType::query()->create([
        'image' => 'media/test-type.png',
        'files' => [
            'id_image' => true,
            'commercial_record' => true,
            'freelancer_certification' => false,
            'iban_certification' => true,
            'license_to_practice_law' => true,
        ],
    ]);
    $type->translations()->create(['locale' => 'en', 'name' => 'Law']);

    $token = registrationUploadToken();
    $logo = storeRegistrationUploadViaHttp('logo', null, $token);
    $idImage = storeRegistrationUploadViaHttp(ProviderTypeFilesEnum::ID_IMAGE->value, null, $token);
    $commercial = storeRegistrationUploadViaHttp(ProviderTypeFilesEnum::COMMERCIAL_RECORD->value, null, $token);
    $iban = storeRegistrationUploadViaHttp(ProviderTypeFilesEnum::IBAN_CERTIFICATION->value, null, $token);
    $license = storeRegistrationUploadViaHttp(ProviderTypeFilesEnum::LICENSE_TO_PRACTICE_LAW->value, null, $token);

    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $category = Category::factory()->create();

    $phone = '512345678';
    Otp::query()->updateOrCreate(
        [
            'phone' => Phone::make($phone)->toString(),
            'purpose' => OtpPurposeEnum::ProviderRegistration,
        ],
        [
            'subject_type' => null,
            'subject_id' => null,
            'token' => '1234',
            'expires_at' => now()->addMinutes(5),
        ],
    );

    test()->from(route('auth.register'))
        ->post(route('auth.register.submit'), [
            'name' => 'Law Firm',
            'provider_type_id' => $type->id,
            'region_id' => $region->id,
            'city_id' => $city->id,
            'address' => 'Riyadh',
            'phone' => $phone,
            'email' => 'law@example.com',
            'iban' => 'SA0380000000608010167519',
            'about' => 'Legal services',
            'password' => 'password',
            'password_confirmation' => 'password',
            'categories' => [['id' => $category->id]],
            'otp' => '1234',
            'upload_token' => $token,
            'uploads' => [
                'logo' => $logo['upload']->id,
                'id_image' => $idImage['upload']->id,
                'commercial_record' => $commercial['upload']->id,
                'iban_certification' => $iban['upload']->id,
                'license_to_practice_law' => $license['upload']->id,
            ],
        ])
        ->assertRedirect(route('auth.register'))
        ->assertSessionHas('success');

    $provider = Provider::query()->where('email', 'law@example.com')->first();

    expect($provider)->not->toBeNull()
        ->and($provider->getMedia(ProviderTypeFilesEnum::LICENSE_TO_PRACTICE_LAW->value))->toHaveCount(1)
        ->and($provider->getMedia(ProviderTypeFilesEnum::ID_IMAGE->value))->toHaveCount(1)
        ->and(ProviderRegistrationUpload::query()->where('token', $token)->count())->toBe(0);
});

test('final registration fails with field-identifiable error for missing upload reference', function () {
    $type = ProviderType::query()->create([
        'image' => 'media/test-type.png',
        'files' => [
            'id_image' => true,
            'commercial_record' => false,
            'freelancer_certification' => false,
            'iban_certification' => false,
            'license_to_practice_law' => false,
        ],
    ]);
    $type->translations()->create(['locale' => 'en', 'name' => 'Individual']);

    $token = registrationUploadToken();
    $logo = storeRegistrationUploadViaHttp('logo', null, $token);

    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $category = Category::factory()->create();
    $phone = '512345679';
    Otp::query()->updateOrCreate(
        [
            'phone' => Phone::make($phone)->toString(),
            'purpose' => OtpPurposeEnum::ProviderRegistration,
        ],
        [
            'subject_type' => null,
            'subject_id' => null,
            'token' => '1234',
            'expires_at' => now()->addMinutes(5),
        ],
    );

    test()->from(route('auth.register'))
        ->post(route('auth.register.submit'), [
            'name' => 'Solo',
            'provider_type_id' => $type->id,
            'region_id' => $region->id,
            'city_id' => $city->id,
            'address' => 'Riyadh',
            'phone' => $phone,
            'email' => 'solo@example.com',
            'iban' => 'SA0380000000608010167519',
            'about' => 'Services',
            'password' => 'password',
            'password_confirmation' => 'password',
            'categories' => [['id' => $category->id]],
            'otp' => '1234',
            'upload_token' => $token,
            'uploads' => [
                'logo' => $logo['upload']->id,
                'id_image' => 999999,
            ],
        ])
        ->assertRedirect(route('auth.register'))
        ->assertSessionHasErrors('uploads.id_image');

    expect(Provider::query()->where('email', 'solo@example.com')->exists())->toBeFalse();
});

test('cleanup command removes only uploads older than retention window', function () {
    $disk = config('provider_registration.temp_disk');
    $freshPath = 'uploads/fresh.bin';
    $oldPath = 'uploads/old.bin';
    Storage::disk($disk)->put($freshPath, 'fresh');
    Storage::disk($disk)->put($oldPath, 'old');

    $fresh = ProviderRegistrationUpload::query()->create([
        'token' => registrationUploadToken(),
        'field' => 'logo',
        'path' => $freshPath,
        'original_name' => 'fresh.png',
        'mime_type' => 'image/png',
        'size' => 5,
        'created_at' => now(),
    ]);

    $old = ProviderRegistrationUpload::query()->create([
        'token' => registrationUploadToken(),
        'field' => 'logo',
        'path' => $oldPath,
        'original_name' => 'old.png',
        'mime_type' => 'image/png',
        'size' => 5,
        'created_at' => now()->subHours((int) config('provider_registration.retention_hours') + 1),
    ]);

    Artisan::call('auth:prune-provider-registration-uploads');

    expect(ProviderRegistrationUpload::query()->find($fresh->id))->not->toBeNull()
        ->and(ProviderRegistrationUpload::query()->find($old->id))->toBeNull()
        ->and(Storage::disk($disk)->exists($freshPath))->toBeTrue()
        ->and(Storage::disk($disk)->exists($oldPath))->toBeFalse();
});
