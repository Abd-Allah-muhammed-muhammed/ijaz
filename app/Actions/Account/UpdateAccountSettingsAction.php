<?php

namespace App\Actions\Account;

use App\Contracts\Account\AccountRepositoryInterface;
use App\DTOs\Account\UpdateAccountSettingsDTO;
use Illuminate\Database\Eloquent\Model;

class UpdateAccountSettingsAction
{
    public function __construct(
        private readonly AccountRepositoryInterface $repository,
    ) {}

    public function handle(Model $user, UpdateAccountSettingsDTO $dto): Model
    {
        return $this->repository->updateSettings($user, $dto->toArray());
    }
}
