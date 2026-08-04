<?php

namespace Modules\Cms\Actions\Page;

use App\Support\LookupCache;
use Modules\Cms\Contracts\Repositories\PageRepositoryInterface;
use Modules\Cms\Models\Page;

class DeletePageAction
{
    public function __construct(
        private readonly PageRepositoryInterface $repository,
    ) {}

    public function handle(Page $page): void
    {
        $slug = (string) $page->slug;

        $this->repository->delete($page);

        LookupCache::forgetAllLocales('pages:all');
        LookupCache::forgetScopedAllLocales('pages:single', $slug);
    }
}
