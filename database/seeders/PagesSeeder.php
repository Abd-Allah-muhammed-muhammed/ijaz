<?php

namespace Database\Seeders;

use App\Support\LookupCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Modules\Cms\Models\Page;
use RuntimeException;

/**
 * Seeds CMS Pages from extracted fixtures:
 * - four standalone leaf pages (privacy, how-to-use-agency, …)
 * - `terms` as one normal page whose content is service-provider-authorization
 *   + how-to-use-agency merged directly (per locale)
 *
 * Upsert-safe: re-running updates title/content for each slug.
 */
class PagesSeeder extends Seeder
{
    /** @var list<string> */
    private const array LEAF_SLUGS = [
        'privacy',
        'how-to-use-agency',
        'real-estate-marketplace-terms',
        'service-provider-authorization',
    ];

    /** @var list<string> */
    private const array TERMS_MERGE_SOURCES = [
        'service-provider-authorization',
        'how-to-use-agency',
    ];

    public function run(): void
    {
        $this->seedLeafPages();
        $this->seedTermsPage();
    }

    private function seedLeafPages(): void
    {
        $byLocale = [];
        foreach (['en', 'ar', 'ur', 'hi'] as $locale) {
            $byLocale[$locale] = $this->loadFixture($locale);
        }

        foreach (self::LEAF_SLUGS as $slug) {
            $translations = [];
            foreach (['en', 'ar', 'ur', 'hi'] as $locale) {
                if (! isset($byLocale[$locale][$slug])) {
                    throw new RuntimeException("Missing CMS fixture for [{$slug}] in {$locale}.json");
                }

                $translations[$locale] = [
                    'title' => (string) $byLocale[$locale][$slug]['title'],
                    'content' => (string) $byLocale[$locale][$slug]['content'],
                ];
            }

            $this->upsertPage($slug, $translations);
        }
    }

    private function seedTermsPage(): void
    {
        $byLocale = [];
        foreach (['en', 'ar', 'ur', 'hi'] as $locale) {
            $byLocale[$locale] = $this->loadFixture($locale);
        }

        $translations = [];
        foreach (['en', 'ar', 'ur', 'hi'] as $locale) {
            $translations[$locale] = [
                'title' => $this->termsTitleFor($locale),
                'content' => $this->mergedTermsContent($byLocale[$locale]),
            ];
        }

        $this->upsertPage('terms', $translations);
    }

    private function termsTitleFor(string $locale): string
    {
        return match ($locale) {
            'ar' => 'الشروط والأحكام',
            'ur' => 'شرائط و ضوابط',
            'hi' => 'नियम और शर्तें',
            default => 'Terms and Conditions',
        };
    }

    /**
     * @param  array<string, array{title: string, content: string}>  $fixture
     */
    private function mergedTermsContent(array $fixture): string
    {
        $parts = [];

        foreach (self::TERMS_MERGE_SOURCES as $slug) {
            if (! isset($fixture[$slug]['content'])) {
                throw new RuntimeException("Missing CMS fixture for [{$slug}] when merging terms.");
            }

            $parts[] = (string) $fixture[$slug]['content'];
        }

        return implode("\r\n", $parts);
    }

    /**
     * @return array<string, array{title: string, content: string}>
     */
    private function loadFixture(string $locale): array
    {
        $path = database_path('seeders/data/cms-static-pages/'.$locale.'.json');

        if (! File::exists($path)) {
            throw new RuntimeException("Missing CMS static page fixture: {$path}");
        }

        /** @var array<string, array{title: string, content: string}> */
        return json_decode(File::get($path), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, array{title: string, content: string}>  $translations
     */
    private function upsertPage(string $slug, array $translations): void
    {
        $page = Page::query()->firstOrNew(['slug' => $slug]);
        $page->slug = $slug;

        foreach ($translations as $locale => $fields) {
            $page->translateOrNew($locale)->fill($fields);
        }

        $page->save();

        LookupCache::forgetAllLocales('pages:all');
        LookupCache::forgetScopedAllLocales('pages:single', $slug);
    }
}
