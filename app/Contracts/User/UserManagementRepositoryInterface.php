<?php

namespace App\Contracts\User;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

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
}
