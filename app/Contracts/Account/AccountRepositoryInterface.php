<?php

namespace App\Contracts\Account;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

interface AccountRepositoryInterface
{
    public function unreadNotificationsCount(Model $user): int;

    public function paginateNotifications(Model $user, int $perPage): LengthAwarePaginator;

    public function markAllNotificationsRead(Model $user): void;

    public function findNotificationForUser(Model $user, string $notificationId): ?DatabaseNotification;

    public function markNotificationRead(DatabaseNotification $notification): void;

    public function deleteNotification(DatabaseNotification $notification): void;

    public function deleteAllNotifications(Model $user): void;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSettings(Model $user, array $data): Model;

    public function markAccountDeleted(Model $user): void;

    public function revokeTokens(Model $user): void;
}
