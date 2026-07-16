<?php

namespace App\Actions\Auth\Provider;

use App\Contracts\Auth\ProviderRepositoryInterface;
use App\DTOs\Auth\ProviderLoginResult;
use Illuminate\Http\Request;

class LoginProviderAction
{
    public function __construct(
        private readonly ProviderRepositoryInterface $providerRepository,
    ) {}

    /**
     * Runs AFTER Provider\Auth\LoginRequest::authenticate() has already
     * succeeded (rate limiter cleared, Approved-status gate passed).
     */
    public function handle(Request $request): ProviderLoginResult
    {
        $request->session()->regenerate();

        $this->providerRepository->findAuthenticated()->update([
            'language' => app()->getLocale(),
        ]);

        return new ProviderLoginResult(redirectRouteName: 'provider.home');
    }
}
