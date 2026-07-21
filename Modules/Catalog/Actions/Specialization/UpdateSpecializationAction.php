<?php

namespace Modules\Catalog\Actions\Specialization;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\SpecializationRepositoryInterface;
use Modules\Catalog\DTOs\UpdateSpecializationDTO;
use Modules\Catalog\Models\Specialization;
use Throwable;

class UpdateSpecializationAction
{
    public function __construct(
        private readonly SpecializationRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Specialization $specialization, UpdateSpecializationDTO $dto): Specialization
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
        } catch (Throwable $throwable) {
            DB::rollBack();
            report($throwable);
            throw $throwable;
        }
    }
}
