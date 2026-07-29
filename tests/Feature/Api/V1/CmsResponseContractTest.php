<?php

use App\Models\User;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Modules\Cms\Models\Banner;
use Modules\Cms\Models\Page;
use Modules\Cms\Models\Question;

/**
 * Response-shape contract lock for mobile CMS catalog + contact message APIs.
 * Must keep passing after Modules/Cms extraction.
 */
test('catalog banners response shape contract', function () {
    Storage::fake('public');

    Banner::query()->create([
        'link' => 'https://example.com',
        'image' => 'banners/contract.png',
    ]);

    $response = $this->getJson('/api/v1/catalog/banners');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toBeArray()->not->toBeEmpty();

    $item = $json['data'][0];
    expect($item)->toHaveKeys(['id', 'image', 'link'])
        ->and($item['link'])->toBe('https://example.com');
});

test('catalog pages list response shape contract', function () {
    Page::query()->create([
        'slug' => 'about-us',
        'translations' => [
            'en' => ['title' => 'About', 'content' => 'About content'],
            'ar' => ['title' => 'عنا', 'content' => 'محتوى'],
            'ur' => ['title' => 'About UR', 'content' => 'Content UR'],
            'hi' => ['title' => 'About HI', 'content' => 'Content HI'],
        ],
    ]);

    $response = $this->getJson('/api/v1/catalog/pages');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toBeArray()->not->toBeEmpty();

    $item = $json['data'][0];
    expect($item)->toHaveKeys(['id', 'slug', 'title'])
        ->and($item)->not->toHaveKey('content')
        ->and($item['slug'])->toBe('about-us');
});

test('catalog page show response shape contract', function () {
    Page::query()->create([
        'slug' => 'terms',
        'translations' => [
            'en' => ['title' => 'Terms', 'content' => 'Terms body'],
            'ar' => ['title' => 'الشروط', 'content' => 'نص الشروط'],
            'ur' => ['title' => 'Terms UR', 'content' => 'Body UR'],
            'hi' => ['title' => 'Terms HI', 'content' => 'Body HI'],
        ],
    ]);

    $response = $this->getJson('/api/v1/catalog/pages/terms');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toHaveKeys(['id', 'slug', 'title', 'content'])
        ->and($json['data']['slug'])->toBe('terms')
        ->and($json['data']['content'])->toBe('Terms body');
});

test('catalog questions paginated response shape contract', function () {
    Question::query()->create([
        'translations' => [
            'en' => ['title' => 'How to pay?', 'answer' => 'Use wallet'],
            'ar' => ['title' => 'كيف أدفع؟', 'answer' => 'المحفظة'],
            'ur' => ['title' => 'Pay UR', 'answer' => 'Answer UR'],
            'hi' => ['title' => 'Pay HI', 'answer' => 'Answer HI'],
        ],
    ]);

    $response = $this->getJson('/api/v1/catalog/questions?per_page=10&search=pay');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toHaveKeys([
            'items', 'total', 'count', 'per_page', 'current_page', 'last_page', 'has_more_pages',
        ])
        ->and($json['data']['items'])->toBeArray()->not->toBeEmpty();

    $item = $json['data']['items'][0];
    expect($item)->toHaveKeys(['id', 'title', 'answer']);
});

test('messages store response shape contract', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $response = $this->postJson('/api/v1/messages', [
        'name' => 'Contract User',
        'phone' => '0501234567',
        'title' => 'Hello',
        'content' => 'Contract message body',
    ]);
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['success'])->toBeTrue()
        ->and($json['data'])->toBe([]);
});
