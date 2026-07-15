<?php

namespace App\Actions\Auth\Admin;

use App\Contracts\Auth\AdminRepositoryInterface;
use App\DTOs\Auth\LoginResult;
use Illuminate\Http\Request;

class LoginAdminAction
{
    public function __construct(
        private readonly AdminRepositoryInterface $adminRepository,
    ) {}

    /**
     * Runs AFTER DashboardLoginRequest::authenticate() has already succeeded
     * (credentials verified, rate limiter cleared). This action only handles
     * what comes next: session regeneration and determining the redirect target.
     */
    public function handle(Request $request): LoginResult
    {
        $request->session()->regenerate();

        return new LoginResult(redirectRouteName: 'dashboard.home');
    }
}
