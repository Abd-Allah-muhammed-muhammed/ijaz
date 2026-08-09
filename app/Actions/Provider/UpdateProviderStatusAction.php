<?php

namespace App\Actions\Provider;

use App\Contracts\Provider\ProviderManagementRepositoryInterface;
use App\DTOs\Provider\UpdateProviderStatusDTO;
use App\Enums\Providers\ProviderStatusEnum;
use App\Models\Provider;
use Modules\Wallet\Actions\CreditProviderRegistrationBonusAction;

class UpdateProviderStatusAction
{
    public function __construct(
        private readonly ProviderManagementRepositoryInterface $repository,
        private readonly CreditProviderRegistrationBonusAction $creditRegistrationBonusAction,
    ) {}

    public function handle(Provider $provider, UpdateProviderStatusDTO $dto): Provider
    {
        $wasPending = $provider->status === ProviderStatusEnum::Pending;
        $becomingApproved = $dto->status === ProviderStatusEnum::Approved->value;

        $provider = $this->repository->saveStatus($provider, $dto->status);

        if ($dto->status === ProviderStatusEnum::Blocked->value) {
            $this->repository->block($provider, $dto->blockDays ?: 0, $dto->blockReason);
        }

        // Welcome bonus only on first approval (Pending → Approved), never on re-approval
        // from Suspended / Rejected / Blocked, and never on non-approval status writes.
        if ($wasPending && $becomingApproved) {
            $this->creditRegistrationBonusAction->handle($provider);
        }

        return $provider;
    }
}
