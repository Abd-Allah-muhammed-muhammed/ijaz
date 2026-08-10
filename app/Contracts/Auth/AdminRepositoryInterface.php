<?php

namespace App\Contracts\Auth;

use App\Models\Admin;
use Illuminate\Support\Collection;

interface AdminRepositoryInterface
{
    public function findAuthenticated(): ?Admin;

    /**
     * Admins that hold the given Spatie permission (guard: admin).
     *
     * @return Collection<int, Admin>
     */
    public function getWithPermission(string $permission): Collection;
}
