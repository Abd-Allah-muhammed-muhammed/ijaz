<?php

namespace Modules\Classifieds\Actions\PropertyAdvisement;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\StoreAdvisementMediaAction;
use Modules\Classifieds\Contracts\Repositories\PropertyAdvisementRepositoryInterface;
use Modules\Classifieds\DTOs\PropertyAdvisementDTO;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\PropertyAdvisement;

final class CreatePropertyAdvisementAction
{
    public function __construct(
        private readonly PropertyAdvisementRepositoryInterface $repository,
        private readonly StoreAdvisementMediaAction $storeAdvisementMediaAction,
    ) {}

    public function handle(User $user, PropertyAdvisementDTO $dto): PropertyAdvisement
    {
        return DB::transaction(function () use ($user, $dto): PropertyAdvisement {
            $propertyAdvisement = $this->repository->create([
                ...$dto->toPersistenceArray(),
                'user_type' => $user::class,
                'user_id' => $user->id,
                'status' => AdvisementStatusEnum::PENDING,
            ]);

            $this->storeAdvisementMediaAction->handle($propertyAdvisement, $dto->files);
            // Keep `.translation` — CityResource/RegionResource only expose title when
            // translation is loaded; PropertyType/Category are Astrotomic Translatable.
            $propertyAdvisement->load([
                'propertyType.translation',
                'city.translation',
                'region.translation',
                'category.translation',
                'user',
                'media',
            ]);

            return $propertyAdvisement;
        });
    }
}
