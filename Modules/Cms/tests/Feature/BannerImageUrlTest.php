<?php

use App\Support\LookupCache;
use Illuminate\Support\Facades\Storage;
use Modules\Cms\Models\Banner;
use Modules\Cms\Services\BannerService;

test('a Banner with a missing stored file returns a null image_url, not the avatar placeholder', function (): void {
    Storage::fake('public');

    $banner = Banner::query()->create([
        'link' => 'https://example.com/promo',
        'image' => 'banners/missing.png',
    ]);

    expect($banner->image_url)->toBeNull()
        ->and($banner->image_url)->not->toBe(asset('media/avatars/blank.png'));
});

test('BannerService::all() (or equivalent) excludes banners with a missing/null image_url from its results', function (): void {
    Storage::fake('public');
    LookupCache::forget('banners:all');

    Storage::disk('public')->put('banners/real.png', 'ok');

    $visible = Banner::query()->create([
        'link' => 'https://example.com/visible',
        'image' => 'banners/real.png',
    ]);
    Banner::query()->create([
        'link' => 'https://example.com/broken',
        'image' => 'banners/missing.png',
    ]);

    $results = app(BannerService::class)->all();

    expect($results)->toHaveCount(1)
        ->and($results->first()->id)->toBe($visible->id)
        ->and($results->first()->image_url)->not->toBeNull();
});
