<?php

use Modules\Marketplace\Enums\CategoryFeesTypeEnum;
use Modules\Marketplace\Http\Controllers\Dashboard\CategoryController;
use Modules\Marketplace\Models\Category;

/**
 * @return array<string, array{title: string, description: string|null}>
 */
function marketplaceCategoryTranslations(string $prefix, ?string $description = null, ?int $titleLength = null): array
{
    $translations = [];

    foreach (['en', 'ar', 'ur', 'hi'] as $locale) {
        $suffix = strtoupper($locale);
        $title = $titleLength === null
            ? "{$prefix} {$suffix}"
            : $suffix.str_repeat('x', $titleLength - strlen($suffix));

        $translations[$locale] = [
            'title' => $title,
            'description' => $description,
        ];
    }

    return $translations;
}

function marketplaceCategoryUpdatePayload(Category $category, array $translations): array
{
    return [
        'translations' => $translations,
        'parent_id' => $category->parent_id,
        'fees_type' => $category->fees_type->value,
        'fees' => $category->fees,
    ];
}

test('updating a category with a title of 192-255 characters (passes old max:255 but exceeds the 191-char DB column) now fails VALIDATION (422 with a field error), not a swallowed QueryException/generic toast', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit categories']);

    $category = Category::factory()->create([
        'fees_type' => CategoryFeesTypeEnum::INHERITED,
        'fees' => null,
    ]);
    $translations = marketplaceCategoryTranslations('Keep');
    foreach ($translations as $locale => $attrs) {
        $category->translations()->updateOrCreate(['locale' => $locale], $attrs);
    }

    $tooLong = marketplaceCategoryTranslations('Keep', null, 200);

    $this->actingAs($admin, 'admin')
        ->from(action([CategoryController::class, 'edit'], $category))
        ->put(action([CategoryController::class, 'update'], $category), marketplaceCategoryUpdatePayload($category, $tooLong))
        ->assertRedirect(action([CategoryController::class, 'edit'], $category))
        ->assertSessionHasErrors('translations.ar.title')
        ->assertSessionMissing('error');

    expect($category->fresh()->translate('ar')->title)->toBe('Keep AR');
});

test('updating a category with a description over 191 characters now fails validation the same way — description currently has no max rule at all', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit categories']);

    $category = Category::factory()->create([
        'fees_type' => CategoryFeesTypeEnum::INHERITED,
        'fees' => null,
    ]);
    $translations = marketplaceCategoryTranslations('Keep Desc');
    foreach ($translations as $locale => $attrs) {
        $category->translations()->updateOrCreate(['locale' => $locale], $attrs);
    }

    $tooLong = marketplaceCategoryTranslations('Keep Desc', str_repeat('d', 200));

    $this->actingAs($admin, 'admin')
        ->from(action([CategoryController::class, 'edit'], $category))
        ->put(action([CategoryController::class, 'update'], $category), marketplaceCategoryUpdatePayload($category, $tooLong))
        ->assertRedirect(action([CategoryController::class, 'edit'], $category))
        ->assertSessionHasErrors('translations.ar.description')
        ->assertSessionMissing('error');

    expect($category->fresh()->translate('ar')->description)->toBeNull();
});

test('updating a category with title/description within 191 characters still succeeds normally (no regression for normal-length data)', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit categories']);

    $category = Category::factory()->create([
        'fees_type' => CategoryFeesTypeEnum::INHERITED,
        'fees' => null,
    ]);
    foreach (marketplaceCategoryTranslations('Old') as $locale => $attrs) {
        $category->translations()->updateOrCreate(['locale' => $locale], $attrs);
    }

    $withinLimit = marketplaceCategoryTranslations(
        prefix: 'Fit',
        description: str_repeat('d', 191),
        titleLength: 191,
    );

    $this->actingAs($admin, 'admin')
        ->put(action([CategoryController::class, 'update'], $category), marketplaceCategoryUpdatePayload($category, $withinLimit))
        ->assertRedirect(route('dashboard.categories.index'))
        ->assertSessionMissing('error');

    $fresh = $category->fresh();
    expect(mb_strlen($fresh->translate('ar')->title))->toBe(191)
        ->and(mb_strlen($fresh->translate('ar')->description))->toBe(191);
});

test('categories #255 and #269 (or local equivalents at their current ~170-173 char titles) can still be saved unchanged without error', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['edit categories']);

    foreach ([170, 173] as $length) {
        $category = Category::factory()->create([
            'fees_type' => CategoryFeesTypeEnum::INHERITED,
            'fees' => null,
        ]);
        $translations = marketplaceCategoryTranslations("Cat{$length}", null, $length);
        foreach ($translations as $locale => $attrs) {
            $category->translations()->updateOrCreate(['locale' => $locale], $attrs);
        }

        $this->actingAs($admin, 'admin')
            ->put(action([CategoryController::class, 'update'], $category), marketplaceCategoryUpdatePayload($category, $translations))
            ->assertRedirect(route('dashboard.categories.index'))
            ->assertSessionMissing('error');

        expect(mb_strlen($category->fresh()->translate('ar')->title))->toBe($length);
    }
});
