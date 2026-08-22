<?php

namespace Modules\Payout\Enums;

use App\Enums\Utilities\Collectable;
use App\Enums\Utilities\HasOperations;
use App\Enums\Utilities\Stringable;

enum PayoutStatusEnum: string
{
    use Collectable, HasOperations, Stringable;

    case Pending = 'pending';
    case Submitted = 'submitted';
    /** Reserved for future automated gateway outbound transfer; no Action sets this today. */
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';

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

    public function color(): string
    {
        return match ($this) {
            self::Pending => 'primary',
            self::Submitted => 'warning',
            self::Processing => 'info',
            self::Completed => 'success',
            self::Failed => 'danger',
        };
    }

    /**
     * Statuses that map to provider-facing `in_progress` (money mid-transfer).
     * Single source of truth for transfer_status mapping and amount_in_transfer sums.
     *
     * @return list<self>
     */
    public static function inProgressCases(): array
    {
        return [
            self::Pending,
            self::Submitted,
            self::Processing,
        ];
    }

    /**
     * @return list<string>
     */
    public static function inProgressValues(): array
    {
        return array_map(
            static fn (self $case): string => $case->value,
            self::inProgressCases(),
        );
    }

    /**
     * Provider-facing transfer status for mobile (wallet history). Never exposes
     * admin/audit fields — only a collapsed status with label + color.
     *
     * @return array{value: string, label: string, color: string}
     */
    public function toProviderStatus(): array
    {
        $value = in_array($this, self::inProgressCases(), true)
            ? 'in_progress'
            : match ($this) {
                self::Completed => 'transferred',
                self::Failed => 'delayed',
            };

        return [
            'value' => $value,
            'label' => __('payout.transfer_status.'.$value),
            'color' => match ($value) {
                'in_progress' => 'warning',
                'transferred' => 'success',
                'delayed' => 'danger',
            },
        ];
    }
}
