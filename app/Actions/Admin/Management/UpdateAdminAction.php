<?php

namespace App\Actions\Admin\Management;

use App\Contracts\Admin\AdminManagementRepositoryInterface;
use App\DTOs\Admin\UpdateAdminDTO;
use App\Models\Admin;

class UpdateAdminAction
{
    public function __construct(
        private readonly AdminManagementRepositoryInterface $repository,
    ) {}

    public function handle(Admin $admin, UpdateAdminDTO $dto): Admin
    {
        $data = [
            'name' => $dto->name,
            'phone' => $dto->phone,
            'email' => $dto->email,
            'address' => $dto->address,
            'job' => $dto->job,
        ];

        if ($dto->image !== null) {
            $admin->deleteImage();
            $data['image'] = $dto->image->store('admins', 'public');
        }

        if (filled($dto->password)) {
            $data['password'] = $dto->password;
        }

        $admin = $this->repository->update($admin, $data);
        $this->repository->syncRoles($admin, $dto->roles);

        return $admin;
    }
}
