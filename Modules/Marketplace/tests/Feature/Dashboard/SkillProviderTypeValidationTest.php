<?php

use App\Enums\ProviderTypeFilesEnum;
use Illuminate\Http\UploadedFile;
use Modules\Marketplace\Http\Controllers\Dashboard\ProviderTypeController;
use Modules\Marketplace\Http\Controllers\Dashboard\SkillController;
use Modules\Marketplace\Models\Skill;

test('creating a skill without category_id returns a validation error, not a generic failure', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['create skills']);

    $translations = [
        'en' => ['title' => 'Skill Title EN'],
        'ar' => ['title' => 'Skill Title AR'],
        'ur' => ['title' => 'Skill Title UR'],
        'hi' => ['title' => 'Skill Title HI'],
    ];

    $this->actingAs($admin, 'admin')
        ->from(action([SkillController::class, 'create']))
        ->post(action([SkillController::class, 'store']), [
            'translations' => $translations,
        ])
        ->assertRedirect(action([SkillController::class, 'create']))
        ->assertSessionHasErrors('category_id')
        ->assertSessionMissing('error');

    expect(Skill::query()->whereTranslation('title', 'Skill Title EN')->exists())->toBeFalse();
});

test('creating a provider type without categories returns a validation error', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['create providerTypes']);

    $files = collect(ProviderTypeFilesEnum::cases())
        ->mapWithKeys(fn (ProviderTypeFilesEnum $file): array => [$file->value => false])
        ->all();

    $this->actingAs($admin, 'admin')
        ->from(action([ProviderTypeController::class, 'create']))
        ->post(action([ProviderTypeController::class, 'store']), [
            'translations' => [
                'en' => ['name' => 'Type Name EN', 'description' => null],
                'ar' => ['name' => 'Type Name AR', 'description' => null],
                'ur' => ['name' => 'Type Name UR', 'description' => null],
                'hi' => ['name' => 'Type Name HI', 'description' => null],
            ],
            'files' => $files,
            'image' => UploadedFile::fake()->image('type.jpg'),
            'categories' => [],
        ])
        ->assertSessionHasErrors('categories');
});
