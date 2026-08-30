<?php

use App\Http\Controllers\Provider\HomeController;
use App\Support\LookupCache;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Enumerable;
use Illuminate\Support\Facades\Storage;
use Modules\Cms\Actions\Banner\DeleteBannerAction;
use Modules\Cms\Actions\Page\DeletePageAction;
use Modules\Cms\Actions\Page\UpdatePageAction;
use Modules\Cms\Actions\Question\DeleteQuestionAction;
use Modules\Cms\DTOs\UpdatePageDTO;
use Modules\Cms\Http\Controllers\Api\V1\CmsController;
use Modules\Cms\Http\Resources\Api\V1\BannerResource as ApiBannerResource;
use Modules\Cms\Http\Resources\Api\V1\PageResource as ApiPageResource;
use Modules\Cms\Http\Resources\Api\V1\QuestionCollection;
use Modules\Cms\Http\Resources\Dashboard\BannerResource as DashboardBannerResource;
use Modules\Cms\Models\Banner;
use Modules\Cms\Models\Page;
use Modules\Cms\Models\Question;
use Modules\Cms\Services\BannerService;
use Modules\Cms\Services\PageService;
use Modules\Cms\Services\QuestionService;

beforeEach(function (): void {
    withoutCmsDashboardLocaleMiddleware();
    Storage::fake('public');

    LookupCache::forget('banners:all');
    LookupCache::forgetAllLocales('pages:all');
    LookupCache::forgetAllLocales('questions:all');

    Banner::query()->create([
        'link' => 'https://example.com/promo',
        'image' => 'banners/cache-test.png',
    ]);
    Storage::disk('public')->put('banners/cache-test.png', 'fake-banner');

    $this->page = Page::query()->create([
        'slug' => 'about-us',
        'translations' => [
            'en' => ['title' => 'About', 'content' => 'About content EN'],
            'ar' => ['title' => 'عنا', 'content' => 'محتوى AR'],
            'ur' => ['title' => 'About UR', 'content' => 'Content UR'],
            'hi' => ['title' => 'About HI', 'content' => 'Content HI'],
        ],
    ]);

    LookupCache::forgetScopedAllLocales('pages:single', 'about-us');

    Question::query()->create([
        'translations' => [
            'en' => ['title' => 'How to pay?', 'answer' => 'Use wallet'],
            'ar' => ['title' => 'كيف أدفع؟', 'answer' => 'المحفظة'],
            'ur' => ['title' => 'Pay UR', 'answer' => 'Answer UR'],
            'hi' => ['title' => 'Pay HI', 'answer' => 'Answer HI'],
        ],
    ]);
});

/**
 * Recursively assert identical PHP types (gettype / get_class) and equal values.
 */
function assertCmsIdenticalTyped(mixed $expected, mixed $actual, string $path = 'root'): void
{
    expect(gettype($actual))->toBe(gettype($expected), "gettype mismatch at {$path}");

    if (is_object($expected)) {
        expect(get_class($actual))->toBe(get_class($expected), "get_class mismatch at {$path}");

        if ($expected instanceof Enumerable) {
            assertCmsIdenticalTyped($expected->all(), $actual->all(), $path);

            return;
        }

        if ($expected instanceof JsonSerializable) {
            assertCmsIdenticalTyped($expected->jsonSerialize(), $actual->jsonSerialize(), $path);

            return;
        }
    }

    if (is_array($expected)) {
        expect(array_keys($actual))->toBe(array_keys($expected), "array keys mismatch at {$path}");

        foreach ($expected as $key => $value) {
            assertCmsIdenticalTyped($value, $actual[$key], "{$path}.{$key}");
        }

        return;
    }

    expect($actual)->toBe($expected, "value mismatch at {$path}");
}

function assertCmsSha256Identical(string $cold, string $warm, string $label): void
{
    expect(hash('sha256', $warm))->toBe(hash('sha256', $cold), "SHA-256 mismatch for {$label}");
    expect($warm)->toBe($cold, "byte mismatch for {$label}");
}

