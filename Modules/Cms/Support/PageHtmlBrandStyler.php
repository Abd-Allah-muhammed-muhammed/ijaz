<?php

namespace Modules\Cms\Support;

use DOMDocument;
use DOMElement;

/**
 * Applies self-contained inline brand styles to CMS Page HTML after sanitization.
 * Headings get brand teal + bold; body tags are left to inherit default text color.
 */
final class PageHtmlBrandStyler
{
    public const string BRAND_TEAL = '#00686D';

    public const string HEADING_STYLE = 'color: #00686D; font-weight: 700;';

    /** Official logo path used on existing web legal pages (root-relative, production-safe). */
    public const string LOGO_SRC = '/media/logos/default.svg';

    public static function apply(string $html): string
    {
        $trimmed = trim($html);

        if ($trimmed === '') {
            return '';
        }

        $dom = new DOMDocument('1.0', 'UTF-8');
        $previous = libxml_use_internal_errors(true);
        $dom->loadHTML(
            '<?xml encoding="UTF-8"><body>'.$trimmed.'</body>',
            LIBXML_HTML_NODEFDTD
        );
        libxml_clear_errors();
        libxml_use_internal_errors($previous);

        foreach (['h1', 'h2', 'h3'] as $tagName) {
            /** @var list<DOMElement> $elements */
            $elements = [];
            foreach ($dom->getElementsByTagName($tagName) as $element) {
                if ($element instanceof DOMElement) {
                    $elements[] = $element;
                }
            }

            foreach ($elements as $element) {
                $element->setAttribute('style', self::HEADING_STYLE);
            }
        }

        $body = $dom->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return $trimmed;
        }

        $output = '';
        foreach ($body->childNodes as $child) {
            $output .= $dom->saveHTML($child);
        }

        return $output;
    }
}
