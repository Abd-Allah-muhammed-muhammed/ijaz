<?php

namespace App\DTOs\Account;

final readonly class AccountCountsData
{
    public function __construct(
        public int $unreadNotificationsCount,
        public int $unreadMessagesCount,
    ) {}
}
