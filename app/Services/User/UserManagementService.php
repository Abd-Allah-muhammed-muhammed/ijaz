<?php

namespace App\Services\User;

use App\Actions\User\DeleteUserAction;
use App\Actions\User\EditUserAction;
use App\Actions\User\ListUsersAction;
use App\Actions\User\ListUserWalletTransactionsAction;
use App\Actions\User\ShowUserAction;
use App\Actions\User\StoreUserAction;
use App\Actions\User\UpdateUserAction;
use App\Actions\User\UpdateUserStatusAction;
use App\DTOs\User\StoreUserDTO;
use App\DTOs\User\UpdateUserDTO;
use App\DTOs\User\UpdateUserStatusDTO;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Modules\Geo\Models\Nationality;
use Modules\Geo\Services\NationalityService;

class UserManagementService
{
    public function __construct(
        private readonly ListUsersAction $listAction,
        private readonly ShowUserAction $showAction,
        private readonly EditUserAction $editAction,
        private readonly StoreUserAction $storeAction,
        private readonly UpdateUserAction $updateAction,
        private readonly DeleteUserAction $deleteAction,
        private readonly UpdateUserStatusAction $updateStatusAction,
        private readonly ListUserWalletTransactionsAction $walletTransactionsAction,
        private readonly NationalityService $nationalityService,
    ) {}

    public function index(Request $request): LengthAwarePaginator
    {
        return $this->listAction->handle($request);
    }

    public function show(User $user): User
    {
        return $this->showAction->handle($user);
    }

    public function edit(User $user): User
    {
        return $this->editAction->handle($user);
    }

    public function store(StoreUserDTO $dto): User
    {
        return DB::transaction(fn (): User => $this->storeAction->handle($dto));
    }

    public function update(User $user, UpdateUserDTO $dto): User
    {
        return DB::transaction(fn (): User => $this->updateAction->handle($user, $dto));
    }

    public function destroy(User $user): void
    {
        DB::transaction(fn () => $this->deleteAction->handle($user));
    }

    public function updateStatus(User $user, UpdateUserStatusDTO $dto): User
    {
        return DB::transaction(fn (): User => $this->updateStatusAction->handle($user, $dto));
    }

    public function listWalletTransactions(User $user, ?string $search = null, int $perPage = 25): LengthAwarePaginator
    {
        return $this->walletTransactionsAction->handle($user, $search, $perPage);
    }

    /**
     * Shared Nationality dropdown for the User create/edit forms, served by Geo.
     *
     * @return Collection<int, Nationality>
     */
    public function getNationalitiesForDropdown(): Collection
    {
        return $this->nationalityService->listForSelect();
    }
}
