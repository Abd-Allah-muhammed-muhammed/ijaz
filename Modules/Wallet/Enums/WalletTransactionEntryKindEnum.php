<?php

namespace Modules\Wallet\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum WalletTransactionEntryKindEnum: string
{
    use Collectable, HasOperations, Stringable;

    case WithdrawRequested = 'withdraw_requested';
    case WithdrawHoldReleased = 'withdraw_hold_released';
    case WithdrawApproved = 'withdraw_approved';
    case WithdrawRejected = 'withdraw_rejected';
    case WithdrawCancelled = 'withdraw_cancelled';
    case TopupCredited = 'topup_credited';

    public function label(): string
    {
        return match ($this) {
            self::WithdrawRequested => 'Withdraw requested',
            self::WithdrawHoldReleased => 'Withdraw hold released',
            self::WithdrawApproved => 'Withdraw approved',
            self::WithdrawRejected => 'Withdraw rejected',
            self::WithdrawCancelled => 'Withdraw cancelled',
            self::TopupCredited => 'Top-up credited',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::WithdrawRequested => 'primary',
            self::WithdrawHoldReleased => 'info',
            self::WithdrawApproved, self::TopupCredited => 'success',
            self::WithdrawRejected, self::WithdrawCancelled => 'danger',
        };
    }

    /**
     * @return array{label: string, color: string, value: string}
     */
    public function toArray(): array
    {
        return [
            'label' => $this->toString(),
            'color' => $this->color(),
            'value' => $this->value,
        ];
    }

    public function translationKey(): string
    {
        return 'wallet.entry_kind.'.$this->value;
    }
}
