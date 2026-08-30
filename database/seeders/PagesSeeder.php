<?php

namespace Database\Seeders;

use App\Support\LookupCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Modules\Cms\Models\Page;
use RuntimeException;

/**
 * Seeds CMS Pages:
 * - leaf pages from extracted fixtures (privacy, how-to-use-agency, …)
 * - `terms` and `policies-and-privacy` as compositions (composed_of_slugs)
 *   of those leaf pages — own content left empty
 *
 * Upsert-safe: re-running updates title/content/composition for each slug.
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

    /** @var array<string, list<string>> */
    private const array COMPOSITIONS = [
        'terms' => [
            'service-provider-authorization',
            'how-to-use-agency',
        ],
        'policies-and-privacy' => [
            'privacy',
            'service-provider-authorization',
            'how-to-use-agency',
            'real-estate-marketplace-terms',
        ],
    ];

    public function run(): void
    {
        $this->seedLeafPages();
        $this->seedComposedPages();
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

            $this->upsertPage($slug, $translations, null);
        }
    }

    private function seedComposedPages(): void
    {
        $byLocale = [];
        foreach (['en', 'ar', 'ur', 'hi'] as $locale) {
            $byLocale[$locale] = $this->loadFixture($locale);
        }

        foreach (self::COMPOSITIONS as $slug => $composedOf) {
            $translations = [];
            foreach (['en', 'ar', 'ur', 'hi'] as $locale) {
                $translations[$locale] = [
                    'title' => $this->composedTitleFor($slug, $locale, $byLocale[$locale]),
                    'content' => '',
                ];
            }

            $this->upsertPage($slug, $translations, $composedOf);
        }
    }

    /**
     * @param  array<string, array{title: string, content: string}>  $fixture
     */
    private function composedTitleFor(string $slug, string $locale, array $fixture): string
    {
        if ($slug === 'terms') {
            return match ($locale) {
                'ar' => 'الشروط والأحكام',
                'ur' => 'شرائط و ضوابط',
                'hi' => 'नियम और शर्तें',
                default => 'Terms and Conditions',
            };
        }

        if (isset($fixture[$slug]['title'])) {
            return (string) $fixture[$slug]['title'];
        }

        return match ($locale) {
            'ar' => 'السياسات والخصوصية في المنصة',
            'ur' => 'پلیٹ فارم کی پالیسیز اور پرائیویسی',
            'hi' => 'प्लेटफ़ॉर्म पर नीतियाँ और गोपनीयता',
            default => 'Policies and Privacy in the Platform',
        };
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
     * @param  list<string>|null  $composedOfSlugs
     */
    private function upsertPage(string $slug, array $translations, ?array $composedOfSlugs): void
    {
        $page = Page::query()->firstOrNew(['slug' => $slug]);
        $page->slug = $slug;
        $page->composed_of_slugs = $composedOfSlugs;

        foreach ($translations as $locale => $fields) {
            $page->translateOrNew($locale)->fill($fields);
        }

        $page->save();

        LookupCache::forgetAllLocales('pages:all');
        LookupCache::forgetScopedAllLocales('pages:single', $slug);
    }
}
