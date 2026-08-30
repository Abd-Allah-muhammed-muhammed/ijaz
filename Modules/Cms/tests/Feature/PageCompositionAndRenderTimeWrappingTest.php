<?php

use Database\Seeders\PagesSeeder;
use Illuminate\Support\Facades\URL;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Cms\Actions\Page\RenderCmsPageContentAction;
use Modules\Cms\Actions\Page\UpdatePageAction;
use Modules\Cms\DTOs\UpdatePageDTO;
use Modules\Cms\Models\Page;
use Modules\Cms\Services\PageService;

test('rendering a single page wraps its content in the badge/card shell showing that page\'s own title', function () {
    $page = Page::query()->create([
        'slug' => 'render-wrap-probe',
        'translations' => [
            'en' => [
                'title' => 'Wrap Probe Title',
                'content' => '<h2>Section</h2><p>Body copy.</p>',
            ],
            'ar' => ['title' => 'عنوان', 'content' => '<p>ar</p>'],
            'ur' => ['title' => 'عنوان', 'content' => '<p>ur</p>'],
            'hi' => ['title' => 'शीर्षक', 'content' => '<p>hi</p>'],
        ],
    ]);

    $html = app(RenderCmsPageContentAction::class)->handle($page);

    expect($html)->toContain('data-testid="cms-page-card"')
        ->and($html)->toContain('data-testid="cms-page-title-badge"')
        ->and($html)->toContain('Wrap Probe Title')
        ->and($html)->toContain('background-color:#00686D')
        ->and($html)->toContain('<h2>Section</h2>')
        ->and($html)->toContain('<p>Body copy.</p>');
});

test('a relative image src in content is rewritten to an absolute URL at render time, using the current app URL', function () {
    config(['app.url' => 'https://render-time.example']);
    URL::forceRootUrl('https://render-time.example');

    $page = Page::query()->create([
        'slug' => 'absolute-img-probe',
        'translations' => [
            'en' => [
                'title' => 'Logo Page',
                'content' => '<p><img src="/media/logos/default.svg" alt="Ijaz"></p>',
            ],
            'ar' => ['title' => 'شعار', 'content' => '<p>ar</p>'],
            'ur' => ['title' => 'لوگو', 'content' => '<p>ur</p>'],
            'hi' => ['title' => 'लोगो', 'content' => '<p>hi</p>'],
        ],
    ]);

    $html = app(RenderCmsPageContentAction::class)->handle($page);

    expect($html)->toContain('src="https://render-time.example/media/logos/default.svg"')
        ->and($html)->not->toContain('src="/media/logos/default.svg"');
});

test('rendering the same page content on two different app URLs (simulate via config) produces two different absolute image URLs — proving this happens at request time, not save time', function () {
    $page = Page::query()->create([
        'slug' => 'env-url-probe',
        'translations' => [
            'en' => [
                'title' => 'Env URL',
                'content' => '<p><img src="/media/logos/default.svg" alt="Ijaz"></p>',
            ],
            'ar' => ['title' => 'أ', 'content' => '<p>ar</p>'],
            'ur' => ['title' => 'ا', 'content' => '<p>ur</p>'],
            'hi' => ['title' => 'ह', 'content' => '<p>hi</p>'],
        ],
    ]);

    $stored = (string) $page->refresh()->translate('en')?->content;
    expect($stored)->toContain('src="/media/logos/default.svg"');

    config(['app.url' => 'https://alpha.test']);
    URL::forceRootUrl('https://alpha.test');
    $alpha = app(RenderCmsPageContentAction::class)->handle($page->fresh());

    config(['app.url' => 'https://beta.test']);
    URL::forceRootUrl('https://beta.test');
    $beta = app(RenderCmsPageContentAction::class)->handle($page->fresh());

    expect($alpha)->toContain('https://alpha.test/media/logos/default.svg')
        ->and($beta)->toContain('https://beta.test/media/logos/default.svg')
        ->and($alpha)->not->toBe($beta)
        ->and((string) $page->fresh()->translate('en')?->content)->toContain('src="/media/logos/default.svg"');
});

