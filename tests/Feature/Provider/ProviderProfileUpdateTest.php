<?php

use App\Enums\ProviderTypeFilesEnum;
use App\Http\Controllers\Provider\AuthController;
use App\Models\Provider;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\ProviderType;
use Modules\Marketplace\Models\Skill;

beforeEach(function (): void {
    withoutOrdersLocaleMiddleware();
    Storage::fake('public');
    Storage::fake('local');
});

/**
 * @return array{
 *     provider: Provider,
 *     providerType: ProviderType,
 *     region: Region,
 *     city: City,
 *     category: Category,
 *     skill: Skill,
 *     otherSkill: Skill,
 * }
 */
function createApprovedProviderForProfileUpdate(array $typeFiles = []): array
{
    $providerType = ProviderType::query()->create([
        'image' => 'provider-types/test.png',
        'files' => $typeFiles,
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
    $otherSkill = Skill::query()->create([
        'category_id' => $category->id,
        'translations' => [
            'en' => ['title' => 'Electrical'],
            'ar' => ['title' => 'كهرباء'],
            'ur' => ['title' => 'Electrical UR'],
            'hi' => ['title' => 'Electrical HI'],
        ],
    ]);

    $provider = createWalletProvider([
        'name' => 'Profile Provider Co',
        'provider_type_id' => $providerType->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
        'address' => 'Old address',
        'phone' => Phone::make('512345670')->toString(),
        'email' => 'profile-provider@example.com',
        'iban' => 'SA0380000000608010167519',
        'about' => 'Old about',
        'logo' => 'providers/old-logo.jpg',
    ]);

    Storage::disk('public')->put('providers/old-logo.jpg', 'old-logo');

    $provider->categories()->sync([$category->id]);
    $provider->categorySkills()->create([
        'category_id' => $category->id,
        'skill_id' => $skill->id,
    ]);

    return compact('provider', 'providerType', 'region', 'city', 'category', 'skill', 'otherSkill');
}

/**
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function providerProfileUpdatePayload(array $deps, array $overrides = []): array
{
    return [
        'name' => 'Updated Provider Co',
        'provider_type_id' => $deps['providerType']->id,
        'region_id' => $deps['region']->id,
        'city_id' => $deps['city']->id,
        'address' => 'New address',
        'phone' => '512345679',
        'email' => 'profile-provider@example.com',
        'iban' => 'SA0380000000608010167519',
        'about' => 'Updated about',
        'categories' => [
            ['id' => $deps['category']->id, 'skills' => [$deps['skill']->id]],
        ],
        ...$overrides,
    ];
}

test('provider can update their profile', function (): void {
    $deps = createApprovedProviderForProfileUpdate();

    $this->actingAs($deps['provider'], 'provider')
        ->from(action([AuthController::class, 'profile']))
        ->post(action([AuthController::class, 'updateProfile']), providerProfileUpdatePayload($deps))
        ->assertRedirect(route('provider.profile'))
        ->assertSessionHas('success', __('data updated successfully'));

    $deps['provider']->refresh();

    expect($deps['provider']->name)->toBe('Updated Provider Co')
        ->and($deps['provider']->about)->toBe('Updated about')
        ->and($deps['provider']->address)->toBe('New address')
        ->and($deps['provider']->phone)->toBe(Phone::make('512345679')->toString());
});

test('provider profile update replaces logo', function (): void {
    $deps = createApprovedProviderForProfileUpdate();
    $oldLogo = $deps['provider']->logo;

    $this->actingAs($deps['provider'], 'provider')
        ->post(action([AuthController::class, 'updateProfile']), providerProfileUpdatePayload($deps, [
            'logo' => UploadedFile::fake()->image('new-logo.jpg'),
        ]))
        ->assertRedirect(route('provider.profile'));

    $deps['provider']->refresh();

    expect($deps['provider']->logo)->not->toBe($oldLogo)
        ->and($deps['provider']->logo)->not->toBeNull();

    Storage::disk('public')->assertExists($deps['provider']->logo);
});

test('provider profile update syncs categories and skills', function (): void {
    $deps = createApprovedProviderForProfileUpdate();

    // Keep the existing skill and add another — exercises the createMany sync path
    // without hitting the Collection::delete gap on skill removal (present in both
    // the current controller and UpdateProviderAction).
    $this->actingAs($deps['provider'], 'provider')
        ->post(action([AuthController::class, 'updateProfile']), providerProfileUpdatePayload($deps, [
            'categories' => [
                ['id' => $deps['category']->id, 'skills' => [$deps['skill']->id, $deps['otherSkill']->id]],
            ],
        ]))
        ->assertRedirect(route('provider.profile'));

    $deps['provider']->refresh();

    $skillIds = $deps['provider']->skills()->pluck('skills.id')->all();

    expect($deps['provider']->categories()->pluck('categories.id')->all())->toContain($deps['category']->id)
        ->and($skillIds)->toContain($deps['skill']->id)
        ->and($skillIds)->toContain($deps['otherSkill']->id);
});

test('provider profile update persists license_to_practice_law', function (): void {
    $deps = createApprovedProviderForProfileUpdate([
        ProviderTypeFilesEnum::LICENSE_TO_PRACTICE_LAW->value => true,
    ]);

    $this->actingAs($deps['provider'], 'provider')
        ->post(action([AuthController::class, 'updateProfile']), providerProfileUpdatePayload($deps, [
            ProviderTypeFilesEnum::LICENSE_TO_PRACTICE_LAW->value => UploadedFile::fake()->create(
                'license.pdf',
                100,
                'application/pdf',
            ),
        ]))
        ->assertRedirect(route('provider.profile'));

    $deps['provider']->refresh();

    // Deliberate improvement from reusing UpdateProviderAction: all
    // ProviderTypeFilesEnum collections (including license_to_practice_law)
    // are now persisted — the old controller hard-coded only four collections.
    expect($deps['provider']->getMedia(ProviderTypeFilesEnum::LICENSE_TO_PRACTICE_LAW->value))->toHaveCount(1);
});
