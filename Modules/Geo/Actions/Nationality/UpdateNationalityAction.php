<?php

namespace Modules\Geo\Actions\Nationality;

use App\Support\LookupCache;
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
        $nationality = DB::transaction(
            fn (): Nationality => $this->repository->update($nationality, $dto->translations)
        );

        LookupCache::forgetAllLocales('nationalities:all');

        return $nationality;
    }
}
