<?php

namespace Modules\Geo\Actions\Nationality;

use Illuminate\Database\Eloquent\Collection;
use Modules\Geo\Contracts\Repositories\NationalityRepositoryInterface;
use Modules\Geo\Models\Nationality;

class ListNationalitiesForSelectAction
{
    public function __construct(
        private readonly NationalityRepositoryInterface $repository,
    ) {}

    /**
     * @return Collection<int, Nationality>
     */
    public function handle(?string $search = null): Collection
    {
        return $this->repository->listForSelect($search);
    }
}
