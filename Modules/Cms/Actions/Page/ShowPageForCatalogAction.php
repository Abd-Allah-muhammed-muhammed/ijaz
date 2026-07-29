<?php

namespace Modules\Cms\Actions\Page;

use Modules\Cms\Models\Page;

class ShowPageForCatalogAction
{
    public function handle(Page $page): Page
    {
        return $page->load('translation');
    }
}
