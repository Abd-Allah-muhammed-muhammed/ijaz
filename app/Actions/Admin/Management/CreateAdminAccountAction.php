<?php

namespace App\Actions\Admin\Management;

use App\Contracts\Admin\AdminManagementRepositoryInterface;
use App\Contracts\Admin\RoleRepositoryInterface;
use App\DTOs\Admin\CreateAdminAccountDTO;
use App\Models\Admin;
use InvalidArgumentException;

final class CreateAdminAccountAction
{
    public function __construct(
        private readonly AdminManagementRepositoryInterface $adminRepository,
        private readonly RoleRepositoryInterface $roleRepository,
    ) {}

    public function handle(CreateAdminAccountDTO $dto): Admin
    {
        if ($this->adminRepository->existsByEmail($dto->email)) {
            throw new InvalidArgumentException('An admin with this email already exists.');
        }

        if ($this->adminRepository->existsByPhone($dto->phone)) {
            throw new InvalidArgumentException('An admin with this phone already exists.');
        }

        if (! $dto->isRoot) {
            if ($dto->roleName === null || $dto->roleName === '') {
                throw new InvalidArgumentException('A role is required when creating a non-root admin.');
            }

            if (! $this->roleRepository->adminGuardRoleExists($dto->roleName)) {
                throw new InvalidArgumentException(
                    "Admin role [{$dto->roleName}] was not found. Run: php artisan db:seed --class=Database\\Seeders\\RolePermissionSeeder"
                );
            }
        }

        $admin = $this->adminRepository->create([
            'name' => $dto->name,
            'email' => $dto->email,
            'phone' => $dto->phone,
            'password' => $dto->password,
            'email_verified_at' => now(),
            'address' => $dto->isRoot ? 'Root Address' : null,
            'job' => $dto->isRoot ? 'Root' : null,
        ]);

        if ($dto->isRoot) {
            $this->adminRepository->markAsRoot($admin);

            if ($this->roleRepository->adminGuardRoleExists('super-admin')) {
                $this->adminRepository->assignRoleByName($admin, 'super-admin');
            }
        } else {
            $this->adminRepository->assignRoleByName($admin, $dto->roleName);
        }

        return $admin->fresh(['roles']) ?? $admin;
    }
}
