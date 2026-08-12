<?php

namespace App\Services\Auth;

use App\Actions\Auth\Admin\LoginAdminAction;
use App\Actions\Auth\Admin\UpdateAdminProfileAction;
use App\Actions\DeviceToken\ClearDeviceTokenByTokenAction;
use App\DTOs\Auth\LoginResult;
use App\DTOs\Auth\UpdateAdminProfileDTO;
use App\Http\Requests\Dashboard\Auth\DashboardLoginRequest;
use App\Models\Admin;
use App\Services\Admin\AdminDeviceTokenService;
use Illuminate\Http\Request;
use Throwable;

class AdminAuthService
{
    public function __construct(
        private readonly LoginAdminAction $loginAdminAction,
        private readonly UpdateAdminProfileAction $updateAdminProfileAction,
        private readonly ClearDeviceTokenByTokenAction $clearDeviceTokenByTokenAction,
    ) {}

    public function login(DashboardLoginRequest $request): LoginResult
    {
        $request->authenticate();

        return $this->loginAdminAction->handle($request);
    }

    public function logout(Request $request): void
    {
        $admin = auth('admin')->user();
        $webToken = $request->session()->get(AdminDeviceTokenService::SESSION_WEB_FCM_TOKEN_KEY);

        if ($admin instanceof Admin && is_string($webToken) && trim($webToken) !== '') {
            $this->clearDeviceTokenByTokenAction->handle($admin, $webToken);
        }

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
