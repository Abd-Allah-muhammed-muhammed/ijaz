<?php

namespace Modules\Wallet\Actions;

use App\Models\Provider;
use Modules\Wallet\Services\WalletService;

class CreditProviderRegistrationBonusAction
{
    public function __construct(
        private readonly WalletService $walletService,
    ) {}

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

        $this->walletService->credit(
            owner: $provider,
            amount: $bonusAmount,
            operation: $provider,
            description: __('Registration bonus'),
        );
    }
}
