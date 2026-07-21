<?php

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Actions\PropertyType\DeletePropertyTypeAction;
use Modules\Catalog\Actions\PropertyType\ListPropertyTypesAction;
use Modules\Catalog\Actions\PropertyType\ListPropertyTypesForApiAction;
use Modules\Catalog\Actions\PropertyType\ListPropertyTypesForSelectAction;
use Modules\Catalog\Actions\PropertyType\ShowPropertyTypeAction;
use Modules\Catalog\Actions\PropertyType\StorePropertyTypeAction;
use Modules\Catalog\Actions\PropertyType\UpdatePropertyTypeAction;
use Modules\Catalog\Actions\PropertyType\UpdateStatusPropertyTypeAction;
use Modules\Catalog\Contracts\Services\PropertyTypeServiceInterface;
use Modules\Catalog\DTOs\StorePropertyTypeDTO;
use Modules\Catalog\DTOs\UpdatePropertyTypeDTO;
use Modules\Catalog\Models\PropertyType;
use Modules\Catalog\QueryFilters\PropertyType\PropertyTypeFilters;

class PropertyTypeService implements PropertyTypeServiceInterface
{
    public function __construct(
        private readonly ListPropertyTypesAction $listAction,
        private readonly ListPropertyTypesForApiAction $listForApiAction,
        private readonly ListPropertyTypesForSelectAction $listForSelectAction,
        private readonly StorePropertyTypeAction $storeAction,
        private readonly UpdatePropertyTypeAction $updateAction,
        private readonly DeletePropertyTypeAction $deleteAction,
        private readonly UpdateStatusPropertyTypeAction $updateStatusAction,
        private readonly ShowPropertyTypeAction $showAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function paginate(PropertyTypeFilters $filters): LengthAwarePaginator
    {
        return $this->listForApiAction->handle($filters);
    }

    public function store(StorePropertyTypeDTO $dto): PropertyType
    {
        return $this->storeAction->handle($dto);
    }

    public function update(PropertyType $propertyType, UpdatePropertyTypeDTO $dto): PropertyType
    {
        return $this->updateAction->handle($propertyType, $dto);
    }

    public function destroy(PropertyType $propertyType): void
    {
        $this->deleteAction->handle($propertyType);
    }

    public function updateStatus(PropertyType $propertyType, bool $isActive): PropertyType
    {
        return $this->updateStatusAction->handle($propertyType, $isActive);
    }

    public function show(PropertyType $propertyType): PropertyType
    {
        return $this->showAction->handle($propertyType);
    }

    /**
     * @return Collection<int, PropertyType>
     */
    public function listForSelect(?string $search = null): Collection
    {
        return $this->listForSelectAction->handle($search);
    }
}
