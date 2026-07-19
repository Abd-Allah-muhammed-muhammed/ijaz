<?php

namespace App\Contracts\Auth;

use App\Models\User;

interface UserRepositoryInterface
{
    public function findByPhone(string $phone): ?User;

    public function findAuthenticated(): ?User;

    public function create(array $data): User;
}
