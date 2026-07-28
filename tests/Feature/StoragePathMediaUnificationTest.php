<?php

use App\Actions\Provider\UpdateProviderAction;
use App\Contracts\Provider\ProviderManagementRepositoryInterface;
use App\DTOs\Provider\UpdateProviderDTO;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Cms\Models\Banner;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Modules\Marketplace\Actions\Category\StoreCategoryAction;
use Modules\Marketplace\DTOs\StoreCategoryDTO;
use Modules\Marketplace\Enums\CategoryFeesTypeEnum;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\ProviderType;
use Modules\Marketplace\Models\Skill;

/**
 * Storage-path media unification — documents three bugs then locks the fixes.
 *
 * Intentional URL/output changes after this work:
 * - Category: icon is stored on the public disk the accessor reads, so icon_url points
 *   at an existing file (previously store() used the default disk while iconUrl used public).
 * - Banner: empty image returns asset('media/avatars/blank.png') instead of asset(null)
 *   from the undeclared $default_image property.
 */
test('category icon url resolves correctly after upload', function (): void {
    // Force a non-public default so the old StoreCategoryAction::store('categories')
    // (no disk arg) writes to the wrong disk while iconUrl still reads 'public'.
    config(['filesystems.default' => 'local']);
    Storage::fake('local');
    Storage::fake('public');

    $icon = UploadedFile::fake()->image('icon.png');

    $category = app(StoreCategoryAction::class)->handle(new StoreCategoryDTO(
        parentId: null,
        translations: [
            'en' => ['title' => 'Plumbing', 'description' => null],
            'ar' => ['title' => 'سباكة', 'description' => null],
            'ur' => ['title' => 'Plumbing UR', 'description' => null],
            'hi' => ['title' => 'Plumbing HI', 'description' => null],
        ],
        feesType: CategoryFeesTypeEnum::FIXED,
        fees: 10.0,
        icon: $icon,
    ));

    expect($category->icon)->not->toBeNull()
        ->and(Storage::disk('public')->exists($category->icon))->toBeTrue()
        ->and($category->icon_url)->toContain($category->icon);
});

test('banner returns a valid default image when none is set', function (): void {
    // image is NOT NULL in DB; exercise the accessor with a blank in-memory value
    // (the bug was undeclared $default_image used when image is empty).
    $banner = new Banner(['link' => 'https://example.com', 'image' => null]);

    $url = $banner->image_url;

    expect($url)->toBeString()
        ->and($url)->not->toBeEmpty()
        ->and($url)->toBe(asset('media/avatars/blank.png'));
});

test('failed provider logo update preserves the old logo', function (): void {
    Storage::fake('public');

    $providerType = ProviderType::query()->create([
        'image' => 'provider-types/test.png',
        'files' => [],
        'translations' => [
            'en' => ['name' => 'Individual EN', 'description' => 'Desc EN'],
            'ar' => ['name' => 'Individual AR', 'description' => 'Desc AR'],
            'ur' => ['name' => 'Individual UR', 'description' => 'Desc UR'],
            'hi' => ['name' => 'Individual HI', 'description' => 'Desc HI'],
        ],
    ]);
    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);
    $category = Category::factory()->create();
    $skill = Skill::query()->create([
        'category_id' => $category->id,
        'translations' => [
            'en' => ['title' => 'Plumbing'],
            'ar' => ['title' => 'سباكة'],
            'ur' => ['title' => 'Plumbing UR'],
            'hi' => ['title' => 'Plumbing HI'],
        ],
    ]);

    $oldLogo = 'providers/old-logo.jpg';
    Storage::disk('public')->put($oldLogo, 'old-logo-bytes');

    $provider = createWalletProvider([
        'logo' => $oldLogo,
        'provider_type_id' => $providerType->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
        'address' => 'Addr',
        'phone' => Phone::make('512345670')->toString(),
        'email' => 'logo-preserve@example.com',
        'iban' => 'SA0380000000608010167519',
        'about' => 'About',
    ]);
    $provider->categories()->sync([$category->id]);

    $repository = Mockery::mock(ProviderManagementRepositoryInterface::class);
    $repository->shouldReceive('update')
        ->once()
        ->andThrow(new RuntimeException('forced update failure'));
    $this->app->instance(ProviderManagementRepositoryInterface::class, $repository);

    $dto = new UpdateProviderDTO(
        name: $provider->name,
        provider_type_id: $provider->provider_type_id,
        region_id: $provider->region_id,
        city_id: $provider->city_id,
        address: $provider->address,
        phone: '512345679',
        email: $provider->email,
        iban: $provider->iban,
        about: $provider->about,
        password: null,
        logo: UploadedFile::fake()->image('new-logo.jpg'),
        categories: [['id' => $category->id, 'skills' => [$skill->id]]],
        mediaFiles: [],
    );

    try {
        app(UpdateProviderAction::class)->handle($provider, $dto);
        expect(false)->toBeTrue('expected update to throw');
    } catch (RuntimeException $e) {
        expect($e->getMessage())->toBe('forced update failure');
    }

    expect(Storage::disk('public')->exists($oldLogo))->toBeTrue();
});
