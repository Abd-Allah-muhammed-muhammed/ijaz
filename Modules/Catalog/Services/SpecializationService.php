<?php

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Modules\Catalog\Actions\Specialization\DeleteSpecializationAction;
use Modules\Catalog\Actions\Specialization\FindSpecializationAction;
use Modules\Catalog\Actions\Specialization\ListAllSpecializationsAction;
use Modules\Catalog\Actions\Specialization\ListRootSpecializationsAction;
use Modules\Catalog\Actions\Specialization\ListSpecializationsAction;
use Modules\Catalog\Actions\Specialization\ListSpecializationsForSelectAction;
use Modules\Catalog\Actions\Specialization\ShowSpecializationAction;
use Modules\Catalog\Actions\Specialization\StoreSpecializationAction;
use Modules\Catalog\Actions\Specialization\UpdateSpecializationAction;
use Modules\Catalog\Contracts\Services\SpecializationServiceInterface;
use Modules\Catalog\DTOs\StoreSpecializationDTO;
use Modules\Catalog\DTOs\UpdateSpecializationDTO;
use Modules\Catalog\Models\Specialization;

class SpecializationService implements SpecializationServiceInterface
{
    public function __construct(
        private readonly ListSpecializationsAction $listAction,
        private readonly ListAllSpecializationsAction $listAllAction,
        private readonly ListSpecializationsForSelectAction $listForSelectAction,
        private readonly StoreSpecializationAction $storeAction,
        private readonly UpdateSpecializationAction $updateAction,
        private readonly DeleteSpecializationAction $deleteAction,
        private readonly ShowSpecializationAction $showAction,
        private readonly FindSpecializationAction $findAction,
        private readonly ListRootSpecializationsAction $listRootAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function getAll(Request $request): Collection
    {
        return $this->listAllAction->handle($request);
    }

    public function store(StoreSpecializationDTO $dto): Specialization
    {
        return $this->storeAction->handle($dto);
    }

    public function update(Specialization $specialization, UpdateSpecializationDTO $dto): Specialization
    {
        return $this->updateAction->handle($specialization, $dto);
    }

    public function destroy(Specialization $specialization): void
    {
        $this->deleteAction->handle($specialization);
    }

    public function show(Specialization $specialization): Specialization
    {
        return $this->showAction->handle($specialization);
    }

    public function findById(int $id): ?Specialization
    {
        return $this->findAction->handle($id);
    }

    /**
     * @return Collection<int, Specialization>
     */
    public function getRootSpecializations(?int $excludeId = null): Collection
    {
        return $this->listRootAction->handle($excludeId);
    }

    /**
     * @return Collection<int, Specialization>
     */
    public function listForSelect(?string $search = null, int $parentId = 0): Collection
    {
        return $this->listForSelectAction->handle($search, $parentId);
    }
}
