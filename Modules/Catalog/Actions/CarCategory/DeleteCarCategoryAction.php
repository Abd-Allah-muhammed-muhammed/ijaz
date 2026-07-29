<?php

namespace Modules\Catalog\Actions\CarCategory;

use Modules\Catalog\Contracts\Repositories\CarCategoryRepositoryInterface;
use Modules\Catalog\Models\CarCategory;

class DeleteCarCategoryAction
{
    public function __construct(
        private readonly CarCategoryRepositoryInterface $repository,
    ) {}

    public function handle(CarCategory $carCategory): void
    {
        $this->repository->delete($carCategory);
    }
}
