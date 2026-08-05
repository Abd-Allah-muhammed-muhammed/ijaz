<?php

namespace Modules\Catalog\Actions\PropertyType;

use App\Support\LookupCache;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\PropertyTypeRepositoryInterface;
use Modules\Catalog\DTOs\StorePropertyTypeDTO;
use Modules\Catalog\Models\PropertyType;
use Throwable;

class StorePropertyTypeAction
{
    public function __construct(
        private readonly PropertyTypeRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StorePropertyTypeDTO $dto): PropertyType
    {
        $propertyType = DB::transaction(fn (): PropertyType => $this->repository->create([
            'translations' => $dto->translations,
            'is_active' => $dto->isActive,
        ]));

        LookupCache::forgetAllLocales('property-types:all');

        return $propertyType;
    }
}
