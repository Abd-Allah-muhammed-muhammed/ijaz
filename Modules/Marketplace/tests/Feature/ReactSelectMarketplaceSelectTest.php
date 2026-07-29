<?php

use App\Http\Controllers\General\ReactSelectController;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\Skill;
use Tests\TestCase;

beforeEach(function (): void {
    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
});

/**
 * Regression lock for Marketplace Step 2: Category/Skill select extraction
 * from ReactSelectController into Marketplace Actions/Services.
 */
it('returns root categories as paginated select options', function (): void {
    /** @var TestCase $this */
    $root = Category::factory()->create();
    $root->translations()->where('locale', 'en')->update(['title' => 'Root Select']);

    $child = Category::factory()->create(['parent_id' => $root->id]);
    $child->translations()->where('locale', 'en')->update(['title' => 'Child Select']);

    $response = $this->getJson(action([ReactSelectController::class, 'categories']));

    $response->assertOk();

    $items = collect($response->json('data.items'));
    expect($items->pluck('id')->all())->toContain($root->id)
        ->and($items->pluck('id')->all())->not->toContain($child->id);
});

it('filters categories by parent_id', function (): void {
    /** @var TestCase $this */
    $parent = Category::factory()->create();
    $child = Category::factory()->create(['parent_id' => $parent->id]);
    $child->translations()->where('locale', 'en')->update(['title' => 'Child Filter']);

    $other = Category::factory()->create();

    $response = $this->getJson(action(
        [ReactSelectController::class, 'categories'],
        ['parent_id' => $parent->id],
    ));

    $response->assertOk();

    $ids = collect($response->json('data.items'))->pluck('id')->all();
    expect($ids)->toContain($child->id)
        ->and($ids)->not->toContain($other->id)
        ->and($ids)->not->toContain($parent->id);
});

it('filters skills by category_id as react-select options', function (): void {
    /** @var TestCase $this */
    $categoryA = Category::factory()->create();
    $categoryB = Category::factory()->create();

    $skillA = Skill::query()->create([
        'category_id' => $categoryA->id,
        'translations' => [
            'en' => ['title' => 'Skill A'],
            'ar' => ['title' => 'مهارة أ'],
            'ur' => ['title' => 'Skill A UR'],
            'hi' => ['title' => 'Skill A HI'],
        ],
    ]);

    Skill::query()->create([
        'category_id' => $categoryB->id,
        'translations' => [
            'en' => ['title' => 'Skill B'],
            'ar' => ['title' => 'مهارة ب'],
            'ur' => ['title' => 'Skill B UR'],
            'hi' => ['title' => 'Skill B HI'],
        ],
    ]);

    $response = $this->getJson(action(
        [ReactSelectController::class, 'skills'],
        ['category_id' => $categoryA->id],
    ));

    $response->assertOk()
        ->assertJsonStructure([
            'success',
            'data' => [
                '*' => ['label', 'value'],
            ],
        ])
        ->assertJsonFragment(['label' => 'Skill A', 'value' => (string) $skillA->id])
        ->assertJsonMissing(['label' => 'Skill B']);
});
