<?php

namespace App\Services\Auth;

use App\Actions\Auth\Admin\LoginAdminAction;
use App\Actions\Auth\Admin\UpdateAdminProfileAction;
use App\DTOs\Auth\LoginResult;
use App\DTOs\Auth\UpdateAdminProfileDTO;
use App\Http\Requests\Dashboard\Auth\DashboardLoginRequest;
use App\Models\Admin;
use Illuminate\Http\Request;
use Throwable;

class AdminAuthService
{
    public function __construct(
        private readonly LoginAdminAction $loginAdminAction,
        private readonly UpdateAdminProfileAction $updateAdminProfileAction,
    ) {}

    public function login(DashboardLoginRequest $request): LoginResult
    {
        $request->authenticate();

        return $this->loginAdminAction->handle($request);
    }

    public function logout(Request $request): void
    {
        auth('admin')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
    }

    public function profile(Admin $admin): Admin
    {
        return $admin;
    }

    /**
     * @throws Throwable
     */
    public function updateProfile(Admin $admin, UpdateAdminProfileDTO $dto): Admin
    {
        return $this->updateAdminProfileAction->handle($admin, $dto);
    }
}
