<?php

namespace Modules\Geo\Actions\Nationality;

use Modules\Geo\Contracts\Repositories\NationalityRepositoryInterface;
use Modules\Geo\Exceptions\GeoException;
use Modules\Geo\Models\Nationality;

class DeleteNationalityAction
{
    public function __construct(
        private readonly NationalityRepositoryInterface $repository,
    ) {}

    /**
     * @throws GeoException
     */
    public function handle(Nationality $nationality): void
    {
        $this->repository->delete($nationality);
    }
}
