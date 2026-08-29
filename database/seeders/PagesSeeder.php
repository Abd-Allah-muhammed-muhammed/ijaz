<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Cms\Models\Page;

/**
 * Seeds the Terms & Conditions CMS page (slug: terms) with clean placeholder HTML.
 * Upsert-safe: re-running updates title/content for the existing slug.
 */
class PagesSeeder extends Seeder
{
    private const string PLACEHOLDER_MARK = '[PLACEHOLDER SECTION — replace with real legal text before launch]';

    public function run(): void
    {
        $translations = [];
        foreach (['en', 'ar', 'ur', 'hi'] as $locale) {
            $translations[$locale] = [
                'title' => $this->titleFor($locale),
                'content' => $this->placeholderContentFor($locale),
            ];
        }

        $page = Page::query()->firstOrNew(['slug' => 'terms']);
        $page->slug = 'terms';

        foreach ($translations as $locale => $fields) {
            $page->translateOrNew($locale)->fill($fields);
        }

        $page->save();
    }

    private function titleFor(string $locale): string
    {
        return match ($locale) {
            'ar' => 'الشروط والأحكام',
            'ur' => 'شرائط و ضوابط',
            'hi' => 'नियम और शर्तें',
            default => 'Terms and Conditions',
        };
    }

    private function placeholderContentFor(string $locale): string
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

        return <<<HTML
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
    }
}
