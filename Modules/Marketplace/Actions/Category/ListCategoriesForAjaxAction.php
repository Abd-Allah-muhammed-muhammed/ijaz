<?php

namespace Modules\Marketplace\Actions\Category;

use Illuminate\Database\Eloquent\Collection;
use Modules\Marketplace\Contracts\Repositories\CategoryRepositoryInterface;
use Modules\Marketplace\Models\Category;

class ListCategoriesForAjaxAction
{
    public function __construct(
        private readonly CategoryRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, Category>
     */
    public function handle(?string $search = null, int $parentId = 0, ?int $providerTypeId = null): Collection
    {
        return $this->repository->listForAjax($search, $parentId, $providerTypeId);
    }
}
