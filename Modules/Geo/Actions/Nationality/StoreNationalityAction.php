<?php

namespace Modules\Geo\Actions\Nationality;

use Illuminate\Support\Facades\DB;
use Modules\Geo\Contracts\Repositories\NationalityRepositoryInterface;
use Modules\Geo\DTOs\StoreNationalityDTO;
use Modules\Geo\Models\Nationality;
use Throwable;

class StoreNationalityAction
{
    public function __construct(
        private readonly NationalityRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreNationalityDTO $dto): Nationality
    {
        return DB::transaction(
            fn (): Nationality => $this->repository->create($dto->translations)
        );
    }
}
