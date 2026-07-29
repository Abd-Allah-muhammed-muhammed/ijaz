<?php

namespace Modules\Catalog\Actions\CarType;

use Modules\Catalog\Contracts\Repositories\CarTypeRepositoryInterface;
use Modules\Catalog\Models\CarType;

class DeleteCarTypeAction
{
    public function __construct(
        private readonly CarTypeRepositoryInterface $repository,
    ) {}

    public function handle(CarType $carType): void
    {
        $this->repository->delete($carType);
    }
}
