<?php

namespace Modules\Catalog\Actions\PropertyType;

use Modules\Catalog\Contracts\Repositories\PropertyTypeRepositoryInterface;
use Modules\Catalog\Models\PropertyType;

class DeletePropertyTypeAction
{
    public function __construct(
        private readonly PropertyTypeRepositoryInterface $repository,
    ) {}

    public function handle(PropertyType $propertyType): void
    {
        $this->repository->delete($propertyType);
    }
}
