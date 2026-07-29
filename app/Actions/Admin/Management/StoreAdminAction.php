<?php

namespace App\Actions\Admin\Management;

use App\Contracts\Admin\AdminManagementRepositoryInterface;
use App\DTOs\Admin\StoreAdminDTO;
use App\Models\Admin;
use App\Support\HandlesTransactionalFileUpload;
use Throwable;

class StoreAdminAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly AdminManagementRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(StoreAdminDTO $dto): Admin
    {
        return $this->storeFileWithCleanup(
            file: $dto->image,
            directory: 'admins',
            disk: 'public',
            dbWork: function (?string $imagePath) use ($dto): Admin {
                $admin = $this->repository->create([
                    'name' => $dto->name,
                    'phone' => $dto->phone,
                    'email' => $dto->email,
                    'password' => $dto->password,
                    'address' => $dto->address,
                    'job' => $dto->job,
                    'image' => $imagePath,
                ]);

                $this->repository->attachRoles($admin, $dto->roles);

                return $admin;
            },
        );
    }
}
