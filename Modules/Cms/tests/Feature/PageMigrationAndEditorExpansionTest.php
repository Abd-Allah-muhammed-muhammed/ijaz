<?php

use Database\Seeders\PagesSeeder;
use Illuminate\Support\Facades\File;
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

test('seeding CMS pages populates all 4 locales with the actual extracted lang content, not placeholder text', function () {
    app(PagesSeeder::class)->run();

    $fixtureEn = json_decode(
        File::get(database_path('seeders/data/cms-static-pages/en.json')),
        true,
        512,
        JSON_THROW_ON_ERROR
    );

    $expectedSnippets = [
        'privacy' => 'This privacy statement explains how Ijaz collects',
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

    $terms = Page::query()->where('slug', 'terms')->firstOrFail();
    $mergedEn = (string) $terms->translate('en')?->content;

    expect($mergedEn)->toContain('authorization must be made exclusively inside Ijaz platform')
        ->and($mergedEn)->toContain('The client logs into their account in the app')
        ->and($mergedEn)->toBe(
            (string) $fixtureEn['service-provider-authorization']['content']."\r\n".(string) $fixtureEn['how-to-use-agency']['content']
        );
});

test('GET /api/v1/catalog/pages/{slug} returns correct real content for CMS catalog slugs', function () {
    app(PagesSeeder::class)->run();

    $slugs = [
        'privacy',
        'terms',
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
            ->and($content)->toContain('data-testid="cms-page-card"')
            ->and($content)->toContain('<h2');
    }
});
