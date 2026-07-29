<?php

namespace Modules\Cms\Contracts\Repositories;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Cms\Models\Page;

interface PageRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator;

    public function create(array $data): Page;

    public function update(Page $page, array $data): Page;

    public function delete(Page $page): void;

    public function loadForEdit(Page $page): Page;

    /**
     * @return Collection<int, Page>
     */
    public function getAllForCatalog(): Collection;
}
