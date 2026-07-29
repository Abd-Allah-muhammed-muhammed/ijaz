<?php

namespace App\Services\Account;

use App\Actions\Account\DeleteAccountAction;
use App\Actions\Account\DeleteAllNotificationsAction;
use App\Actions\Account\DeleteNotificationAction;
use App\Actions\Account\GetAccountCountsAction;
use App\Actions\Account\ListNotificationsAction;
use App\Actions\Account\MarkAllNotificationsReadAction;
use App\Actions\Account\MarkNotificationAsReadAction;
use App\Actions\Account\UpdateAccountSettingsAction;
use App\DTOs\Account\AccountCountsData;
use App\DTOs\Account\DeleteNotificationResult;
use App\DTOs\Account\MarkNotificationResult;
use App\DTOs\Account\UpdateAccountSettingsDTO;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Facades\DB;

class AccountService
{
    public function __construct(
        private readonly GetAccountCountsAction $getCountsAction,
        private readonly ListNotificationsAction $listNotificationsAction,
        private readonly MarkAllNotificationsReadAction $markAllNotificationsReadAction,
        private readonly MarkNotificationAsReadAction $markNotificationAsReadAction,
        private readonly DeleteNotificationAction $deleteNotificationAction,
        private readonly DeleteAllNotificationsAction $deleteAllNotificationsAction,
        private readonly UpdateAccountSettingsAction $updateSettingsAction,
        private readonly DeleteAccountAction $deleteAccountAction,
    ) {}

    public function counts(Model $user): AccountCountsData
    {
        return $this->getCountsAction->handle($user);
    }

    public function listNotifications(Model $user, int $perPage = 15): LengthAwarePaginator
    {
        return $this->listNotificationsAction->handle($user, $perPage);
    }

    public function markAllNotificationsRead(Model $user): void
    {
        $this->markAllNotificationsReadAction->handle($user);
    }

    public function markNotificationAsRead(Model $user, DatabaseNotification $notification): MarkNotificationResult
    {
        return $this->markNotificationAsReadAction->handle($user, $notification);
    }

    public function deleteNotification(Model $user, DatabaseNotification $notification): DeleteNotificationResult
    {
        return $this->deleteNotificationAction->handle($user, $notification);
    }

    public function deleteAllNotifications(Model $user): void
    {
        $this->deleteAllNotificationsAction->handle($user);
    }

    public function updateSettings(Model $user, UpdateAccountSettingsDTO $dto): Model
    {
        return $this->updateSettingsAction->handle($user, $dto);
    }

    public function deleteAccount(Model $user): void
    {
        DB::transaction(fn () => $this->deleteAccountAction->handle($user));
    }
}
