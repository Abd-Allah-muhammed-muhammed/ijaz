<?php

namespace Modules\Wallet\Actions;

use App\Models\Provider;
use Modules\Wallet\Contracts\Repositories\WalletTransactionRepositoryInterface;
use Modules\Wallet\Services\WalletService;

class CreditProviderRegistrationBonusAction
{
    public function __construct(
        private readonly WalletService $walletService,
        private readonly WalletTransactionRepositoryInterface $transactionRepo,
    ) {}

    /**
     * Credits the configured welcome bonus once per provider.
     *
     * Intended lifecycle: first Pending → Approved transition (see UpdateProviderStatusAction).
     * Idempotent: skips when a prior "Registration bonus" credit already exists for this provider,
     * so re-approval after suspension/rejection/block and legacy Pending accounts that already
     * received the old registration-time grant cannot be double-credited.
     */
    public function handle(Provider $provider): void
    {
        $enabled = filter_var(
            app('settings')->get('provider_registration_bonus_enabled', true),
            FILTER_VALIDATE_BOOLEAN,
        );

        if (! $enabled) {
            return;
        }

        $bonusAmount = (float) app('settings')->get('provider_registration_bonus_amount', 50);

        if ($bonusAmount <= 0) {
            return;
        }

        $description = __('Registration bonus');

        if ($this->transactionRepo->existsCreditForOperation(
            owner: $provider,
            operation: $provider,
            descriptions: $this->registrationBonusDescriptions(),
        )) {
            return;
        }

        $this->walletService->credit(
            owner: $provider,
            amount: $bonusAmount,
            operation: $provider,
            description: $description,
        );
    }

    /**
     * Descriptions stored historically depend on the locale at credit time.
     *
     * @return list<string>
     */
    private function registrationBonusDescriptions(): array
    {
        return array_values(array_unique([
            __('Registration bonus'),
            trans('Registration bonus', [], 'en'),
            trans('Registration bonus', [], 'ar'),
        ]));
    }
}
