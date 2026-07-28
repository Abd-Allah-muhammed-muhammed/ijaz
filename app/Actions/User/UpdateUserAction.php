<?php

namespace App\Actions\User;

use App\Contracts\User\UserManagementRepositoryInterface;
use App\DTOs\User\UpdateUserDTO;
use App\Models\User;
use App\Support\HandlesTransactionalFileUpload;
use App\Support\Phone;
use Throwable;

class UpdateUserAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly UserManagementRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(User $user, UpdateUserDTO $dto): User
    {
        return $this->storeFileWithCleanup(
            file: $dto->image,
            directory: 'users',
            disk: 'public',
            previousPath: $dto->image !== null ? $user->image : null,
            dbWork: function (?string $imagePath) use ($user, $dto): User {
                $data = [
                    'f_name' => $dto->f_name,
                    'l_name' => $dto->l_name,
                    'email' => $dto->email,
                    'phone' => Phone::make($dto->phone)->toString(),
                    'nationality_id' => $dto->nationality_id,
                ];

                if ($imagePath !== null) {
                    $data['image'] = $imagePath;
                }

                if (filled($dto->password)) {
                    $data['password'] = $dto->password;
                }

                return $this->repository->update($user, $data);
            },
        );
    }
}
