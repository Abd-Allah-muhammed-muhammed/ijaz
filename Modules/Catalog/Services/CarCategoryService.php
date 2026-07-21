<?php

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Actions\CarCategory\DeleteCarCategoryAction;
use Modules\Catalog\Actions\CarCategory\ListCarCategoriesAction;
use Modules\Catalog\Actions\CarCategory\ListCarCategoriesForSelectAction;
use Modules\Catalog\Actions\CarCategory\ListRootCarCategoriesAction;
use Modules\Catalog\Actions\CarCategory\ShowCarCategoryAction;
use Modules\Catalog\Actions\CarCategory\StoreCarCategoryAction;
use Modules\Catalog\Actions\CarCategory\UpdateCarCategoryAction;
use Modules\Catalog\Contracts\Services\CarCategoryServiceInterface;
use Modules\Catalog\DTOs\StoreCarCategoryDTO;
use Modules\Catalog\DTOs\UpdateCarCategoryDTO;
use Modules\Catalog\Models\CarCategory;

class CarCategoryService implements CarCategoryServiceInterface
{
    public function __construct(
        private readonly ListCarCategoriesAction $listAction,
        private readonly ListCarCategoriesForSelectAction $listForSelectAction,
        private readonly StoreCarCategoryAction $storeAction,
        private readonly UpdateCarCategoryAction $updateAction,
        private readonly DeleteCarCategoryAction $deleteAction,
        private readonly ShowCarCategoryAction $showAction,
        private readonly ListRootCarCategoriesAction $listRootAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function store(StoreCarCategoryDTO $dto): CarCategory
    {
        return $this->storeAction->handle($dto);
    }

    public function update(CarCategory $carCategory, UpdateCarCategoryDTO $dto): CarCategory
    {
        return $this->updateAction->handle($carCategory, $dto);
    }

    public function destroy(CarCategory $carCategory): void
    {
        $this->deleteAction->handle($carCategory);
    }

    public function show(CarCategory $carCategory): CarCategory
    {
        return $this->showAction->handle($carCategory);
    }

    /**
     * @return Collection<int, CarCategory>
     */
    public function getRootCategories(): Collection
    {
        return $this->listRootAction->handle();
    }

    /**
     * @return Collection<int, CarCategory>
     */
    public function listForSelect(?string $search = null): Collection
    {
        return $this->listForSelectAction->handle($search);
    }
}
