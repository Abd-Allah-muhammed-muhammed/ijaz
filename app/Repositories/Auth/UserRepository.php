<?php

namespace App\Repositories\Auth;

use App\Contracts\Auth\UserRepositoryInterface;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

class UserRepository implements UserRepositoryInterface
{
    public function findByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }

    public function findAuthenticated(): ?User
    {
        return Auth::guard('user-api')->user() ?? auth()->user();
    }

    public function create(array $data): User
    {
        return User::create($data);
    }
}
