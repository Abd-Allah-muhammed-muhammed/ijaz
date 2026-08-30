<?php

namespace Modules\Cms\Support;

use Mews\Purifier\Facades\Purifier;
use Modules\Cms\Support\PageHtmlBrandStyler as BrandStyler;

/**
 * Sanitizes CMS Page HTML for public API / mobile rendering.
 * Allowlist matches the unrestricted Pages admin editor.
 * After sanitization, applies inline brand styles so content is self-contained.
 */
final class PageHtmlSanitizer
{
    public const string CONFIG = 'pages';

    public static function clean(string $html): string
    {
        return (string) Purifier::clean($html, self::CONFIG);
    }

    /**
     * Sanitize then apply inline brand heading styles (teal + bold).
     */
    public static function prepare(string $html): string
    {
        return BrandStyler::apply(self::clean($html));
    }

    /**
     * @param  array<string, array{title: string, content: string}>  $translations
     * @return array<string, array{title: string, content: string}>
     */
    public static function cleanTranslations(array $translations): array
    {
        foreach ($translations as $locale => $fields) {
            $translations[$locale]['content'] = self::prepare((string) ($fields['content'] ?? ''));
        }

        return $translations;
    }
}
