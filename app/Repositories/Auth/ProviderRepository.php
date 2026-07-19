<?php

namespace App\Repositories\Auth;

use App\Contracts\Auth\ProviderRepositoryInterface;
use App\Models\Provider;
use Illuminate\Support\Facades\Auth;

class ProviderRepository implements ProviderRepositoryInterface
{
    public function findAuthenticated(): ?Provider
    {
        return Auth::guard('provider')->user();
    }

    public function create(array $data): Provider
    {
        return Provider::create($data);
    }
}
