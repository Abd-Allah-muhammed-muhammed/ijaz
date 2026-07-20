<?php

namespace Modules\Geo\Actions\Nationality;

use Illuminate\Support\Facades\DB;
use Modules\Geo\Contracts\Repositories\NationalityRepositoryInterface;
use Modules\Geo\DTOs\UpdateNationalityDTO;
use Modules\Geo\Models\Nationality;
use Throwable;

class UpdateNationalityAction
{
    public function __construct(
        private readonly NationalityRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Nationality $nationality, UpdateNationalityDTO $dto): Nationality
    {
        return DB::transaction(
            fn (): Nationality => $this->repository->update($nationality, $dto->translations)
        );
    }
}
