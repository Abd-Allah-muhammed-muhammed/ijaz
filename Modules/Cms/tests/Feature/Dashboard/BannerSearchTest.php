<?php

use Modules\Cms\Http\Controllers\Dashboard\BannerController;
use Modules\Cms\Models\Banner;

test('admin can search banners by link', function (): void {
    withoutCmsDashboardLocaleMiddleware();

    $admin = createCmsDashboardAdmin(['show banners']);

    $matching = Banner::query()->create([
        'link' => 'https://example.com/unique-banner-target',
        'image' => 'banners/match.png',
    ]);

    Banner::query()->create([
        'link' => 'https://other.example/unrelated',
        'image' => 'banners/other.png',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([BannerController::class, 'index'], ['search' => 'unique-banner-target']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Banners/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $matching->id)
        );
});