test('API banners response is byte-for-byte identical cold vs warm (Eloquent Collection preserved)', function (): void {
    $service = app(BannerService::class);

    $coldCollection = $service->all();
    expect($coldCollection)->toBeInstanceOf(EloquentCollection::class)
        ->and($coldCollection->first())->toBeInstanceOf(Banner::class);

    $coldHttp = $this->getJson(action([CmsController::class, 'banners']))->assertSuccessful();
    $coldBody = $coldHttp->getContent();

    $warmCollection = $service->all();
    expect($warmCollection)->toBeInstanceOf(EloquentCollection::class)
        ->and(get_class($warmCollection))->toBe(get_class($coldCollection))
        ->and(get_class($warmCollection->first()))->toBe(get_class($coldCollection->first()));

    $request = Request::create('/api/v1/catalog/banners', 'GET');
    assertCmsSha256Identical(
        json_encode(ApiBannerResource::collection($coldCollection)->resolve($request)),
        json_encode(ApiBannerResource::collection($warmCollection)->resolve($request)),
        'api.banners.resource',
    );

    $warmHttp = $this->getJson(action([CmsController::class, 'banners']))->assertSuccessful();
    assertCmsSha256Identical($coldBody, $warmHttp->getContent(), 'api.banners.http');
    assertCmsIdenticalTyped($coldHttp->json(), $warmHttp->json(), 'api.banners.json');
});

test('Provider Home banners prop is byte-for-byte identical cold vs warm', function (): void {
    $service = app(BannerService::class);
    $request = Request::create('/provider/home', 'GET');

    $cold = DashboardBannerResource::collection($service->all())->resolve($request);
    $warm = DashboardBannerResource::collection($service->all())->resolve($request);

    assertCmsSha256Identical(json_encode($cold), json_encode($warm), 'provider.home.banners');
    assertCmsIdenticalTyped($cold, $warm, 'provider.home.banners.typed');
    expect(method_exists(HomeController::class, '__invoke'))->toBeTrue();
});

test('API pages list is byte-for-byte identical cold vs warm', function (): void {
    app()->setLocale('en');
    $service = app(PageService::class);

    $coldCollection = $service->listForCatalog();
    expect($coldCollection)->toBeInstanceOf(EloquentCollection::class)
        ->and($coldCollection->first())->toBeInstanceOf(Page::class);

    $coldHttp = $this->getJson(action([CmsController::class, 'pages']))->assertSuccessful();
    $coldBody = $coldHttp->getContent();

    $warmCollection = $service->listForCatalog();
    expect(get_class($warmCollection))->toBe(get_class($coldCollection));

    $warmHttp = $this->getJson(action([CmsController::class, 'pages']))->assertSuccessful();
    assertCmsSha256Identical($coldBody, $warmHttp->getContent(), 'api.pages.list.http');
    assertCmsIdenticalTyped($coldHttp->json(), $warmHttp->json(), 'api.pages.list.json');
    expect($warmHttp->json('data.0.title'))->toBe('About');
});

test('API pages list is locale-aware via pages:all cache', function (): void {
    LookupCache::forgetAllLocales('pages:all');

    $cold = $this->withHeaders(['Accept-Language' => 'ar'])
        ->getJson(action([CmsController::class, 'pages']))
        ->assertSuccessful();
    $warm = $this->withHeaders(['Accept-Language' => 'ar'])
        ->getJson(action([CmsController::class, 'pages']))
        ->assertSuccessful();

    assertCmsSha256Identical($cold->getContent(), $warm->getContent(), 'api.pages.list.ar');
    expect($warm->json('data.0.title'))->toBe('عنا');
});

test('API page show is byte-for-byte identical cold vs warm via pages:single', function (): void {
    app()->setLocale('en');
    $service = app(PageService::class);
    $page = Page::query()->where('slug', 'about-us')->firstOrFail();

    $coldModel = $service->showForCatalog($page);
    expect($coldModel)->toBeInstanceOf(Page::class);

    $coldHttp = $this->getJson(action([CmsController::class, 'page'], ['page' => 'about-us']))
        ->assertSuccessful();
    $coldBody = $coldHttp->getContent();

    $warmModel = $service->showForCatalog(Page::query()->where('slug', 'about-us')->firstOrFail());
    expect(get_class($warmModel))->toBe(get_class($coldModel));

    $request = Request::create('/api/v1/catalog/pages/about-us', 'GET');
    assertCmsSha256Identical(
        json_encode(ApiPageResource::make($coldModel)->resolve($request)),
        json_encode(ApiPageResource::make($warmModel)->resolve($request)),
        'api.pages.show.resource',
    );

    $warmHttp = $this->getJson(action([CmsController::class, 'page'], ['page' => 'about-us']))
        ->assertSuccessful();
    assertCmsSha256Identical($coldBody, $warmHttp->getContent(), 'api.pages.show.http');
    expect($warmHttp->json('data.content'))->toContain('About content EN')
        ->and($warmHttp->json('data.content'))->toContain('data-testid="cms-page-card"');
});

