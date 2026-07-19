<?php

namespace App\Services\Auth;

use App\Actions\Auth\Admin\LoginAdminAction;
use App\DTOs\Auth\LoginResult;
use App\Http\Requests\Dashboard\Auth\DashboardLoginRequest;
use Illuminate\Http\Request;

class AdminAuthService
{
    public function __construct(
        private readonly LoginAdminAction $loginAdminAction,
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
}
