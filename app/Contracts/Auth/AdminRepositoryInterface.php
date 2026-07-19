<?php

namespace App\Contracts\Auth;

use App\Models\Admin;

interface AdminRepositoryInterface
{
    public function findAuthenticated(): ?Admin;
}
