<?php

namespace App\Http\Controllers\Dashboard;

use App\DTOs\Auth\UpdateAdminProfileDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Dashboard\Auth\DashboardLoginRequest;
use App\Http\Requests\Dashboard\Auth\UpdateAdminProfileRequest;
use App\Http\Resources\Dashboard\AdminResource;
use App\Models\Admin;
use App\Services\Auth\AdminAuthService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Response;
use Throwable;

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

    public function profile(): Response
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        return inertia('Dashboard/Profile/Index', [
            'admin' => AdminResource::make($this->adminAuthService->profile($admin)),
        ]);
    }

    /**
     * @throws Throwable
     */
    public function updateProfile(UpdateAdminProfileRequest $request): RedirectResponse
    {
        /** @var Admin $admin */
        $admin = auth('admin')->user();

        try {
            $this->adminAuthService->updateProfile(
                $admin,
                UpdateAdminProfileDTO::fromValidated(
                    $request->validated(),
                    $request->file('image'),
                ),
            );

            return to_route('dashboard.profile')->with('success', __('data updated successfully'));
        } catch (Throwable $e) {
            report($e);

            return redirect()->back()->with('error', __('something went wrong'));
        }
    }
}
