<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Cms\Actions\Page\StorePageAction;
use Modules\Cms\DTOs\StorePageDTO;
use Modules\Cms\Http\Controllers\Dashboard\PageController;
use Modules\Cms\Support\PageHtmlBrandStyler;
use Modules\Cms\Support\PageHtmlSanitizer;

test('uploading an image via the Pages content editor stores it and returns a usable URL', function () {
    withoutCmsDashboardLocaleMiddleware();
    Storage::fake('public');

    $admin = createCmsDashboardAdmin(['create pages', 'edit pages']);

    $file = UploadedFile::fake()->image('hero.png', 120, 80);

    $response = $this->actingAs($admin, 'admin')
        ->post(action([PageController::class, 'uploadContentImage']), [
            'image' => $file,
        ]);

    $response->assertSuccessful()
        ->assertJsonPath('success', true);

    $url = $response->json('data.url');
    $path = $response->json('data.path');

    expect($url)->toBeString()->not->toBeEmpty()
        ->and($url)->toContain('/storage/')
        ->and($path)->toBeString()->toStartWith('pages/content/');

    Storage::disk('public')->assertExists($path);
});

test('uploaded image file type/size is validated (mirror existing upload validation limits used elsewhere in the app, e.g. Bank logo)', function () {
    withoutCmsDashboardLocaleMiddleware();
    Storage::fake('public');

    $admin = createCmsDashboardAdmin(['create pages', 'edit pages']);

    $oversized = UploadedFile::fake()->image('big.jpg')->size(513);

    $this->actingAs($admin, 'admin')
        ->withHeaders(['Accept' => 'application/json'])
        ->post(action([PageController::class, 'uploadContentImage']), [
            'image' => $oversized,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);

    $notImage = UploadedFile::fake()->create('notes.txt', 10, 'text/plain');

    $this->actingAs($admin, 'admin')
        ->withHeaders(['Accept' => 'application/json'])
        ->post(action([PageController::class, 'uploadContentImage']), [
            'image' => $notImage,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['image']);

    expect(Storage::disk('public')->allFiles('pages/content'))->toBeEmpty();
});

test('the existing Insert Logo shortcut still works unchanged — regression', function () {
    expect(PageHtmlBrandStyler::LOGO_SRC)->toBe('/media/logos/default.svg');

    $logoHtml = '<p style="text-align:center;"><img src="/media/logos/default.svg" alt="Ijaz" width="120" height="120"></p>';
    $prepared = PageHtmlSanitizer::prepare($logoHtml);

    expect($prepared)->toContain('/media/logos/default.svg')
        ->and($prepared)->toContain('alt="Ijaz"')
        ->and($prepared)->toContain('width="120"')
        ->and($prepared)->toContain('height="120"');
});

test('img tags with an uploaded image URL survive HTML sanitization on save, same as the logo img already does', function () {
    $uploadedSrc = '/storage/pages/content/example-hero.webp';
    $html = '<h2>About</h2><p><img src="'.$uploadedSrc.'" alt="Hero" width="400" height="200"></p>';

    $page = app(StorePageAction::class)->handle(new StorePageDTO(
        slug: 'uploaded-img-probe',
        translations: [
            'en' => ['title' => 'Uploaded Img', 'content' => $html],
            'ar' => ['title' => 'صورة', 'content' => $html],
            'ur' => ['title' => 'تصویر', 'content' => $html],
            'hi' => ['title' => 'छवि', 'content' => $html],
        ],
    ));

    $content = (string) $page->refresh()->load('translations')->translate('en')?->content;

    expect($content)->toContain($uploadedSrc)
        ->and($content)->toContain('<img')
        ->and($content)->toContain('alt="Hero"')
        ->and($content)->toContain('style="color: #00686D; font-weight: 700;"');
});
