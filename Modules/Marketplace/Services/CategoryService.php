<?php

namespace Modules\Marketplace\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Marketplace\Actions\Category\DeleteCategoryAction;
use Modules\Marketplace\Actions\Category\GetCategoriesForDropdownAction;
use Modules\Marketplace\Actions\Category\ListCategoriesAction;
use Modules\Marketplace\Actions\Category\ListCategoriesForApiAction;
use Modules\Marketplace\Actions\Category\ListCategoriesForSelectAction;
use Modules\Marketplace\Actions\Category\ListLeafCategoriesAction;
use Modules\Marketplace\Actions\Category\ListRootCategoriesAction;
use Modules\Marketplace\Actions\Category\ShowCategoryAction;
use Modules\Marketplace\Actions\Category\StoreCategoryAction;
use Modules\Marketplace\Actions\Category\UpdateCategoryAction;
use Modules\Marketplace\DTOs\StoreCategoryDTO;
use Modules\Marketplace\DTOs\UpdateCategoryDTO;
use Modules\Marketplace\Models\Category;

class CategoryService
{
    public function __construct(
        private readonly ListCategoriesAction $listAction,
        private readonly ListCategoriesForApiAction $listForApiAction,
        private readonly ListCategoriesForSelectAction $listForSelectAction,
        private readonly ListRootCategoriesAction $listRootAction,
        private readonly ListLeafCategoriesAction $listLeafAction,
        private readonly StoreCategoryAction $storeAction,
        private readonly UpdateCategoryAction $updateAction,
        private readonly DeleteCategoryAction $deleteAction,
        private readonly ShowCategoryAction $showAction,
        private readonly GetCategoriesForDropdownAction $dropdownAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function listForApi(Request $request): LengthAwarePaginator
    {
        return $this->listForApiAction->handle($request);
    }

    public function listWithNoChildrenForApi(Request $request): LengthAwarePaginator
    {
        return $this->listForApiAction->handleWithNoChildren($request);
    }

    public function listChildrenForApi(Category $category, Request $request): LengthAwarePaginator
    {
        return $this->listForApiAction->handleChildren($category, $request);
    }

    public function store(StoreCategoryDTO $dto): Category
    {
        return $this->storeAction->handle($dto);
    }

    public function update(Category $category, UpdateCategoryDTO $dto): Category
    {
        return $this->updateAction->handle($category, $dto);
    }

    public function destroy(Category $category): void
    {
        $this->deleteAction->handle($category);
    }

    public function show(Category $category): Category
    {
        return $this->showAction->handle($category);
    }

    /** @return Collection<int, Category> */
    public function getRootCategories(): Collection
    {
        return $this->listRootAction->handle();
    }

    /** @return Collection<int, Category> */
    public function getLeafCategories(): Collection
    {
        return $this->listLeafAction->handle();
    }

    /** @return Collection<int, Category> */
    public function getAllForDropdown(): Collection
    {
        return $this->dropdownAction->handle();
    }

    /** @return Collection<int, Category> */
    public function getAllExcept(Category $category): Collection
    {
        return $this->dropdownAction->handleExcept($category);
    }

    public function listForSelect(?string $search = null, int $parentId = 0, int $perPage = 10): LengthAwarePaginator
    {
        return $this->listForSelectAction->handle($search, $parentId, $perPage);
    }
}
