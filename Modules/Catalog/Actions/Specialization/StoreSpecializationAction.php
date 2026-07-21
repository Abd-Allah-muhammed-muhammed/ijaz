<?php

namespace Modules\Catalog\Actions\Specialization;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\SpecializationRepositoryInterface;
use Modules\Catalog\DTOs\StoreSpecializationDTO;
use Modules\Catalog\Models\Specialization;
use Throwable;

class StoreSpecializationAction
{
    public function __construct(
        private readonly SpecializationRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreSpecializationDTO $dto): Specialization
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
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }
}
