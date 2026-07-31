<?php

use App\Enums\Providers\ProviderStatusEnum;
use App\Models\Provider;
use Illuminate\Support\Facades\DB;
use Modules\Geo\Models\City;
use Modules\Geo\Models\Region;
use Modules\Marketplace\Http\Controllers\Dashboard\ProviderTypeController;
use Modules\Marketplace\Http\Controllers\Dashboard\SkillController;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\ProviderType;
use Modules\Marketplace\Models\Skill;

test('skills index displays the assigned category title, not empty', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['show skills']);

    $category = Category::factory()->create();
    $category->translations()->where('locale', 'en')->update([
        'title' => 'Cleaning Category',
    ]);

    $skill = Skill::query()->create([
        'category_id' => $category->id,
        'translations' => [
            'en' => ['title' => 'Window Washing EN'],
            'ar' => ['title' => 'Window Washing AR'],
            'ur' => ['title' => 'Window Washing UR'],
            'hi' => ['title' => 'Window Washing HI'],
        ],
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([SkillController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Skills/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $skill->id)
            ->where('rows.data.0.category.title', 'Cleaning Category')
        );
});

test('provider types index displays the correct providers count, not always zero', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['show providerTypes']);

    $providerType = ProviderType::query()->create([
        'image' => 'provider-types/count-test.png',
        'files' => [],
        'translations' => [
            'en' => ['name' => 'Count Type EN', 'description' => 'Desc EN'],
            'ar' => ['name' => 'Count Type AR', 'description' => 'Desc AR'],
            'ur' => ['name' => 'Count Type UR', 'description' => 'Desc UR'],
            'hi' => ['name' => 'Count Type HI', 'description' => 'Desc HI'],
        ],
    ]);

    $region = Region::factory()->create();
    $city = City::factory()->create(['region_id' => $region->id]);

    Provider::query()->create([
        'name' => 'Counted Provider Co',
        'iban' => fake()->unique()->iban('SA'),
        'logo' => 'media/test-logo.png',
        'provider_type_id' => $providerType->id,
        'region_id' => $region->id,
        'city_id' => $city->id,
        'password' => 'password',
        'status' => ProviderStatusEnum::Approved,
        'language' => 'en',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([ProviderTypeController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/ProviderTypes/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $providerType->id)
            ->where('rows.data.0.providers_count', 1)
        );
});

test('deleting a provider type with assigned categories succeeds (detaches pivot first)', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['delete providerTypes']);

    $category = Category::factory()->create();

    $providerType = ProviderType::query()->create([
        'image' => 'provider-types/delete-test.png',
        'files' => [],
        'translations' => [
            'en' => ['name' => 'Delete Type EN', 'description' => 'Desc EN'],
            'ar' => ['name' => 'Delete Type AR', 'description' => 'Desc AR'],
            'ur' => ['name' => 'Delete Type UR', 'description' => 'Desc UR'],
            'hi' => ['name' => 'Delete Type HI', 'description' => 'Desc HI'],
        ],
    ]);
    $providerType->categories()->sync([$category->id]);

    expect($providerType->categories()->count())->toBe(1);

    $this->actingAs($admin, 'admin')
        ->from(action([ProviderTypeController::class, 'index']))
        ->delete(action([ProviderTypeController::class, 'destroy'], $providerType))
        ->assertRedirect(route('dashboard.provider-types.index'))
        ->assertSessionHas('success')
        ->assertSessionMissing('error');

    expect(ProviderType::query()->whereKey($providerType->id)->exists())->toBeFalse()
        ->and(DB::table('provider_type_categories')->where('provider_type_id', $providerType->id)->exists())->toBeFalse();
});
