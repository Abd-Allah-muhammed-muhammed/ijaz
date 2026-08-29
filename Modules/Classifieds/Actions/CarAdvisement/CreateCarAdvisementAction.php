<?php

namespace Modules\Classifieds\Actions\CarAdvisement;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\StoreAdvisementMediaAction;
use Modules\Classifieds\Contracts\Repositories\CarAdvisementRepositoryInterface;
use Modules\Classifieds\DTOs\CarAdvisementDTO;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\CarAdvisement;

final class CreateCarAdvisementAction
{
    public function __construct(
        private readonly CarAdvisementRepositoryInterface $repository,
        private readonly StoreAdvisementMediaAction $storeAdvisementMediaAction,
    ) {}

    public function handle(User $user, CarAdvisementDTO $dto): CarAdvisement
    {
        return DB::transaction(function () use ($user, $dto): CarAdvisement {
            $carAdvisement = $this->repository->create([
                ...$dto->toPersistenceArray(),
                'user_type' => $user::class,
                'user_id' => $user->id,
                'status' => AdvisementStatusEnum::PENDING,
            ]);

            $this->storeAdvisementMediaAction->handle($carAdvisement, $dto->files);
            $carAdvisement->load([
                'carBrand',
                'carType',
                'carCategory',
                'city',
                'region',
                'bank.translations',
                'bank.media',
                'user',
                'media',
            ]);

            return $carAdvisement;
        });
    }
}
