<?php

use App\Enums\Providers\ProviderStatusEnum;
use App\Http\Controllers\Api\V1\PlatformController;
use App\Http\Controllers\Api\V1\User\ProviderController as ApiUserProviderController;
use App\Http\Controllers\Dashboard\ProviderController;
use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;
use App\Support\Phone;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\ProviderType;
use Modules\Marketplace\Models\Skill;
use Spatie\Permission\Models\Permission;

beforeEach(function (): void {
    withoutOrdersLocaleMiddleware();
});

function createProviderManagementAdmin(array $permissions): Admin
{
    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'admin',
        ], [
            'group' => 'providers',
        ]);
    }

    $admin = Admin::query()->create([
        'name' => 'Providers Admin',
        'phone' => fake()->unique()->numerify('05########'),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    $admin->givePermissionTo($permissions);

    return $admin;
}

/**
 * @return array{providerType: ProviderType, region: Region, city: City, category: Category, skill: Skill}
 */
function createProviderManagementFormDeps(): array
{
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

    return compact('providerType', 'region', 'city', 'category', 'skill');
}

it('lists providers with the prams inertia prop', function (): void {
    $admin = createProviderManagementAdmin(['show providers']);
    createWalletProvider(['name' => 'Listed Provider Co']);

    $this->actingAs($admin, 'admin')
        ->get(action([ProviderController::class, 'index'], ['search' => 'Listed Provider']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Providers/Index')
            ->has('prams')
            ->has('rows.data', 1)
            ->has('stats')
            ->where('stats.total', fn ($total) => $total >= 1)
        );
});

it('passes true provider status totals on the index, not page-scoped approximations', function (): void {
    $admin = createProviderManagementAdmin(['show providers']);

    createWalletProvider(['status' => ProviderStatusEnum::Approved]);
    createWalletProvider(['status' => ProviderStatusEnum::Approved]);
    createWalletProvider(['status' => ProviderStatusEnum::Pending]);
    createWalletProvider(['status' => ProviderStatusEnum::Blocked]);

    // Force pagination below the true total so page-scoped client filters would undercount.
    $this->actingAs($admin, 'admin')
        ->get(action([ProviderController::class, 'index'], ['per_page' => 1]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Providers/Index')
            ->has('rows.data', 1)
            ->where('stats.total', 4)
            ->where('stats.approved', 2)
            ->where('stats.pending', 1)
            ->where('stats.blocked', 1)
        );
});

it('serves marketplace and geo dropdowns on the create form', function (): void {
    $admin = createProviderManagementAdmin(['create providers']);
    createProviderManagementFormDeps();

    $this->actingAs($admin, 'admin')
        ->get(action([ProviderController::class, 'create']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Providers/Create')
            ->has('types', 1)
            ->has('regions', 1)
            ->has('cities', 1)
        );
});

it('renders a provider with its wallet transactions', function (): void {
    $admin = createProviderManagementAdmin(['show providers']);
    $provider = createWalletProvider();

    $this->actingAs($admin, 'admin')
        ->get(action([ProviderController::class, 'show'], $provider))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Providers/Show')
            ->has('provider')
            ->has('transactions')
            ->has('prams')
        );
});

it('stores a provider with normalized phone, pending status, and synced categories', function (): void {
    Storage::fake('public');
    Storage::fake('local');

    $admin = createProviderManagementAdmin(['create providers']);
    $deps = createProviderManagementFormDeps();

    $this->actingAs($admin, 'admin')
        ->post(action([ProviderController::class, 'store']), [
            'name' => 'New Provider Co',
            'provider_type_id' => $deps['providerType']->id,
            'region_id' => $deps['region']->id,
            'city_id' => $deps['city']->id,
            'address' => 'Riyadh Street 1',
            'phone' => '512345678',
            'email' => 'provider@example.com',
            'iban' => 'SA0380000000608010167519',
            'about' => 'About the provider',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'logo' => UploadedFile::fake()->image('logo.jpg'),
            'categories' => [
                ['id' => $deps['category']->id, 'skills' => [$deps['skill']->id]],
            ],
        ])
        ->assertRedirect(route('dashboard.providers.index'));

    $created = Provider::query()->where('email', 'provider@example.com')->firstOrFail();

    expect($created->phone)->toBe(Phone::make('512345678')->toString())
        ->and($created->status)->toBe(ProviderStatusEnum::Pending)
        ->and($created->code)->toBe(date('dmy').$created->id)
        ->and($created->logo)->not->toBeNull()
        ->and($created->categories()->pluck('categories.id')->all())->toContain($deps['category']->id)
        ->and($created->skills()->pluck('skills.id')->all())->toContain($deps['skill']->id);
});

it('updates a provider without requiring a new password', function (): void {
    Storage::fake('public');
    Storage::fake('local');

    $admin = createProviderManagementAdmin(['edit providers']);
    $deps = createProviderManagementFormDeps();
    $provider = createWalletProvider([
        'email' => 'target-provider@example.com',
        'provider_type_id' => $deps['providerType']->id,
        'region_id' => $deps['region']->id,
        'city_id' => $deps['city']->id,
        'password' => Hash::make('original-password'),
        'about' => 'Old about',
        'address' => 'Old address',
        'iban' => 'SA0380000000608010167519',
        'phone' => Phone::make('512345670')->toString(),
    ]);
    $provider->categories()->sync([$deps['category']->id]);
    $originalPassword = $provider->password;

    $this->actingAs($admin, 'admin')
        ->put(action([ProviderController::class, 'update'], $provider), [
            'name' => 'Renamed Provider',
            'provider_type_id' => $deps['providerType']->id,
            'region_id' => $deps['region']->id,
            'city_id' => $deps['city']->id,
            'address' => 'New address',
            'phone' => '512345679',
            'email' => 'target-provider@example.com',
            'iban' => 'SA0380000000608010167519',
            'about' => 'Updated about',
            'categories' => [
                ['id' => $deps['category']->id, 'skills' => [$deps['skill']->id]],
            ],
        ])
        ->assertRedirect(route('dashboard.providers.index'));

    $provider->refresh();

    expect($provider->name)->toBe('Renamed Provider')
        ->and($provider->password)->toBe($originalPassword)
        ->and($provider->phone)->toBe(Phone::make('512345679')->toString())
        ->and($provider->skills()->pluck('skills.id')->all())->toContain($deps['skill']->id);
});

it('deletes a provider', function (): void {
    $admin = createProviderManagementAdmin(['delete providers']);
    $provider = createWalletProvider();

    $this->actingAs($admin, 'admin')
        ->delete(action([ProviderController::class, 'destroy'], $provider))
        ->assertRedirect(route('dashboard.providers.index'));

    expect(Provider::query()->whereKey($provider->getKey())->exists())->toBeFalse();
});

it('blocks a provider when the status becomes blocked', function (): void {
    $admin = createProviderManagementAdmin(['process providers']);
    $provider = createWalletProvider();

    $this->actingAs($admin, 'admin')
        ->put(route('dashboard.providers.update-status', $provider), [
            'status' => ProviderStatusEnum::Blocked->value,
            'block_days' => 5,
            'block_reason' => 'policy',
        ])
        ->assertRedirect(route('dashboard.providers.index'));

    $provider->refresh();

    expect($provider->status)->toBe(ProviderStatusEnum::Blocked)
        ->and($provider->blocked_at)->not->toBeNull()
        ->and($provider->blocked_until)->not->toBeNull()
        ->and($provider->blockHistories()->where('reason', 'policy')->exists())->toBeTrue();
});

it('loads a provider for the user API get endpoint through the service', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['user-api'], 'user-api');
    $provider = createWalletProvider(['name' => 'API Provider']);

    $this->getJson(action([ApiUserProviderController::class, 'get'], [
        'provider_id' => $provider->id,
    ]))
        ->assertSuccessful()
        ->assertJsonPath('data.id', $provider->id)
        ->assertJsonPath('data.name', 'API Provider');
});

it('returns not found for unknown provider_id on the user API get endpoint', function (): void {
    $user = User::factory()->create();
    Sanctum::actingAs($user, ['user-api'], 'user-api');

    $this->getJson(action([ApiUserProviderController::class, 'get'], [
        'provider_id' => 999999,
    ]))
        ->assertStatus(400)
        ->assertJsonPath('success', false);
});

it('looks up a catalog provider by phone through ProviderManagementService', function (): void {
    $deps = createProviderManagementFormDeps();
    $phone = Phone::make('0501234567');
    $provider = createWalletProvider([
        'name' => 'Catalog Wired Provider',
        'phone' => $phone->toString(),
        'provider_type_id' => $deps['providerType']->id,
    ]);

    $this->getJson(action([PlatformController::class, 'providers'], [
        'phone' => '0501234567',
    ]))
        ->assertSuccessful()
        ->assertJsonPath('data.id', $provider->id)
        ->assertJsonPath('data.name', 'Catalog Wired Provider');
});
