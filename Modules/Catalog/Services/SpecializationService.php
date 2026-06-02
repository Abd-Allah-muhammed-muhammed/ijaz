<?php

namespace Modules\Catalog\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\SpecializationRepositoryInterface;
use Modules\Catalog\Contracts\Services\SpecializationServiceInterface;
use Modules\Catalog\DTOs\StoreSpecializationDTO;
use Modules\Catalog\DTOs\UpdateSpecializationDTO;
use Modules\Catalog\Models\Specialization;

class SpecializationService implements SpecializationServiceInterface
{
    public function __construct(
        private readonly SpecializationRepositoryInterface $repository,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->repository->paginate($request);
    }

    public function getAll(Request $request): Collection
    {
        return $this->repository->getAll($request);
    }

    public function store(StoreSpecializationDTO $dto): Specialization
    {
        DB::beginTransaction();
        try {
            $data = [
                'parent_id' => $dto->parentId,
                'icon' => $dto->icon,
            ];

            $specialization = $this->repository->create($data);
            $specialization->translations()->createMany($dto->translations);

            DB::commit();

            return $specialization->load(['translation']);
        } catch (\Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }

    public function update(Specialization $specialization, UpdateSpecializationDTO $dto): Specialization
    {
        DB::beginTransaction();
        try {
            $data = [
                'parent_id' => $dto->parentId,
            ];

            if ($dto->icon) {
                $specialization->deleteIcon();
                $data['icon'] = $dto->icon;
            }

            $specialization = $this->repository->update($specialization, $data);
            $specialization->translations()->delete();
            $specialization->translations()->createMany($dto->translations);

            DB::commit();

            return $specialization->load(['translation']);
        } catch (\Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }

    public function destroy(Specialization $specialization): void
    {
        $this->repository->delete($specialization);
    }

    public function show(Specialization $specialization): Specialization
    {
        return $specialization
            ->loadCount('children')
            ->load([
                'translation',
                'children.translation',
            ]);
    }

    /**
     * @return Collection<int, Specialization>
     */
    public function getRootSpecializations(?int $excludeId = null): Collection
    {
        return $this->repository->getRootSpecializations($excludeId);
    }
}
