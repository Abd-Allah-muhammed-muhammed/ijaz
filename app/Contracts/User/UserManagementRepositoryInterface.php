<?php

namespace App\Contracts\User;

use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Collection as SupportCollection;

interface UserManagementRepositoryInterface
{
    public function paginate(Request $request): LengthAwarePaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User;

    public function delete(User $user): void;

    public function loadForShow(User $user): User;

    public function loadForEdit(User $user): User;

    public function saveStatus(User $user, string $status): User;

    public function block(User $user, int $blockDays, ?string $reason): void;

    public function revokeTokens(User $user): void;

    public function countAll(): int;

    /**
     * @return array{total: int, active: int, blocked: int}
     */
    public function statusCounts(): array;

    /**
     * @return SupportCollection<string, int>
     */
    public function registrationCountsSince(CarbonInterface $since): SupportCollection;

    /**
     * @return Collection<int, User>
     */
    public function latestForDashboard(int $limit = 4): Collection;
}
