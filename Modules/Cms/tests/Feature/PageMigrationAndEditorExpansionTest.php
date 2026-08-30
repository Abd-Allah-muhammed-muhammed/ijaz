<?php

use Database\Seeders\PagesSeeder;
use Illuminate\Support\Facades\File;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Cms\Models\Page;
use Modules\Cms\Support\PageHtmlSanitizer;

test('Pages content sanitizer now allows table/thead/tbody/tr/td/th, blockquote, code, pre, hr, s/del, and inline color/text-align styles, in addition to the previous safe set', function () {
    $html = <<<'HTML'
<h2 style="color: #00686D; font-weight: 700;">Heading</h2>
<p style="text-align: center; color: #071437;">Aligned colored paragraph</p>
<blockquote><p>Quoted</p></blockquote>
<p><code>inline</code> and <s>struck</s> <del>deleted</del> <u>under</u></p>
<pre><code>block</code></pre>
<hr>
<table><thead><tr><th>H</th></tr></thead><tbody><tr><td style="text-align:left;">C</td></tr></tbody></table>
HTML;

    $cleaned = PageHtmlSanitizer::clean($html);

    expect($cleaned)->toContain('<table>')
        ->and($cleaned)->toContain('<thead>')
        ->and($cleaned)->toContain('<tbody>')
        ->and($cleaned)->toContain('<tr>')
        ->and($cleaned)->toContain('<th>')
        ->and($cleaned)->toContain('<td')
        ->and($cleaned)->toContain('<blockquote>')
        ->and($cleaned)->toContain('<code>')
        ->and($cleaned)->toContain('<pre>')
        ->and($cleaned)->toContain('<hr')
        ->and($cleaned)->toContain('<s>')
        ->and($cleaned)->toContain('<del>')
        ->and($cleaned)->toContain('<u>')
        ->and($cleaned)->toContain('text-align:center')
        ->and($cleaned)->toContain('color:#071437');
});

test('a full round-trip: admin content using every newly-allowed element (table, color, alignment, blockquote) survives sanitization unchanged', function () {
    $html = <<<'HTML'
<h1>Title</h1>
<p style="text-align: right; color: #dc3545;">Right red</p>
<blockquote><p>Quote me</p></blockquote>
<table>
<thead><tr><th>A</th><th>B</th></tr></thead>
<tbody><tr><td>1</td><td>2</td></tr></tbody>
</table>
<p><strong>bold</strong> <em>italic</em> <u>under</u> <s>strike</s></p>
<hr>
<pre><code>code block</code></pre>
HTML;

    $prepared = PageHtmlSanitizer::prepare($html);

    expect($prepared)->toContain('<h1 style="color: #00686D; font-weight: 700;">Title</h1>')
        ->and($prepared)->toContain('text-align:right')
        ->and($prepared)->toContain('color:#dc3545')
        ->and($prepared)->toContain('<blockquote>')
        ->and($prepared)->toContain('<table>')
        ->and($prepared)->toContain('<th>A</th>')
        ->and($prepared)->toContain('<td>1</td>')
        ->and($prepared)->toContain('<u>under</u>')
        ->and($prepared)->toContain('<s>strike</s>')
        ->and($prepared)->toContain('<hr')
        ->and($prepared)->toContain('<pre>')
        ->and($prepared)->toContain('code block');
});

test('seeding the 5 real pages populates all 4 locales with the actual extracted lang content, not placeholder text', function () {
    app(PagesSeeder::class)->run();

    $fixtureEn = json_decode(
        File::get(database_path('seeders/data/cms-static-pages/en.json')),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $expectedSnippets = [
        'privacy' => 'This privacy statement explains how Ijaz collects',
        'policies-and-privacy' => 'Welcome to Ijaz platform, where we aim to create a secure',
        'how-to-use-agency' => 'The client logs into their account in the app',
        'real-estate-marketplace-terms' => 'No user is allowed to add a property unless they hold a valid license',
        'service-provider-authorization' => 'the authorization must be made exclusively inside Ijaz platform',
    ];

    foreach ($expectedSnippets as $slug => $snippet) {
        $page = Page::query()->where('slug', $slug)->first();
        expect($page)->not->toBeNull();

        foreach (['en', 'ar', 'ur', 'hi'] as $locale) {
            $translation = $page->translate($locale);
            expect($translation)->not->toBeNull()
                ->and((string) $translation->title)->not->toBeEmpty()
                ->and((string) $translation->content)->not->toBeEmpty()
                ->and((string) $translation->content)->not->toContain('[PLACEHOLDER SECTION');
        }

        $enContent = (string) $page->translate('en')?->content;
        expect($enContent)->toContain($snippet)
            ->and($enContent)->toContain('/media/logos/default.svg')
            ->and($enContent)->toBe((string) $fixtureEn[$slug]['content']);
    }
});

test('GET /api/v1/catalog/pages/{slug} returns correct real content for each of the 5 migrated slugs', function () {
    app(PagesSeeder::class)->run();

    $slugs = [
        'privacy',
        'policies-and-privacy',
        'how-to-use-agency',
        'real-estate-marketplace-terms',
        'service-provider-authorization',
    ];

    foreach ($slugs as $slug) {
        $response = $this->getJson('/api/v1/catalog/pages/'.$slug);

        $response->assertSuccessful()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.slug', $slug);

        $content = (string) $response->json('data.content');
        $title = (string) $response->json('data.title');

        expect($title)->not->toBeEmpty()
            ->and($content)->not->toBeEmpty()
            ->and($content)->not->toContain('[PLACEHOLDER SECTION')
            ->and($content)->toContain('<h2');
    }
});

test('the 5 original website URLs still return 200 and now render via CmsPageView with equivalent real content', function () {
    app(PagesSeeder::class)->run();

    $this->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    $this->withoutVite();

    $routes = [
        '/privacy-and-policies' => ['slug' => 'policies-and-privacy', 'snippet' => 'Welcome to Ijaz platform'],
        '/privacy-policy' => ['slug' => 'privacy', 'snippet' => 'This privacy statement explains how Ijaz collects'],
        '/how-to-use-agency' => ['slug' => 'how-to-use-agency', 'snippet' => 'The client logs into their account in the app'],
        '/real-estate-marketplace-terms-of-use' => ['slug' => 'real-estate-marketplace-terms', 'snippet' => 'valid license issued by the Saudi Real Estate Authority'],
        '/service-provider-authorization-terms-and-conditions' => ['slug' => 'service-provider-authorization', 'snippet' => 'authorization must be made exclusively'],
    ];

    foreach ($routes as $path => $expectation) {
        $this->get($path)
            ->assertSuccessful()
            ->assertInertia(fn ($page) => $page
                ->component('Frontend/CmsPage')
                ->where('page.slug', $expectation['slug'])
                ->where('page.content', fn ($content) => is_string($content)
                    && str_contains($content, $expectation['snippet'])
                    && ! str_contains($content, '[PLACEHOLDER SECTION'))
            );
    }
});
