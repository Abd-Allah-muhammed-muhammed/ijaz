<?php

namespace App\Actions\Auth\Admin;

use App\Contracts\Admin\AdminManagementRepositoryInterface;
use App\DTOs\Auth\UpdateAdminProfileDTO;
use App\Models\Admin;
use App\Support\HandlesTransactionalFileUpload;
use Throwable;

class UpdateAdminProfileAction
{
    use HandlesTransactionalFileUpload;

    public function __construct(
        private readonly AdminManagementRepositoryInterface $repository,
    ) {}

    /**
     * @throws Throwable
     */
    public function handle(Admin $admin, UpdateAdminProfileDTO $dto): Admin
    {
        return $this->storeFileWithCleanup(
            file: $dto->image,
            directory: 'admins',
            disk: 'public',
            previousPath: $dto->image !== null ? $admin->image : null,
            dbWork: function (?string $imagePath) use ($admin, $dto): Admin {
                $data = [
                    'name' => $dto->name,
                    'phone' => $dto->phone,
                    'email' => $dto->email,
                    'address' => $dto->address,
                    'job' => $dto->job,
                ];

                if ($imagePath !== null) {
                    $data['image'] = $imagePath;
                }

                if (filled($dto->password)) {
                    $data['password'] = $dto->password;
                }

                return $this->repository->update($admin, $data);
            },
        );
    }
}
