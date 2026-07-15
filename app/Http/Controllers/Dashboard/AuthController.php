<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Auth\DashboardLoginRequest;
use App\Services\Auth\AdminAuthService;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly AdminAuthService $adminAuthService,
    ) {}

    public function loginForm()
    {
        return inertia('Dashboard/Auth/LoginPage');
    }

    public function login(DashboardLoginRequest $request)
    {
        $result = $this->adminAuthService->login($request);

        return redirect()->intended(route($result->redirectRouteName, absolute: false));
    }

    public function logout(Request $request)
    {
        $this->adminAuthService->logout($request);

        return redirect('/');
    }
}
