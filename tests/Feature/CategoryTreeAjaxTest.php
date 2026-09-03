<?php

use App\Http\Controllers\General\AjaxController;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\ProviderType;

beforeEach(function () {
    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
});

test('categories tree endpoint returns nested roots with children', function () {
    $root = Category::factory()->create();
    $sub = Category::factory()->create(['parent_id' => $root->id]);
    $leaf = Category::factory()->create(['parent_id' => $sub->id]);
    $unrelatedRoot = Category::factory()->create();

    $response = $this->getJson(action([AjaxController::class, 'categoriesTree']));

    $response->assertSuccessful()
        ->assertJsonPath('data.0.id', $root->id);

    $data = $response->json('data');
    $rootPayload = collect($data)->firstWhere('id', $root->id);

    expect($rootPayload)->not->toBeNull()
        ->and($rootPayload['has_children'])->toBeTrue()
        ->and($rootPayload['children'])->toHaveCount(1)
        ->and($rootPayload['children'][0]['id'])->toBe($sub->id)
        ->and($rootPayload['children'][0]['children'][0]['id'])->toBe($leaf->id)
        ->and(collect($data)->pluck('id')->all())->toContain($unrelatedRoot->id);
});

test('categories tree endpoint scopes roots by provider_type_id including full subtree', function () {
    $type = ProviderType::query()->create([
        'image' => 'media/test-type.png',
        'files' => [],
    ]);
    $type->translations()->create([
        'locale' => 'en',
        'name' => 'Scoped Type',
    ]);

    $scopedRoot = Category::factory()->create();
    $sub = Category::factory()->create(['parent_id' => $scopedRoot->id]);
    $leaf = Category::factory()->create(['parent_id' => $sub->id]);
    $otherRoot = Category::factory()->create();

    $type->categories()->attach($scopedRoot->id);

    $response = $this->getJson(action([AjaxController::class, 'categoriesTree'], [
        'provider_type_id' => $type->id,
    ]));

    $response->assertSuccessful();

    $ids = collect($response->json('data'))->pluck('id')->all();

    expect($ids)->toBe([$scopedRoot->id])
        ->and($response->json('data.0.children.0.id'))->toBe($sub->id)
        ->and($response->json('data.0.children.0.children.0.id'))->toBe($leaf->id)
        ->and($ids)->not->toContain($otherRoot->id);
});