test('a page with composed_of_slugs set ignores its own content field and instead renders each referenced page\'s content, each independently wrapped in its own badge/card, concatenated in the configured order', function () {
    $first = Page::query()->create([
        'slug' => 'compose-first',
        'translations' => [
            'en' => ['title' => 'First Card', 'content' => '<p>FIRST_BODY</p>'],
            'ar' => ['title' => 'أول', 'content' => '<p>ar</p>'],
            'ur' => ['title' => 'پہلا', 'content' => '<p>ur</p>'],
            'hi' => ['title' => 'पहला', 'content' => '<p>hi</p>'],
        ],
    ]);
    $second = Page::query()->create([
        'slug' => 'compose-second',
        'translations' => [
            'en' => ['title' => 'Second Card', 'content' => '<p>SECOND_BODY</p>'],
            'ar' => ['title' => 'ثاني', 'content' => '<p>ar</p>'],
            'ur' => ['title' => 'دوسرا', 'content' => '<p>ur</p>'],
            'hi' => ['title' => 'दूसरा', 'content' => '<p>hi</p>'],
        ],
    ]);

    $hub = Page::query()->create([
        'slug' => 'compose-hub',
        'composed_of_slugs' => ['compose-second', 'compose-first'],
        'translations' => [
            'en' => ['title' => 'Hub Title', 'content' => '<p>OWN_CONTENT_MUST_BE_IGNORED</p>'],
            'ar' => ['title' => 'مجمع', 'content' => '<p>ar</p>'],
            'ur' => ['title' => 'مرکز', 'content' => '<p>ur</p>'],
            'hi' => ['title' => 'हब', 'content' => '<p>hi</p>'],
        ],
    ]);

    $html = app(RenderCmsPageContentAction::class)->handle($hub->fresh());

    expect($html)->not->toContain('OWN_CONTENT_MUST_BE_IGNORED')
        ->and($html)->toContain('Second Card')
        ->and($html)->toContain('FIRST_BODY')
        ->and($html)->toContain('SECOND_BODY')
        ->and(substr_count($html, 'data-testid="cms-page-card"'))->toBe(2);

    $secondPos = strpos($html, 'SECOND_BODY');
    $firstPos = strpos($html, 'FIRST_BODY');
    expect($secondPos)->toBeLessThan($firstPos);

    expect($first->slug)->toBe('compose-first')
        ->and($second->slug)->toBe('compose-second');
});

test('GET /api/v1/catalog/pages/terms still returns a flat content string field (unchanged shape) — now containing 2 wrapped cards concatenated, but the response shape itself has zero breaking change', function () {
    app(PagesSeeder::class)->run();

    $response = $this->getJson('/api/v1/catalog/pages/terms');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toHaveKeys(['id', 'slug', 'title', 'content'])
        ->and($json['data']['slug'])->toBe('terms')
        ->and($json['data']['content'])->toBeString()
        ->and(is_array($json['data']['content']))->toBeFalse()
        ->and(substr_count((string) $json['data']['content'], 'data-testid="cms-page-card"'))->toBe(2)
        ->and($json['data']['content'])->toContain('authorization must be made exclusively')
        ->and($json['data']['content'])->toContain('The client logs into their account in the app');
});

test('editing a referenced page (e.g. how-to-use-agency) independently is reflected next time a composed page (terms) is rendered — always live, never a stale copy', function () {
    app(PagesSeeder::class)->run();

    $leaf = Page::query()->where('slug', 'how-to-use-agency')->firstOrFail();
    $marker = 'LIVE_EDIT_MARKER_'.uniqid();

    app(UpdatePageAction::class)->handle($leaf, new UpdatePageDTO(
        slug: 'how-to-use-agency',
        translations: [
            'en' => [
                'title' => (string) $leaf->translate('en')?->title,
                'content' => '<p>'.$marker.'</p><p>The client logs into their account in the app.</p>',
            ],
            'ar' => [
                'title' => (string) $leaf->translate('ar')?->title,
                'content' => (string) $leaf->translate('ar')?->content,
            ],
            'ur' => [
                'title' => (string) $leaf->translate('ur')?->title,
                'content' => (string) $leaf->translate('ur')?->content,
            ],
            'hi' => [
                'title' => (string) $leaf->translate('hi')?->title,
                'content' => (string) $leaf->translate('hi')?->content,
            ],
        ],
        composedOfSlugs: null,
    ));

    $termsHtml = (string) $this->getJson('/api/v1/catalog/pages/terms')->json('data.content');

    expect($termsHtml)->toContain($marker);
});

test('the same Blade partial/rendering path is used for both the website route and the API endpoint — assert byte-identical output for the same page between the two surfaces', function () {
    app(PagesSeeder::class)->run();

    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    $this->withoutVite();

    $apiContent = (string) $this->getJson('/api/v1/catalog/pages/how-to-use-agency')->json('data.content');

    $web = $this->get('/how-to-use-agency');
    $web->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Frontend/CmsPage')
            ->where('page.slug', 'how-to-use-agency')
            ->where('page.content', $apiContent)
        );

    $serviceContent = app(PageService::class)->catalogPayloadBySlug('how-to-use-agency')['content'];
    expect($serviceContent)->toBe($apiContent);
});
