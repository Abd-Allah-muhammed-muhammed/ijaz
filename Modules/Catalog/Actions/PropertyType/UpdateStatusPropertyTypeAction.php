<?php

namespace Modules\Catalog\Actions\PropertyType;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\PropertyTypeRepositoryInterface;
use Modules\Catalog\Models\PropertyType;
use Throwable;

class UpdateStatusPropertyTypeAction
{
    public function __construct(
        private readonly PropertyTypeRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(PropertyType $propertyType, bool $isActive): PropertyType
    {
        return DB::transaction(
            fn (): PropertyType => $this->repository->update($propertyType, ['is_active' => $isActive])
        );
    }
}
