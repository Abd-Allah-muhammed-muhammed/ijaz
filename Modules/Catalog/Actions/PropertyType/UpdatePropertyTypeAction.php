<?php

namespace Modules\Catalog\Actions\PropertyType;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\PropertyTypeRepositoryInterface;
use Modules\Catalog\DTOs\UpdatePropertyTypeDTO;
use Modules\Catalog\Models\PropertyType;
use Throwable;

class UpdatePropertyTypeAction
{
    public function __construct(
        private readonly PropertyTypeRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(PropertyType $propertyType, UpdatePropertyTypeDTO $dto): PropertyType
    {
        return DB::transaction(function () use ($propertyType, $dto): PropertyType {
            $data = ['translations' => $dto->translations];

            if ($dto->isActive !== null) {
                $data['is_active'] = $dto->isActive;
            }

            return $this->repository->update($propertyType, $data);
        });
    }
}
