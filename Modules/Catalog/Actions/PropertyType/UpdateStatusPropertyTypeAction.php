<?php

namespace Modules\Catalog\Actions\PropertyType;

use App\Support\LookupCache;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\Repositories\PropertyTypeRepositoryInterface;
use Modules\Catalog\Models\PropertyType;
use Throwable;

class UpdateStatusPropertyTypeAction
{
    public function __construct(
        private readonly PropertyTypeRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(PropertyType $propertyType, bool $isActive): PropertyType
    {
        $propertyType = DB::transaction(
            fn (): PropertyType => $this->repository->update($propertyType, ['is_active' => $isActive])
        );

        LookupCache::forgetAllLocales('property-types:all');

        return $propertyType;
    }
}
