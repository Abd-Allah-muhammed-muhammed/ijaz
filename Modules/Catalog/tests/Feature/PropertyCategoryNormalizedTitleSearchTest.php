<?php

use App\Support\Normalize;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Http\Controllers\Api\V1\PropertyCategoryController;
use Modules\Catalog\Models\PropertyCategory;
use Modules\Catalog\Models\PropertyCategoryTranslation;
use Modules\Catalog\QueryFilters\PropertyCategory\PropertyCategoryFilters;
use Modules\Catalog\Repositories\PropertyCategoryRepository;
use Tests\TestCase;

test('property category search finds results by normalized title', function (): void {
    /** @var TestCase $this */
    app()->setLocale('en');

    $category = PropertyCategory::query()->create([
        'parent_id' => null,
        'is_active' => true,
    ]);
    $category->translations()->create([
        'locale' => 'en',
        'title' => 'UniqueResidentialSearchTarget',
    ]);

    PropertyCategory::query()->create([
        'parent_id' => null,
        'is_active' => true,
    ])->translations()->create([
        'locale' => 'en',
        'title' => 'UnrelatedCommercialNoise',
    ]);

    $translation = PropertyCategoryTranslation::query()
        ->where('property_category_id', $category->id)
        ->where('locale', 'en')
        ->first();

    expect($translation)->not->toBeNull()
        ->and($translation->normalized_title)->toBe(
            Normalize::make('UniqueResidentialSearchTarget', 'en')->toString()
        );

    $filters = new PropertyCategoryFilters(new Request(['search' => 'UniqueResidentialSearchTarget']));
    $apiIds = $filters->apply(PropertyCategory::query())->pluck('id');

    expect($apiIds)->toContain($category->id);

    $dashboard = app(PropertyCategoryRepository::class)
        ->paginateForDashboard(Request::create('/dashboard/property-categories', 'GET', [
            'search' => 'UniqueResidentialSearchTarget',
        ]));

    expect($dashboard->pluck('id'))->toContain($category->id);

    $response = $this->getJson(action([PropertyCategoryController::class, 'index'], [
        'search' => 'UniqueResidentialSearchTarget',
    ]));

    $response->assertOk();
    $ids = collect($response->json('data.items'))->pluck('id');
    expect($ids)->toContain($category->id);
});

test('property category normalized title backfill repairs null rows for search', function (): void {
    /** @var TestCase $this */
    app()->setLocale('en');

    $categoryId = DB::table('property_categories')->insertGetId([
        'parent_id' => null,
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    DB::table('property_category_translations')->insert([
        'property_category_id' => $categoryId,
        'locale' => 'en',
        'title' => 'BackfillSearchableCategory',
        'normalized_title' => null,
    ]);

    expect(
        DB::table('property_category_translations')
            ->where('property_category_id', $categoryId)
            ->value('normalized_title')
    )->toBeNull();

    PropertyCategoryTranslation::query()
        ->whereNull('normalized_title')
        ->each(function (PropertyCategoryTranslation $translation): void {
            $translation->normalized_title = Normalize::make($translation->title, $translation->locale)->toString();
            $translation->saveQuietly();
        });

    $filters = new PropertyCategoryFilters(new Request(['search' => 'BackfillSearchableCategory']));
    $ids = $filters->apply(PropertyCategory::query())->pluck('id');

    expect($ids)->toContain($categoryId);
});
