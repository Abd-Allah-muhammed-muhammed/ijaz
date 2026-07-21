<?php

namespace Modules\Cms\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Cms\Actions\Page\DeletePageAction;
use Modules\Cms\Actions\Page\ListPagesAction;
use Modules\Cms\Actions\Page\ListPagesForCatalogAction;
use Modules\Cms\Actions\Page\ShowPageAction;
use Modules\Cms\Actions\Page\ShowPageForCatalogAction;
use Modules\Cms\Actions\Page\StorePageAction;
use Modules\Cms\Actions\Page\UpdatePageAction;
use Modules\Cms\DTOs\StorePageDTO;
use Modules\Cms\DTOs\UpdatePageDTO;
use Modules\Cms\Models\Page;

class PageService
{
    public function __construct(
        private readonly ListPagesAction $listAction,
        private readonly StorePageAction $storeAction,
        private readonly UpdatePageAction $updateAction,
        private readonly DeletePageAction $deleteAction,
        private readonly ShowPageAction $showAction,
        private readonly ListPagesForCatalogAction $listForCatalogAction,
        private readonly ShowPageForCatalogAction $showForCatalogAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function store(StorePageDTO $dto): Page
    {
        return $this->storeAction->handle($dto);
    }

    public function update(Page $page, UpdatePageDTO $dto): Page
    {
        return $this->updateAction->handle($page, $dto);
    }

    public function destroy(Page $page): void
    {
        $this->deleteAction->handle($page);
    }

    public function show(Page $page): Page
    {
        return $this->showAction->handle($page);
    }

    /**
     * @return Collection<int, Page>
     */
    public function listForCatalog(): Collection
    {
        return $this->listForCatalogAction->handle();
    }

    public function showForCatalog(Page $page): Page
    {
        return $this->showForCatalogAction->handle($page);
    }
}
