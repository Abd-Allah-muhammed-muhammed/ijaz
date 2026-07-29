<?php

namespace App\Actions\Account;

use App\Contracts\Account\AccountRepositoryInterface;
use App\DTOs\Account\AccountCountsData;
use Illuminate\Database\Eloquent\Model;
use Modules\Chat\Services\ConversationService;

class GetAccountCountsAction
{
    public function __construct(
        private readonly AccountRepositoryInterface $repository,
        private readonly ConversationService $conversationService,
    ) {}

    public function handle(Model $user): AccountCountsData
    {
        return new AccountCountsData(
            unreadNotificationsCount: $this->repository->unreadNotificationsCount($user),
            unreadMessagesCount: $this->conversationService->countUnreadFor($user),
        );
    }
}
