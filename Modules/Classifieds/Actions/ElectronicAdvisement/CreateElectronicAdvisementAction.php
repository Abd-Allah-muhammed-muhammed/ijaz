<?php

namespace Modules\Classifieds\Actions\ElectronicAdvisement;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\StoreAdvisementMediaAction;
use Modules\Classifieds\Contracts\Repositories\ElectronicAdvisementRepositoryInterface;
use Modules\Classifieds\DTOs\ElectronicAdvisementDTO;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\ElectronicAdvisement;

final class CreateElectronicAdvisementAction
{
    public function __construct(
        private readonly ElectronicAdvisementRepositoryInterface $repository,
        private readonly StoreAdvisementMediaAction $storeAdvisementMediaAction,
    ) {}

    public function handle(User $user, ElectronicAdvisementDTO $dto): ElectronicAdvisement
    {
        return DB::transaction(function () use ($user, $dto): ElectronicAdvisement {
            $electronicAdvisement = $this->repository->create([
                ...$dto->toPersistenceArray(),
                'user_type' => $user::class,
                'user_id' => $user->id,
                'status' => AdvisementStatusEnum::PENDING,
            ]);

            $this->storeAdvisementMediaAction->handle($electronicAdvisement, $dto->files);
            $electronicAdvisement->load([
                'deviceCategory',
                'electronicBrand',
                'city',
                'region',
                'user',
                'media',
            ]);

            return $electronicAdvisement;
        });
    }
}
