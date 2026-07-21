<?php

namespace Modules\Cms\Actions\Page;

use Illuminate\Database\Eloquent\Collection;
use Modules\Cms\Contracts\Repositories\PageRepositoryInterface;
use Modules\Cms\Models\Page;

class ListPagesForCatalogAction
{
    public function __construct(
        private readonly PageRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, Page>
     */
    public function handle(): Collection
    {
        return $this->repository->getAllForCatalog();
    }
}
