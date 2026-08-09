<?php

namespace App\Notifications;

use App\Enums\Providers\ProviderStatusEnum;
use App\Enums\Users\UserStatusEnum;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

/**
 * Single class for User + Provider account status changes.
 * Keys are shared ({account_status_*}); payload carries account_type for clients.
 *
 * Notifiable statuses differ by actor (User has no approved/rejected/suspended cases):
 * - Provider: approved, rejected, blocked, suspended
 * - User: blocked
 */
class AccountStatusChangedNotification extends StatusChangedNotification
{
    /**
     * @param  User|Provider  $account
     */
    public function __construct(
        public Model $account,
        public string $status,
    ) {}

    /**
     * @return list<string>
     */
    public static function notifiableStatusesFor(Model $account): array
    {
        if ($account instanceof Provider) {
            return [
                ProviderStatusEnum::Approved->value,
                ProviderStatusEnum::Rejected->value,
                ProviderStatusEnum::Blocked->value,
                ProviderStatusEnum::Suspended->value,
            ];
        }

        if ($account instanceof User) {
            return [
                UserStatusEnum::Blocked->value,
            ];
        }

        return [];
    }

    public static function shouldNotify(Model $account, string $status): bool
    {
        return in_array($status, self::notifiableStatusesFor($account), true);
    }

    protected function domain(): string
    {
        return 'account';
    }

    protected function statusValue(): string
    {
        return $this->status;
    }

    protected function entityPayload(): array
    {
        return [
            'account_id' => $this->account->getKey(),
            'account_type' => $this->account instanceof Provider ? 'provider' : 'user',
        ];
    }

    protected function entityFirebaseData(object $notifiable): array
    {
        return [
            'account_id' => $this->account->getKey(),
            'account_type' => $this->account instanceof Provider ? 'provider' : 'user',
            'screen' => 'account',
        ];
    }
}
