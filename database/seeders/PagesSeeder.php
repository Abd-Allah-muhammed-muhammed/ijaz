<?php

namespace Database\Seeders;

use App\Support\LookupCache;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Modules\Cms\Models\Page;
use Modules\Cms\Support\PageHtmlSanitizer;
use RuntimeException;

/**
 * Seeds CMS Pages:
 * - `terms` — placeholder Terms & Conditions (mobile API)
 * - five real marketing/legal pages from extracted fixtures
 *   (database/seeders/data/cms-static-pages/{locale}.json)
 *
 * Upsert-safe: re-running updates title/content for each slug.
 */
class PagesSeeder extends Seeder
{
    private const string PLACEHOLDER_MARK = '[PLACEHOLDER SECTION — replace with real legal text before launch]';

    /** @var list<string> */
    private const array MIGRATED_SLUGS = [
        'privacy',
        'policies-and-privacy',
        'how-to-use-agency',
        'real-estate-marketplace-terms',
        'service-provider-authorization',
    ];

    public function run(): void
    {
        $this->seedTermsPlaceholder();
        $this->seedMigratedStaticPages();
    }

    private function seedTermsPlaceholder(): void
    {
        $translations = [];
        foreach (['en', 'ar', 'ur', 'hi'] as $locale) {
            $translations[$locale] = [
                'title' => $this->termsTitleFor($locale),
                'content' => $this->termsPlaceholderContentFor($locale),
            ];
        }

        $this->upsertPage('terms', $translations);
    }

    private function seedMigratedStaticPages(): void
    {
        $byLocale = [];
        foreach (['en', 'ar', 'ur', 'hi'] as $locale) {
            $byLocale[$locale] = $this->loadFixture($locale);
        }

        foreach (self::MIGRATED_SLUGS as $slug) {
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

    private function termsTitleFor(string $locale): string
    {
        return match ($locale) {
            'ar' => 'الشروط والأحكام',
            'ur' => 'شرائط و ضوابط',
            'hi' => 'नियम और शर्तें',
            default => 'Terms and Conditions',
        };
    }

    private function termsPlaceholderContentFor(string $locale): string
    {
        $intro = match ($locale) {
            'ar' => 'هذا محتوى تجريبي للشروط والأحكام — ليس نصًا قانونيًا.',
            'ur' => 'یہ شرائط و ضوابط کا عارضی مواد ہے — قانونی متن نہیں۔',
            'hi' => 'यह नियम और शर्तों का प्लेसहोल्डर सामग्री है — कानूनी पाठ नहीं।',
            default => 'This is placeholder Terms and Conditions content — not legal text.',
        };

        $sectionAcceptance = match ($locale) {
            'ar' => 'القبول',
            'ur' => 'قبول',
            'hi' => 'स्वीकृति',
            default => 'Acceptance',
        };

        $sectionUse = match ($locale) {
            'ar' => 'استخدام المنصة',
            'ur' => 'پلیٹ فارم کا استعمال',
            'hi' => 'प्लेटफ़ॉर्म का उपयोग',
            default => 'Platform use',
        };

        $listItemOne = match ($locale) {
            'ar' => 'بند تجريبي أول',
            'ur' => 'پہلا عارضی نقطہ',
            'hi' => 'पहला प्लेसहोल्डर बिंदु',
            default => 'First placeholder bullet',
        };

        $listItemTwo = match ($locale) {
            'ar' => 'بند تجريبي ثانٍ',
            'ur' => 'دوسرا عارضی نقطہ',
            'hi' => 'दूसरा प्लेसहोल्डर बिंदु',
            default => 'Second placeholder bullet',
        };

        $mark = self::PLACEHOLDER_MARK;

        $html = <<<HTML
<h2>{$sectionAcceptance}</h2>
<p>{$intro}</p>
<p><strong>{$mark}</strong></p>
<h2>{$sectionUse}</h2>
<p>{$mark}</p>
<ul>
<li>{$listItemOne}</li>
<li>{$listItemTwo}</li>
</ul>
<ol>
<li>{$mark}</li>
</ol>
HTML;

        return PageHtmlSanitizer::prepare($html);
    }
}
