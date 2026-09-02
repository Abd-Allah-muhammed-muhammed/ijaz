<?php

namespace App\Actions\Provider;

use App\Contracts\Provider\ProviderManagementRepositoryInterface;
use App\Enums\Providers\ProviderStatusEnum;
use App\Models\Provider;
use Illuminate\Validation\ValidationException;

class SelfDeactivateProviderAction
{
    public function __construct(
        private readonly ProviderManagementRepositoryInterface $repository,
    ) {}

    public function handle(Provider $provider): Provider
    {
        if ($provider->status !== ProviderStatusEnum::Approved) {
            throw ValidationException::withMessages([
                'status' => __('auth.self_deactivate_requires_approved'),
            ]);
        }

        return $this->repository->saveStatus(
            $provider,
            ProviderStatusEnum::SelfDeactivated->value,
        );
    }
}
