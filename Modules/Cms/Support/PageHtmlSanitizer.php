<?php

namespace Modules\Cms\Support;

use Mews\Purifier\Facades\Purifier;

/**
 * Sanitizes CMS Page HTML for public API / mobile rendering.
 * Allows only the formatting set produced by the constrained Pages admin editor.
 */
final class PageHtmlSanitizer
{
    public const string CONFIG = 'pages';

    public static function clean(string $html): string
    {
        return (string) Purifier::clean($html, self::CONFIG);
    }

    /**
     * @param  array<string, array{title: string, content: string}>  $translations
     * @return array<string, array{title: string, content: string}>
     */
    public static function cleanTranslations(array $translations): array
    {
        foreach ($translations as $locale => $fields) {
            $translations[$locale]['content'] = self::clean((string) ($fields['content'] ?? ''));
        }

        return $translations;
    }
}