test('API questions paginated response is byte-for-byte identical cold vs warm (no search)', function (): void {
    app()->setLocale('en');
    $service = app(QuestionService::class);
    $request = Request::create('/api/v1/catalog/questions', 'GET', ['per_page' => 10]);

    $coldPaginator = $service->listForApi($request);
    expect($coldPaginator)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and($coldPaginator->first())->toBeInstanceOf(Question::class);

    $coldHttp = $this->getJson(action([CmsController::class, 'questions'], ['per_page' => 10]))
        ->assertSuccessful();
    $coldBody = $coldHttp->getContent();

    $warmPaginator = $service->listForApi($request);
    expect($warmPaginator)->toBeInstanceOf(LengthAwarePaginator::class)
        ->and(get_class($warmPaginator->first()))->toBe(get_class($coldPaginator->first()));

    assertCmsSha256Identical(
        json_encode(QuestionCollection::make($coldPaginator)->resolve($request)),
        json_encode(QuestionCollection::make($warmPaginator)->resolve($request)),
        'api.questions.resolved',
    );

    $warmHttp = $this->getJson(action([CmsController::class, 'questions'], ['per_page' => 10]))
        ->assertSuccessful();
    assertCmsSha256Identical($coldBody, $warmHttp->getContent(), 'api.questions.http');
    assertCmsIdenticalTyped($coldHttp->json(), $warmHttp->json(), 'api.questions.json');
});

test('API questions search path remains uncached and still returns matching items', function (): void {
    app()->setLocale('en');

    $response = $this->getJson(action([CmsController::class, 'questions'], [
        'per_page' => 10,
        'search' => 'pay',
    ]))->assertSuccessful();

    expect($response->json('data.items'))->not->toBeEmpty()
        ->and($response->json('data.items.0.title'))->toBe('How to pay?');
});

test('Frontend web About/home remain static and privacy-policy renders the original static component', function (): void {
    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    $this->withoutVite();

    $this->get(route('about-us'))->assertSuccessful();
    $this->get(route('home'))->assertSuccessful();
    $this->get(route('privacy-policy'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Frontend/PrivacyPolicy')
            ->missing('page')
        );
});

test('DeleteBannerAction invalidates banners:all cache', function (): void {
    $service = app(BannerService::class);
    expect($service->all())->toHaveCount(1);

    app(DeleteBannerAction::class)->handle(Banner::query()->firstOrFail());

    expect($service->all())->toHaveCount(0);
});

test('DeletePageAction and slug-changing UpdatePageAction invalidate pages caches', function (): void {
    app()->setLocale('en');
    LookupCache::forgetAllLocales('pages:all');
    LookupCache::forgetScopedAllLocales('pages:single', 'about-us');
    LookupCache::forgetScopedAllLocales('pages:single', 'about-company');

    $service = app(PageService::class);
    $page = Page::query()->where('slug', 'about-us')->firstOrFail();

    expect($service->listForCatalog())->toHaveCount(1);

    // Warm the single-page cache with a fresh model instance (not the beforeEach reference).
    expect($service->showForCatalog($page)->title)->toBe('About');

    $page = Page::query()->where('slug', 'about-us')->firstOrFail();

    $updated = app(UpdatePageAction::class)->handle($page, new UpdatePageDTO(
        slug: 'about-company',
        translations: [
            'en' => ['title' => 'About Company', 'content' => 'Updated'],
            'ar' => ['title' => 'عنا', 'content' => 'محتوى'],
            'ur' => ['title' => 'About UR', 'content' => 'Content UR'],
            'hi' => ['title' => 'About HI', 'content' => 'Content HI'],
        ],
    ));

    expect($updated->slug)->toBe('about-company')
        ->and(Page::query()->where('slug', 'about-company')->exists())->toBeTrue()
        ->and(Page::query()->whereTranslation('title', 'About Company')->exists())->toBeTrue();

    expect($service->listForCatalog()->first()->title)->toBe('About Company');

    $this->getJson(action([CmsController::class, 'page'], ['page' => 'about-us']))
        ->assertNotFound();
    $this->getJson(action([CmsController::class, 'page'], ['page' => 'about-company']))
        ->assertSuccessful()
        ->assertJsonPath('data.title', 'About Company');

    $updated = Page::query()->where('slug', 'about-company')->firstOrFail();
    app(DeletePageAction::class)->handle($updated);
    expect($service->listForCatalog())->toHaveCount(0);
});

test('DeleteQuestionAction invalidates questions:all cache', function (): void {
    app()->setLocale('en');
    $service = app(QuestionService::class);
    $request = Request::create('/api/v1/catalog/questions', 'GET', ['per_page' => 10]);

    expect($service->listForApi($request)->total())->toBe(1);

    app(DeleteQuestionAction::class)->handle(Question::query()->firstOrFail());

    expect($service->listForApi($request)->total())->toBe(0);
});
