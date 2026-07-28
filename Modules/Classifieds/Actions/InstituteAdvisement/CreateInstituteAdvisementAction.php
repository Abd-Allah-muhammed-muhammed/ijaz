<?php

namespace Modules\Classifieds\Actions\InstituteAdvisement;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Modules\Classifieds\Actions\StoreAdvisementMediaAction;
use Modules\Classifieds\Contracts\Repositories\InstituteAdvisementRepositoryInterface;
use Modules\Classifieds\DTOs\InstituteAdvisementDTO;
use Modules\Classifieds\Enums\AdvisementStatusEnum;
use Modules\Classifieds\Models\InstituteAdvisement;

final class CreateInstituteAdvisementAction
{
    public function __construct(
        private readonly InstituteAdvisementRepositoryInterface $repository,
        private readonly StoreAdvisementMediaAction $storeAdvisementMediaAction,
    ) {}

    public function handle(User $user, InstituteAdvisementDTO $dto): InstituteAdvisement
    {
        return DB::transaction(function () use ($user, $dto): InstituteAdvisement {
            $instituteAdvisement = $this->repository->create([
                ...$dto->toPersistenceArray(),
                'user_type' => $user::class,
                'user_id' => $user->id,
                'status' => AdvisementStatusEnum::PENDING,
            ]);

            $this->storeAdvisementMediaAction->handle($instituteAdvisement, $dto->files);
            $instituteAdvisement->load([
                'specialization',
                'city',
                'region',
                'user',
                'media',
            ]);

            return $instituteAdvisement;
        });
    }
}
