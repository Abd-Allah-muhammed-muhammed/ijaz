<?php

namespace Modules\Cms\Actions\Page;

use Illuminate\Support\Facades\View;
use Modules\Cms\Contracts\Repositories\PageRepositoryInterface;
use Modules\Cms\Models\Page;

/**
 * Render a CMS page's final HTML for catalog surfaces (website + API).
 *
 * Card/badge wrapping and relative→absolute image URL rewriting happen here
 * at request time — never at save time — so shell/design and environment URL
 * changes apply instantly without re-saving pages.
 *
 * When `composed_of_slugs` is set, this page's own `content` is ignored and
 * each referenced page is rendered (recursively) and concatenated in order.
 */
class RenderCmsPageContentAction
{
    public function __construct(
        private readonly PageRepositoryInterface $repository,
    ) {}

    /**
     * @param  list<string>  $visitedSlugs
     */
    public function handle(Page $page, array $visitedSlugs = []): string
    {
        $slug = (string) $page->slug;

        if (in_array($slug, $visitedSlugs, true)) {
            return '';
        }

        $visitedSlugs[] = $slug;

        $composedOf = $page->composed_of_slugs;

        if (is_array($composedOf) && $composedOf !== []) {
            $parts = [];

            foreach ($composedOf as $childSlug) {
                if (! is_string($childSlug) || $childSlug === '') {
                    continue;
                }

                if (in_array($childSlug, $visitedSlugs, true)) {
                    continue;
                }

                $child = $this->repository->loadForCatalogBySlug($childSlug);
                $parts[] = $this->handle($child, $visitedSlugs);
            }

            return implode('', $parts);
        }

        return $this->renderCard(
            title: (string) $page->title,
            content: (string) ($page->content ?? ''),
        );
    }

    private function renderCard(string $title, string $content): string
    {
        return View::make('cms.page-card', [
            'title' => $title,
            'content' => $content,
        ])->render();
    }
}
