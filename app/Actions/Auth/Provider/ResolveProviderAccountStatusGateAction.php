<?php

namespace App\Actions\Auth\Provider;

use App\Contracts\Provider\ProviderManagementRepositoryInterface;
use App\DTOs\Auth\ProviderAccountStatusGateDTO;
use App\Enums\Providers\ProviderStatusEnum;
use App\Models\Provider;

class ResolveProviderAccountStatusGateAction
{
    public function __construct(
        private readonly ProviderManagementRepositoryInterface $repository,
    ) {}

    /**
     * Returns null when the provider is Approved (gate page is not applicable).
     */
    public function handle(Provider $provider): ?ProviderAccountStatusGateDTO
    {
        $provider = $this->repository->loadForAccountStatusGate($provider);

        if ($provider->status === ProviderStatusEnum::Approved) {
            return null;
        }

        $isTemporaryBlock = (bool) $provider->blocked_until;
        $reason = null;
        $blockReason = null;

        if (in_array($provider->status, [
            ProviderStatusEnum::Suspended,
            ProviderStatusEnum::Rejected,
        ], true)) {
            $reason = filled($provider->reason) ? (string) $provider->reason : null;
        }

        if ($provider->status === ProviderStatusEnum::Blocked) {
            $blockReason = filled($provider->latestBlockHistory?->reason)
                ? (string) $provider->latestBlockHistory->reason
                : null;
        }

        return new ProviderAccountStatusGateDTO(
            status: $provider->status,
            reason: $reason,
            blockedUntil: $provider->blocked_until?->toIso8601String(),
            isTemporaryBlock: $isTemporaryBlock,
            blockReason: $blockReason,
        );
    }
}
