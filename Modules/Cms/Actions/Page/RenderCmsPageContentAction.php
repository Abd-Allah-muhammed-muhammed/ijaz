<?php

namespace Modules\Cms\Actions\Page;

use Illuminate\Support\Facades\View;
use Modules\Cms\Models\Page;

/**
 * Render a CMS page's final HTML for catalog surfaces (website + API).
 *
 * Card/badge wrapping and relative→absolute image URL rewriting happen here
 * at request time — never at save time — so shell/design and environment URL
 * changes apply instantly without re-saving pages.
 */
class RenderCmsPageContentAction
{
    public function handle(Page $page): string
    {
        return View::make('cms.page-card', [
            'title' => (string) $page->title,
            'content' => (string) ($page->content ?? ''),
        ])->render();
    }
}
