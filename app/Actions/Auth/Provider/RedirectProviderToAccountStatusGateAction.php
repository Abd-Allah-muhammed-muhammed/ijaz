<?php

namespace App\Actions\Auth\Provider;

use App\Models\Provider;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\RedirectResponse;

/**
 * Shared logout + signed-URL redirect used by LoginRequest and
 * EnsureProviderIsApprovedMiddleware when a non-Approved provider is rejected.
 */
class RedirectProviderToAccountStatusGateAction
{
    public function __construct(
        private readonly GenerateProviderAccountStatusGateUrlAction $generateUrlAction,
    ) {}

    public function handle(Provider $provider): RedirectResponse
    {
        return redirect()->to($this->generateUrlAction->handle($provider));
    }

    /**
     * Abort the current FormRequest / auth attempt with an Inertia-friendly redirect.
     */
    public function throwRedirect(Provider $provider): never
    {
        throw new HttpResponseException($this->handle($provider));
    }
}
