<?php

namespace App\Contracts\Auth;

use App\Models\Provider;

interface ProviderRepositoryInterface
{
    public function findAuthenticated(): ?Provider;

    public function create(array $data): Provider;
}
