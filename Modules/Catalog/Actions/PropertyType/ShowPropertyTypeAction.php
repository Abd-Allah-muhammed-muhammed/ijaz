<?php

namespace Modules\Catalog\Actions\PropertyType;

use Modules\Catalog\Contracts\Repositories\PropertyTypeRepositoryInterface;
use Modules\Catalog\Models\PropertyType;

class ShowPropertyTypeAction
{
    public function __construct(
        private readonly PropertyTypeRepositoryInterface $repository,
    ) {}

    public function handle(PropertyType $propertyType): PropertyType
    {
        return $this->repository->loadForEdit($propertyType);
    }
}
