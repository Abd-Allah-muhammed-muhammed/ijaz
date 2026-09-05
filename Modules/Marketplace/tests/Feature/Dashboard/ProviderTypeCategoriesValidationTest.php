<?php

use App\Enums\ProviderTypeFilesEnum;
use Modules\Marketplace\Http\Controllers\Dashboard\ProviderTypeController;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\ProviderType;

test('updating a provider type with nested categories returns validation error, not 500', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit providerTypes']);

    $category = Category::factory()->create();

    $providerType = ProviderType::query()->create([
        'image' => 'provider-types/validation-test.png',
        'files' => [],
        'translations' => [
            'en' => ['name' => 'Nested Categories Type EN', 'description' => null],
            'ar' => ['name' => 'Nested Categories Type AR', 'description' => null],
            'ur' => ['name' => 'Nested Categories Type UR', 'description' => null],
            'hi' => ['name' => 'Nested Categories Type HI', 'description' => null],
        ],
    ]);

    $files = collect(ProviderTypeFilesEnum::cases())
        ->mapWithKeys(fn (ProviderTypeFilesEnum $file): array => [$file->value => false])
        ->all();

    $this->actingAs($admin, 'admin')
        ->from(action([ProviderTypeController::class, 'edit'], $providerType))
        ->put(action([ProviderTypeController::class, 'update'], $providerType), [
            'translations' => [
                'en' => ['name' => 'Nested Categories Type EN', 'description' => null],
                'ar' => ['name' => 'Nested Categories Type AR', 'description' => null],
                'ur' => ['name' => 'Nested Categories Type UR', 'description' => null],
                'hi' => ['name' => 'Nested Categories Type HI', 'description' => null],
            ],
            'files' => $files,
            'categories' => [[
                'id' => $category->id,
                'skills' => [],
            ]],
        ])
        ->assertRedirect(action([ProviderTypeController::class, 'edit'], $providerType))
        ->assertSessionHasErrors('categories.0')
        ->assertSessionMissing('error');
});

test('updating a provider type with integer category ids succeeds', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit providerTypes']);

    $category = Category::factory()->create();

    $providerType = ProviderType::query()->create([
        'image' => 'provider-types/update-ok-test.png',
        'files' => [],
        'translations' => [
            'en' => ['name' => 'Integer Categories Type EN', 'description' => null],
            'ar' => ['name' => 'Integer Categories Type AR', 'description' => null],
            'ur' => ['name' => 'Integer Categories Type UR', 'description' => null],
            'hi' => ['name' => 'Integer Categories Type HI', 'description' => null],
        ],
    ]);

    $files = collect(ProviderTypeFilesEnum::cases())
        ->mapWithKeys(fn (ProviderTypeFilesEnum $file): array => [$file->value => false])
        ->all();

    $this->actingAs($admin, 'admin')
        ->from(action([ProviderTypeController::class, 'edit'], $providerType))
        ->put(action([ProviderTypeController::class, 'update'], $providerType), [
            'translations' => [
                'en' => ['name' => 'Integer Categories Type EN Updated', 'description' => null],
                'ar' => ['name' => 'Integer Categories Type AR Updated', 'description' => null],
                'ur' => ['name' => 'Integer Categories Type UR Updated', 'description' => null],
                'hi' => ['name' => 'Integer Categories Type HI Updated', 'description' => null],
            ],
            'files' => $files,
            'categories' => [(int) $category->id],
        ])
        ->assertRedirect(route('dashboard.provider-types.index'))
        ->assertSessionHas('success')
        ->assertSessionMissing('error');

    expect($providerType->fresh()->categories()->pluck('categories.id')->all())
        ->toContain($category->id);
});
