<?php

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Actions\PropertyCategory\DeletePropertyCategoryAction;
use Modules\Catalog\Actions\PropertyCategory\ListPropertyCategoriesAction;
use Modules\Catalog\Actions\PropertyCategory\ListPropertyCategoriesForApiAction;
use Modules\Catalog\Actions\PropertyCategory\ListPropertyCategoriesForSelectAction;
use Modules\Catalog\Actions\PropertyCategory\ListRootPropertyCategoriesAction;
use Modules\Catalog\Actions\PropertyCategory\ShowPropertyCategoryAction;
use Modules\Catalog\Actions\PropertyCategory\StorePropertyCategoryAction;
use Modules\Catalog\Actions\PropertyCategory\UpdatePropertyCategoryAction;
use Modules\Catalog\Contracts\Services\PropertyCategoryServiceInterface;
use Modules\Catalog\DTOs\StorePropertyCategoryDTO;
use Modules\Catalog\DTOs\UpdatePropertyCategoryDTO;
use Modules\Catalog\Models\PropertyCategory;
use Modules\Catalog\QueryFilters\PropertyCategory\PropertyCategoryFilters;

class PropertyCategoryService implements PropertyCategoryServiceInterface
{
    public function __construct(
        private readonly ListPropertyCategoriesAction $listAction,
        private readonly ListPropertyCategoriesForApiAction $listForApiAction,
        private readonly ListPropertyCategoriesForSelectAction $listForSelectAction,
        private readonly StorePropertyCategoryAction $storeAction,
        private readonly UpdatePropertyCategoryAction $updateAction,
        private readonly DeletePropertyCategoryAction $deleteAction,
        private readonly ShowPropertyCategoryAction $showAction,
        private readonly ListRootPropertyCategoriesAction $listRootAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function paginate(PropertyCategoryFilters $filters): LengthAwarePaginator
    {
        return $this->listForApiAction->handle($filters);
    }

    public function store(StorePropertyCategoryDTO $dto): PropertyCategory
    {
        return $this->storeAction->handle($dto);
    }

    public function update(PropertyCategory $propertyCategory, UpdatePropertyCategoryDTO $dto): PropertyCategory
    {
        return $this->updateAction->handle($propertyCategory, $dto);
    }

    public function destroy(PropertyCategory $propertyCategory): void
    {
        $this->deleteAction->handle($propertyCategory);
    }

    public function show(PropertyCategory $propertyCategory): PropertyCategory
    {
        return $this->showAction->handle($propertyCategory);
    }

    /**
     * @return Collection<int, PropertyCategory>
     */
    public function getRootCategories(): Collection
    {
        return $this->listRootAction->handle();
    }

    /**
     * @return Collection<int, PropertyCategory>
     */
    public function listForSelect(?string $search = null): Collection
    {
        return $this->listForSelectAction->handle($search);
    }
}
