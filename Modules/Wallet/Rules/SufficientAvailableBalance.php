<?php

namespace Modules\Wallet\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Translation\PotentiallyTranslatedString;
use Modules\Wallet\Services\WalletService;

/**
 * Ensures the requested withdrawal amount does not exceed available balance
 * (wallet.balance - wallet.pending_debit), including holds from other pending withdrawals.
 */
final class SufficientAvailableBalance implements ValidationRule
{
    public function __construct(
        private readonly ?string $guard = null,
    ) {}

    /**
     * @param  Closure(string, ?string=): PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_numeric($value)) {
            return;
        }

        /** @var Model|null $owner */
        $owner = $this->guard !== null
            ? auth($this->guard)->user()
            : auth()->user();

        if ($owner === null) {
            return;
        }

        $amount = (float) $value;
        $available = app(WalletService::class)->getBalance($owner)->available;

        if ($available < $amount) {
            $fail(__('insufficient_available_balance', [
                'available' => $available,
                'requested' => $amount,
            ]));
        }
    }
}
