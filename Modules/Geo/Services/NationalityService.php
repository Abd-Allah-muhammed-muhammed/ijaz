<?php

namespace Modules\Geo\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Modules\Geo\Actions\Nationality\DeleteNationalityAction;
use Modules\Geo\Actions\Nationality\ListNationalitiesAction;
use Modules\Geo\Actions\Nationality\StoreNationalityAction;
use Modules\Geo\Actions\Nationality\UpdateNationalityAction;
use Modules\Geo\DTOs\StoreNationalityDTO;
use Modules\Geo\DTOs\UpdateNationalityDTO;
use Modules\Geo\Exceptions\GeoException;
use Modules\Geo\Models\Nationality;

class NationalityService
{
    public function __construct(
        private readonly ListNationalitiesAction $listAction,
        private readonly StoreNationalityAction $storeAction,
        private readonly UpdateNationalityAction $updateAction,
        private readonly DeleteNationalityAction $deleteAction,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function store(StoreNationalityDTO $dto): Nationality
    {
        return $this->storeAction->handle($dto);
    }

    public function update(Nationality $nationality, UpdateNationalityDTO $dto): Nationality
    {
        return $this->updateAction->handle($nationality, $dto);
    }

    /**
     * @throws GeoException
     */
    public function destroy(Nationality $nationality): void
    {
        $this->deleteAction->handle($nationality);
    }
}
