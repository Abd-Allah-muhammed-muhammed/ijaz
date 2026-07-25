<?php

namespace App\Actions\User;

use App\Contracts\User\UserManagementRepositoryInterface;
use App\DTOs\User\UpdateUserDTO;
use App\Models\User;
use App\Support\Phone;

class UpdateUserAction
{
    public function __construct(
        private readonly UserManagementRepositoryInterface $repository,
    ) {}

    public function handle(User $user, UpdateUserDTO $dto): User
    {
        $data = [
            'f_name' => $dto->f_name,
            'l_name' => $dto->l_name,
            'email' => $dto->email,
            'phone' => Phone::make($dto->phone)->toString(),
            'nationality_id' => $dto->nationality_id,
        ];

        if ($dto->image !== null) {
            $data['image'] = $dto->image->store('users', 'public');
            $user->deleteImage();
        }

        if (filled($dto->password)) {
            $data['password'] = $dto->password;
        }

        return $this->repository->update($user, $data);
    }
}
