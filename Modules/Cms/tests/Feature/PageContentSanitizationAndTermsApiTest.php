<?php

use Database\Seeders\PagesSeeder;
use Modules\Cms\Actions\Page\StorePageAction;
use Modules\Cms\Actions\Page\UpdatePageAction;
use Modules\Cms\DTOs\StorePageDTO;
use Modules\Cms\DTOs\UpdatePageDTO;
use Modules\Cms\Models\Page;
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
        ->and($content)->toContain('<h2>')
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

test('GET /api/v1/catalog/pages/terms returns the seeded Terms page with clean structured HTML content', function () {
    app(PagesSeeder::class)->run();

    $response = $this->getJson('/api/v1/catalog/pages/terms');
    $response->assertSuccessful();

    $json = $response->json();
    expect($json)->toHaveKeys(['success', 'message', 'data', 'errors'])
        ->and($json['data'])->toHaveKeys(['id', 'slug', 'title', 'content'])
        ->and($json['data']['slug'])->toBe('terms')
        ->and($json['data']['content'])->toContain('<h2>')
        ->and($json['data']['content'])->toContain('<p>')
        ->and($json['data']['content'])->toContain('<ul>')
        ->and($json['data']['content'])->toContain('<ol>')
        ->and($json['data']['content'])->toContain('[PLACEHOLDER SECTION — replace with real legal text before launch]')
        ->and($json['data']['content'])->not->toContain('<script')
        ->and($json['data']['content'])->not->toContain('<iframe');

    // Placeholder structure must survive the same sanitizer used on save.
    $reSanitized = PageHtmlSanitizer::clean($json['data']['content']);
    expect($reSanitized)->toContain('<h2>')
        ->and($reSanitized)->toContain('<ul>')
        ->and($reSanitized)->toContain('<ol>')
        ->and($reSanitized)->toContain('[PLACEHOLDER SECTION — replace with real legal text before launch]');
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

    expect($content)->toContain('<h2>')
        ->and($content)->toContain('<h3>')
        ->and($content)->toContain('<p>')
        ->and($content)->toContain('<strong>')
        ->and($content)->toContain('<em>')
        ->and($content)->toContain('<ul>')
        ->and($content)->toContain('<ol>')
        ->and($content)->toContain('<li>')
        ->and($content)->toContain('href="https://example.com"')
        ->and($content)->not->toContain('<script')
        ->and($content)->not->toContain('<iframe');

    $direct = PageHtmlSanitizer::clean($tiptapHtml);
    expect($direct)->toContain('<h2>Acceptance</h2>')
        ->and($direct)->toContain('<strong>world</strong>')
        ->and($direct)->toContain('<em>friends</em>')
        ->and($direct)->toContain('<h3>Details</h3>');
});

test('existing Pages (e.g. privacy) are unaffected by the sanitization change — regression, safe existing content passes through unchanged', function () {
    $safe = '<h2>Privacy</h2><p>We collect <strong>minimal</strong> data.</p><ul><li>Email</li></ul><p><a href="https://example.com">Policy</a></p>';

    $page = Page::query()->create([
        'slug' => 'privacy',
        'translations' => [
            'en' => ['title' => 'Privacy', 'content' => $safe],
            'ar' => ['title' => 'الخصوصية', 'content' => $safe],
            'ur' => ['title' => 'رازداری', 'content' => $safe],
            'hi' => ['title' => 'गोपनीयता', 'content' => $safe],
        ],
    ]);

    $cleaned = PageHtmlSanitizer::clean($safe);

    expect($cleaned)->toContain('<h2>Privacy</h2>')
        ->and($cleaned)->toContain('<strong>minimal</strong>')
        ->and($cleaned)->toContain('<ul>')
        ->and($cleaned)->toContain('<li>Email</li>')
        ->and($cleaned)->toContain('href="https://example.com"');

    $updated = app(UpdatePageAction::class)->handle($page, new UpdatePageDTO(
        slug: 'privacy',
        translations: [
            'en' => ['title' => 'Privacy', 'content' => $safe],
            'ar' => ['title' => 'الخصوصية', 'content' => $safe],
            'ur' => ['title' => 'رازداری', 'content' => $safe],
            'hi' => ['title' => 'गोपनीयता', 'content' => $safe],
        ],
    ));

    expect((string) $updated->translate('en')?->content)->toContain('<h2>Privacy</h2>')
        ->and((string) $updated->translate('en')?->content)->toContain('<strong>minimal</strong>')
        ->and((string) $updated->translate('en')?->content)->toContain('<li>Email</li>');
});
