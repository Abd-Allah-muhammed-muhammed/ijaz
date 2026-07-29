<?php

namespace App\Actions\User;

use App\Contracts\User\UserManagementRepositoryInterface;
use App\DTOs\User\StoreUserDTO;
use App\Models\User;
use App\Support\HandlesTransactionalFileUpload;
use App\Support\Phone;
use Throwable;

class StoreUserAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly UserManagementRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreUserDTO $dto): User
    {
        return $this->storeFileWithCleanup(
            file: $dto->image,
            directory: 'users',
            disk: 'public',
            dbWork: fn (?string $imagePath): User => $this->repository->create([
                'f_name' => $dto->f_name,
                'l_name' => $dto->l_name,
                'email' => $dto->email,
                'password' => $dto->password,
                'phone' => Phone::make($dto->phone)->toString(),
                'nationality_id' => $dto->nationality_id,
                'image' => $imagePath,
            ]),
        );
    }
}
