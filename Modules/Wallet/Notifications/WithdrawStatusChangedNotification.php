<?php

namespace Modules\Wallet\Notifications;

use App\Enums\OperationStatusEnum;
use App\Notifications\StatusChangedNotification;
use Modules\Wallet\Models\WithdrawRequest;

class WithdrawStatusChangedNotification extends StatusChangedNotification
{
    public function __construct(
        public WithdrawRequest $withdrawRequest,
        public string $status,
    ) {}

    /**
     * @return list<string>
     */
    public static function notifiableStatuses(): array
    {
        return [
            OperationStatusEnum::Approved->value,
            OperationStatusEnum::Rejected->value,
        ];
    }

    public static function shouldNotify(string $status): bool
    {
        return in_array($status, self::notifiableStatuses(), true);
    }

    protected function domain(): string
    {
        return 'withdraw';
    }

    protected function statusValue(): string
    {
        return $this->status;
    }

    protected function entityPayload(): array
    {
        return [
            'withdraw_request_id' => $this->withdrawRequest->id,
        ];
    }

    protected function entityFirebaseData(object $notifiable): array
    {
        return [
            'withdraw_request_id' => $this->withdrawRequest->id,
            'screen' => 'withdrawRequest',
        ];
    }
}
