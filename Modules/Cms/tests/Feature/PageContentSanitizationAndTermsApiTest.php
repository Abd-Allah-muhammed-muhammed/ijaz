<?php

use Database\Seeders\PagesSeeder;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Cms\Actions\Page\StorePageAction;
use Modules\Cms\Actions\Page\UpdatePageAction;
use Modules\Cms\DTOs\StorePageDTO;
use Modules\Cms\DTOs\UpdatePageDTO;
use Modules\Cms\Models\Page;
use Modules\Cms\Support\PageHtmlBrandStyler;
use Modules\Cms\Support\PageHtmlSanitizer;

test('saving Page content strips dangerous HTML (script tags, on* event attributes, iframe) while preserving safe formatting tags (h1-h6, p, ul, ol, li, strong, em, a)', function () {
    $dangerous = <<<'HTML'
<h2>Safe heading</h2>
<p onclick="alert(1)">Hello <strong>world</strong> and <em>friends</em></p>
<script>alert('xss')</script>
<iframe src="https://evil.example"></iframe>
<ul><li>One</li><li>Two</li></ul>
<ol><li>First</li></ol>
<a href="https://example.com" onclick="steal()">Link</a>
HTML;

    $page = app(StorePageAction::class)->handle(new StorePageDTO(
        slug: 'sanitize-probe',
        translations: [
            'en' => ['title' => 'Sanitize Probe', 'content' => $dangerous],
            'ar' => ['title' => 'فحص', 'content' => $dangerous],
            'ur' => ['title' => 'جانچ', 'content' => $dangerous],
            'hi' => ['title' => 'जांच', 'content' => $dangerous],
        ],
    ));

    $page->refresh()->load('translations');
    $content = (string) $page->translate('en')?->content;

    expect($content)->not->toContain('<script')
        ->and($content)->not->toContain('onclick')
        ->and($content)->not->toContain('<iframe')
        ->and($content)->toContain('<h2')
        ->and($content)->toContain('<p>')
        ->and($content)->toContain('<strong>')
        ->and($content)->toContain('<em>')
        ->and($content)->toContain('<ul>')
        ->and($content)->toContain('<ol>')
        ->and($content)->toContain('<li>')
        ->and($content)->toContain('<a href="https://example.com"');

    $updated = app(UpdatePageAction::class)->handle($page, new UpdatePageDTO(
        slug: 'sanitize-probe',
        translations: [
            'en' => ['title' => 'Sanitize Probe', 'content' => '<p onmouseover="x()">Keep</p><script>bad()</script>'],
            'ar' => ['title' => 'فحص', 'content' => '<p>ar</p>'],
            'ur' => ['title' => 'جانچ', 'content' => '<p>ur</p>'],
            'hi' => ['title' => 'जांच', 'content' => '<p>hi</p>'],
        ],
    ));

    $updatedContent = (string) $updated->translate('en')?->content;
    expect($updatedContent)->not->toContain('onmouseover')
        ->and($updatedContent)->not->toContain('<script')
        ->and($updatedContent)->toContain('<p>');
});

test('saving Page content automatically applies inline brand-color styling to h2/h3 headings (color: #00686D) — admin writes plain semantic HTML, backend applies the brand style on save', function () {
    $plain = '<h2>Acceptance</h2><h3>Details</h3><p>Body text</p>';

    $page = app(StorePageAction::class)->handle(new StorePageDTO(
        slug: 'brand-style-probe',
        translations: [
            'en' => ['title' => 'Brand Style', 'content' => $plain],
            'ar' => ['title' => 'فحص', 'content' => $plain],
            'ur' => ['title' => 'جانچ', 'content' => $plain],
            'hi' => ['title' => 'जांच', 'content' => $plain],
        ],
    ));

    $content = (string) $page->refresh()->load('translations')->translate('en')?->content;

    expect($content)->toContain('style="color: #00686D; font-weight: 700;"')
        ->and($content)->toContain('<h2 style="color: #00686D; font-weight: 700;">Acceptance</h2>')
        ->and($content)->toContain('<h3 style="color: #00686D; font-weight: 700;">Details</h3>')
        ->and($content)->toContain(PageHtmlBrandStyler::BRAND_TEAL);
});

test('paragraph tags are left with no forced color override (inherit default), matching the existing privacy page behavior', function () {
    $html = '<h2>Title</h2><p>Plain paragraph</p><ul><li>Item</li></ul>';

    $prepared = PageHtmlSanitizer::prepare($html);

    expect($prepared)->toContain('<p>Plain paragraph</p>')
        ->and($prepared)->not->toMatch('/<p[^>]*style=/')
        ->and($prepared)->not->toMatch('/<ul[^>]*style=/')
        ->and($prepared)->not->toMatch('/<li[^>]*style=/')
        ->and($prepared)->toContain('<h2 style="color: #00686D; font-weight: 700;">Title</h2>');
});

