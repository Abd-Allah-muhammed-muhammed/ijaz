<?php

namespace App\Repositories\Auth;

use App\Contracts\Auth\AdminRepositoryInterface;
use App\Models\Admin;
use Illuminate\Support\Facades\Auth;

class AdminRepository implements AdminRepositoryInterface
{
    public function findAuthenticated(): ?Admin
    {
        return Auth::guard('admin')->user();
    }
}
