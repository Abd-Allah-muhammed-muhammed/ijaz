<?php

namespace App\Services\Admin;

use App\Actions\Admin\Management\CreateAdminAccountAction;
use App\Actions\Admin\Management\DeleteAdminAction;
use App\Actions\Admin\Management\ListAdminsAction;
use App\Actions\Admin\Management\ShowAdminAction;
use App\Actions\Admin\Management\StoreAdminAction;
use App\Actions\Admin\Management\UpdateAdminAction;
use App\Contracts\Admin\AdminManagementRepositoryInterface;
use App\DTOs\Admin\CreateAdminAccountDTO;
use App\DTOs\Admin\StoreAdminDTO;
use App\DTOs\Admin\UpdateAdminDTO;
use App\Models\Admin;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Spatie\Permission\Models\Role;

class AdminManagementService
{
    public function __construct(
        private readonly ListAdminsAction $listAction,
        private readonly StoreAdminAction $storeAction,
        private readonly UpdateAdminAction $updateAction,
        private readonly DeleteAdminAction $deleteAction,
        private readonly ShowAdminAction $showAction,
        private readonly CreateAdminAccountAction $createAdminAccountAction,
        private readonly AdminManagementRepositoryInterface $adminRepository,
        private readonly RoleService $roleService,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function store(StoreAdminDTO $dto): Admin
    {
        return $this->storeAction->handle($dto);
    }

    public function createAccount(CreateAdminAccountDTO $dto): Admin
    {
        return $this->createAdminAccountAction->handle($dto);
    }

    public function emailExists(string $email): bool
    {
        return $this->adminRepository->existsByEmail($email);
    }

    public function phoneExists(string $phone): bool
    {
        return $this->adminRepository->existsByPhone($phone);
    }

    public function update(Admin $admin, UpdateAdminDTO $dto): Admin
    {
        return $this->updateAction->handle($admin, $dto);
    }

    public function destroy(Admin $admin): void
    {
        $this->deleteAction->handle($admin);
    }

    public function show(Admin $admin): Admin
    {
        return $this->showAction->handle($admin);
    }

    /**
     * @return Collection<int, Role>
     */
    public function getRolesForDropdown(): Collection
    {
        return $this->roleService->getAllForDropdown();
    }
}