test('GET /api/v1/catalog/pages/terms returns a flat content string of composed wrapped cards (unchanged response shape)', function () {
    app(PagesSeeder::class)->run();

    $response = $this->getJson('/api/v1/catalog/pages/terms');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toHaveKeys(['id', 'slug', 'title', 'content'])
        ->and($json['data']['slug'])->toBe('terms')
        ->and($json['data']['content'])->toBeString()
        ->and($json['data']['content'])->toContain('data-testid="cms-page-card"')
        ->and($json['data']['content'])->toContain('<h2')
        ->and($json['data']['content'])->toContain('<p>')
        ->and($json['data']['content'])->not->toContain('<script')
        ->and($json['data']['content'])->not->toContain('<iframe');
});

test('GET /api/v1/catalog/pages/terms returns content with the inline-styled headings already baked in — this is what mobile receives and renders as-is', function () {
    app(PagesSeeder::class)->run();

    $response = $this->getJson('/api/v1/catalog/pages/terms');
    $response->assertSuccessful();

    $content = (string) $response->json('data.content');

    expect($content)->toContain('style="color: #00686D; font-weight: 700;"')
        ->and($content)->toContain('<h2 style="color: #00686D; font-weight: 700;">')
        ->and($content)->toContain('data-testid="cms-page-card"');
});

test('saving Page content via the new editor still sanitizes correctly — regression against PageHtmlSanitizer, Tiptap output must pass through unchanged for safe tags', function () {
    // Representative HTML produced by the constrained Tiptap Pages editor.
    $tiptapHtml = <<<'HTML'
<h2>Acceptance</h2><p>Hello <strong>world</strong> and <em>friends</em>.</p><ul><li>One</li><li>Two</li></ul><ol><li>First</li></ol><p><a target="_blank" rel="noopener noreferrer nofollow" href="https://example.com">Link</a></p><h3>Details</h3><p>More text</p>
HTML;

    $page = app(StorePageAction::class)->handle(new StorePageDTO(
        slug: 'tiptap-sanitize-probe',
        translations: [
            'en' => ['title' => 'Tiptap Probe', 'content' => $tiptapHtml],
            'ar' => ['title' => 'فحص', 'content' => $tiptapHtml],
            'ur' => ['title' => 'جانچ', 'content' => $tiptapHtml],
            'hi' => ['title' => 'जांच', 'content' => $tiptapHtml],
        ],
    ));

    $content = (string) $page->refresh()->load('translations')->translate('en')?->content;

    expect($content)->toContain('<h2')
        ->and($content)->toContain('<h3')
        ->and($content)->toContain('<p>')
        ->and($content)->toContain('<strong>')
        ->and($content)->toContain('<em>')
        ->and($content)->toContain('<ul>')
        ->and($content)->toContain('<ol>')
        ->and($content)->toContain('<li>')
        ->and($content)->toContain('href="https://example.com"')
        ->and($content)->toContain('color: #00686D')
        ->and($content)->not->toContain('<script')
        ->and($content)->not->toContain('<iframe');

    $direct = PageHtmlSanitizer::clean($tiptapHtml);
    expect($direct)->toContain('<h2>Acceptance</h2>')
        ->and($direct)->toContain('<strong>world</strong>')
        ->and($direct)->toContain('<em>friends</em>')
        ->and($direct)->toContain('<h3>Details</h3>');
});

test('existing Pages (privacy) are unaffected by the new inline-styling pass unless explicitly re-saved — regression, no silent retroactive rewrite of unrelated content', function () {
    $safe = '<h2>Privacy</h2><p>We collect <strong>minimal</strong> data.</p><ul><li>Email</li></ul><p><a href="https://example.com">Policy</a></p>';

    $page = Page::query()->create([
        'slug' => 'privacy-brand-regression',
        'translations' => [
            'en' => ['title' => 'Privacy', 'content' => $safe],
            'ar' => ['title' => 'الخصوصية', 'content' => $safe],
            'ur' => ['title' => 'رازداری', 'content' => $safe],
            'hi' => ['title' => 'गोपनीयता', 'content' => $safe],
        ],
    ]);

    $stored = (string) $page->refresh()->load('translations')->translate('en')?->content;

    // Direct Eloquent create bypasses the Action pipeline — no silent rewrite.
    expect($stored)->toBe($safe)
        ->and($stored)->not->toContain('#00686D')
        ->and($stored)->not->toContain('font-weight: 700');

    $cleanedOnly = PageHtmlSanitizer::clean($safe);
    expect($cleanedOnly)->toContain('<h2>Privacy</h2>')
        ->and($cleanedOnly)->not->toContain('#00686D');
});

test('visiting /pages/{slug} on the website renders the reusable CmsPage template with live CMS data', function () {
    app(PagesSeeder::class)->run();

    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);

    // Locale prefix is empty in the test harness route registration (see route:list).
    $this->get('/pages/how-to-use-agency')
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Frontend/CmsPage')
            ->where('page.slug', 'how-to-use-agency')
            ->has('page.title')
            ->where('page.content', fn ($content) => is_string($content)
                && str_contains($content, 'data-testid="cms-page-card"')
                && str_contains($content, 'The client logs into their account in the app'))
        );
});
