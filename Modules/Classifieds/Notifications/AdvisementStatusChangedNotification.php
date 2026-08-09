<?php

namespace Modules\Classifieds\Notifications;

use App\Notifications\StatusChangedNotification;
use Illuminate\Database\Eloquent\Model;
use Modules\Classifieds\Enums\AdvisementStatusEnum;

class AdvisementStatusChangedNotification extends StatusChangedNotification
{
    /**
     * @param  'car'|'property'|'electronic'|'institute'  $advisementKind
     */
    public function __construct(
        public Model $advisement,
        public string $status,
        public string $advisementKind,
    ) {}

    /**
     * @return list<string>
     */
    public static function notifiableStatuses(): array
    {
        return [
            AdvisementStatusEnum::PUBLISHED->value,
            AdvisementStatusEnum::REJECTED->value,
        ];
    }

    public static function shouldNotify(string $status): bool
    {
        return in_array($status, self::notifiableStatuses(), true);
    }

    protected function domain(): string
    {
        return 'advisement';
    }

    protected function statusValue(): string
    {
        return $this->status;
    }

    protected function entityPayload(): array
    {
        return [
            'advisement_id' => $this->advisement->getKey(),
            'advisement_kind' => $this->advisementKind,
        ];
    }

    protected function entityFirebaseData(object $notifiable): array
    {
        return [
            'advisement_id' => $this->advisement->getKey(),
            'advisement_kind' => $this->advisementKind,
            'screen' => 'advisement',
        ];
    }
}
