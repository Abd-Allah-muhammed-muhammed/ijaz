<?php

namespace Modules\Cms\Support;

use DOMDocument;
use DOMElement;
use DOMNode;

/**
 * Converts legacy marketing-page lang HTML (Bootstrap classes / bold label paragraphs)
 * into clean semantic HTML suitable for CMS Pages storage.
 */
final class StaticPageLangHtmlConverter
{
    /**
     * Convert a single content HTML fragment from lang/*.json.
     */
    public static function convertBody(string $html): string
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

        $body = $dom->getElementsByTagName('body')->item(0);

        if ($body === null) {
            return $trimmed;
        }

        /** @var list<DOMElement> $paragraphs */
        $paragraphs = [];
        foreach ($body->getElementsByTagName('p') as $paragraph) {
            if ($paragraph instanceof DOMElement) {
                $paragraphs[] = $paragraph;
            }
        }

        foreach ($paragraphs as $paragraph) {
            $class = strtolower((string) $paragraph->getAttribute('class'));
            $isSectionLabel = str_contains($class, 'fw-bold')
                || str_contains($class, 'text-success');

            if ($isSectionLabel) {
                $heading = $dom->createElement('h2');
                while ($paragraph->firstChild !== null) {
                    $heading->appendChild($paragraph->firstChild);
                }
                $paragraph->parentNode?->replaceChild($heading, $paragraph);

                continue;
            }

            $paragraph->removeAttribute('class');
            $paragraph->removeAttribute('style');
        }

        foreach (['ul', 'ol', 'li', 'strong', 'em', 'b', 'i', 'a', 'span', 'div'] as $tagName) {
            /** @var list<DOMElement> $elements */
            $elements = [];
            foreach ($body->getElementsByTagName($tagName) as $element) {
                if ($element instanceof DOMElement) {
                    $elements[] = $element;
                }
            }

            foreach ($elements as $element) {
                if ($tagName === 'div') {
                    // Unwrap leftover wrappers: move children up, remove div.
                    $parent = $element->parentNode;
                    if ($parent === null) {
                        continue;
                    }
                    while ($element->firstChild !== null) {
                        $parent->insertBefore($element->firstChild, $element);
                    }
                    $parent->removeChild($element);

                    continue;
                }

                $element->removeAttribute('class');
                if ($tagName !== 'a') {
                    $element->removeAttribute('style');
                }
            }
        }

        return self::innerHtml($body);
    }

    /**
     * Build hub-style HTML: centered logo + optional section title + converted body.
     */
    public static function section(string $bodyHtml, ?string $sectionTitle = null, bool $withLogo = true): string
    {
        $parts = [];

        if ($withLogo) {
            $parts[] = '<p style="text-align:center;"><img src="/media/logos/default.svg" alt="Ijaz" width="120" height="120"></p>';
        }

        if ($sectionTitle !== null && trim($sectionTitle) !== '') {
            $parts[] = '<h2>'.e(trim($sectionTitle)).'</h2>';
        }

        $converted = self::convertBody($bodyHtml);
        if ($converted !== '') {
            $parts[] = $converted;
        }

        return implode("\n", $parts);
    }

    private static function innerHtml(DOMNode $node): string
    {
        $html = '';
        foreach ($node->childNodes as $child) {
            $html .= $node->ownerDocument?->saveHTML($child) ?? '';
        }

        return trim($html);
    }
}
