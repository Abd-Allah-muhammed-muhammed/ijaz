<?php

namespace App\Actions\User;

use App\Contracts\User\UserManagementRepositoryInterface;
use App\DTOs\User\StoreUserDTO;
use App\Models\User;
use App\Support\Phone;

class StoreUserAction
{
    public function __construct(
        private readonly UserManagementRepositoryInterface $repository,
    ) {}

    public function handle(StoreUserDTO $dto): User
    {
        return $this->repository->create([
            'f_name' => $dto->f_name,
            'l_name' => $dto->l_name,
            'email' => $dto->email,
            'password' => $dto->password,
            'phone' => Phone::make($dto->phone)->toString(),
            'nationality_id' => $dto->nationality_id,
            'image' => $dto->image?->store('users', 'public'),
        ]);
    }
}
