<?php

use Database\Seeders\PagesSeeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
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

test('the 5 original website routes return 200 and render their original static components again, not CmsPage', function () {
    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    $this->withoutVite();

    $routes = [
        '/privacy-and-policies' => 'Frontend/PrivacyAndPolicies',
        '/privacy-policy' => 'Frontend/PrivacyPolicy',
        '/how-to-use-agency' => 'Frontend/HowToUseAgency',
        '/real-estate-marketplace-terms-of-use' => 'Frontend/RealEstateMarketplaceTermsOfUse',
        '/service-provider-authorization-terms-and-conditions' => 'Frontend/ServiceProviderAuthorizationTermsAndConditions',
    ];

    foreach ($routes as $path => $component) {
        $this->get($path)
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component($component)
                ->missing('page')
            );
    }
});

test('composed_of_slugs column no longer exists on pages table', function () {
    expect(Schema::hasColumn('pages', 'composed_of_slugs'))->toBeFalse();
});

test('the Pages admin form no longer has a "Composed of" field', function () {
    $admin = createCmsDashboardAdmin([
        'show pages',
        'create pages',
        'edit pages',
    ]);

    withoutCmsDashboardLocaleMiddleware();

    $this->actingAs($admin, 'admin')
        ->get(route('dashboard.pages.create'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Pages/Create')
            ->missing('pageOptions')
        );

    app(PagesSeeder::class)->run();
    $terms = Page::query()->where('slug', 'terms')->firstOrFail();

    $this->actingAs($admin, 'admin')
        ->get(route('dashboard.pages.edit', $terms))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Pages/Edit')
            ->missing('pageOptions')
            ->has('row')
        );
});

test('GET /api/v1/catalog/pages/terms returns a single merged content string containing both the Service Provider Authorization and How to Use Agency wording', function () {
    app(PagesSeeder::class)->run();

    $response = $this->getJson('/api/v1/catalog/pages/terms');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toHaveKeys(['id', 'slug', 'title', 'content'])
        ->and($json['data']['slug'])->toBe('terms')
        ->and($json['data']['content'])->toBeString()
        ->and(is_array($json['data']['content']))->toBeFalse()
        ->and($json['data']['content'])->toContain('authorization must be made exclusively')
        ->and($json['data']['content'])->toContain('The client logs into their account in the app')
        ->and(substr_count((string) $json['data']['content'], 'data-testid="cms-page-card"'))->toBe(1);
});

test('the terms page content is directly editable via the normal Pages admin CRUD, no special composition behavior', function () {
    app(PagesSeeder::class)->run();

    $terms = Page::query()->where('slug', 'terms')->firstOrFail();
    $marker = 'DIRECT_TERMS_EDIT_'.uniqid();

    app(UpdatePageAction::class)->handle($terms, new UpdatePageDTO(
        slug: 'terms',
        translations: [
            'en' => [
                'title' => (string) $terms->translate('en')?->title,
                'content' => '<p>'.$marker.'</p>',
            ],
            'ar' => [
                'title' => (string) $terms->translate('ar')?->title,
                'content' => (string) $terms->translate('ar')?->content,
            ],
            'ur' => [
                'title' => (string) $terms->translate('ur')?->title,
                'content' => (string) $terms->translate('ur')?->content,
            ],
            'hi' => [
                'title' => (string) $terms->translate('hi')?->title,
                'content' => (string) $terms->translate('hi')?->content,
            ],
        ],
    ));

    $termsHtml = (string) $this->getJson('/api/v1/catalog/pages/terms')->json('data.content');

    expect($termsHtml)->toContain($marker)
        ->and($termsHtml)->not->toContain('The client logs into their account in the app');
});

test('the 4 individual pages (privacy, service-provider-authorization, how-to-use-agency, real-estate-marketplace-terms) still exist as independently editable CMS pages, unaffected', function () {
    app(PagesSeeder::class)->run();

    $fixtureEn = json_decode(
        File::get(database_path('seeders/data/cms-static-pages/en.json')),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $slugs = [
        'privacy',
        'service-provider-authorization',
        'how-to-use-agency',
        'real-estate-marketplace-terms',
    ];

    foreach ($slugs as $slug) {
        $page = Page::query()->where('slug', $slug)->first();
        expect($page)->not->toBeNull();

        $enContent = (string) $page->translate('en')?->content;
        expect($enContent)->toBe((string) $fixtureEn[$slug]['content']);

        $leafMarker = strtoupper($slug).'_LEAF_EDIT_'.uniqid();
        app(UpdatePageAction::class)->handle($page, new UpdatePageDTO(
            slug: $slug,
            translations: [
                'en' => [
                    'title' => (string) $page->translate('en')?->title,
                    'content' => '<p>'.$leafMarker.'</p>',
                ],
                'ar' => [
                    'title' => (string) $page->translate('ar')?->title,
                    'content' => (string) $page->translate('ar')?->content,
                ],
                'ur' => [
                    'title' => (string) $page->translate('ur')?->title,
                    'content' => (string) $page->translate('ur')?->content,
                ],
                'hi' => [
                    'title' => (string) $page->translate('hi')?->title,
                    'content' => (string) $page->translate('hi')?->content,
                ],
            ],
        ));

        $leafApi = (string) $this->getJson('/api/v1/catalog/pages/'.$slug)->json('data.content');
        expect($leafApi)->toContain($leafMarker);

        $termsApi = (string) $this->getJson('/api/v1/catalog/pages/terms')->json('data.content');
        expect($termsApi)->not->toContain($leafMarker);
    }
});

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

test('the same Blade partial/rendering path is used for both the website route and the API endpoint — assert byte-identical output for the same page between the two surfaces', function () {
    app(PagesSeeder::class)->run();

    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    $this->withoutVite();

    $apiContent = (string) $this->getJson('/api/v1/catalog/pages/terms')->json('data.content');

    $web = $this->get('/pages/terms');
    $web->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Frontend/CmsPage')
            ->where('page.slug', 'terms')
            ->where('page.content', $apiContent)
        );

    $serviceContent = app(PageService::class)->catalogPayloadBySlug('terms')['content'];
    expect($serviceContent)->toBe($apiContent);
});
