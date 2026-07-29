<?php

namespace Modules\Orders\Actions\Provider;

use App\Models\Provider;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Chat\Services\ConversationService;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Models\Order;

class ListProviderOrderConversationsAction
{
    public function __construct(
        private readonly ConversationService $conversations,
    ) {}

    public function handle(Provider $provider, int $perPage = 10): LengthAwarePaginator
    {
        return $this->conversations->listForProviderOrderOperations(
            $provider,
            Order::class,
            (new Order)->getTable(),
            OrderStatusEnum::EndedByClient->value,
            $perPage,
        );
    }
}
