<?php

namespace App\Repositories\Account;

use App\Contracts\Account\AccountRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;

class AccountRepository implements AccountRepositoryInterface
{
    public function unreadNotificationsCount(Model $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function paginateNotifications(Model $user, int $perPage): LengthAwarePaginator
    {
        return $user
            ->notifications()
            ->latest()
            ->paginate($perPage);
    }

    public function markAllNotificationsRead(Model $user): void
    {
        $user->unreadNotifications()->update([
            'read_at' => now(),
        ]);
    }

    public function findNotificationForUser(Model $user, string $notificationId): ?DatabaseNotification
    {
        /** @var DatabaseNotification|null $notification */
        $notification = $user->notifications()->whereKey($notificationId)->first();

        return $notification;
    }

    public function markNotificationRead(DatabaseNotification $notification): void
    {
        $notification->markAsRead();
    }

    public function deleteNotification(DatabaseNotification $notification): void
    {
        $notification->delete();
    }

    public function deleteAllNotifications(Model $user): void
    {
        $user->notifications()->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateSettings(Model $user, array $data): Model
    {
        $user->update($data);

        return $user;
    }

    public function markAccountDeleted(Model $user): void
    {
        $user->update([
            'status' => 'deleted',
        ]);
    }

    public function revokeTokens(Model $user): void
    {
        $user->tokens()->delete();

        if (method_exists($user, 'clearAllDeviceTokens')) {
            $user->clearAllDeviceTokens();
        }
    }
}
