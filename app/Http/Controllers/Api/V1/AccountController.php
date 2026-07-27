<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\Account\UpdateAccountSettingsDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateAccountLanguageRequest;
use App\Http\Resources\Api\V1\NotificationCollection;
use App\Models\Provider;
use App\Models\User;
use App\Services\Account\AccountService;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use MMAE\ApiResponse\Traits\HasApiResponse;

#[Group('Users')]
class AccountController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly AccountService $accountService,
    ) {}

    /**
     * Get counts of unread notifications and unread messages for the authenticated user.
     */
    public function counts(): JsonResponse
    {
        /**
         * @var User|Provider $user
         */
        $user = auth()->user();
        $counts = $this->accountService->counts($user);

        return $this->successResponse([
            'unread_notifications_count' => $counts->unreadNotificationsCount,
            'unread_messages_count' => $counts->unreadMessagesCount,
        ]);
    }

    public function markAllNotificationsAsRead(): JsonResponse
    {
        $this->accountService->markAllNotificationsRead(auth()->user());

        return $this->successMessageResponse(message: 'All notifications marked as read.');
    }

    public function markAsRead(DatabaseNotification $notification): JsonResponse
    {
        $result = $this->accountService->markNotificationAsRead(auth()->user(), $notification);

        abort_if($result->isNotFound(), $result->statusCode, $result->message);

        return $this->successMessageResponse(message: $result->message);
    }

    public function deleteNotification(DatabaseNotification $notification): JsonResponse
    {
        $result = $this->accountService->deleteNotification(auth()->user(), $notification);

        abort_if($result->isNotFound(), $result->statusCode, $result->message);

        return $this->successMessageResponse(message: $result->message);
    }

    public function deleteAllNotification(): JsonResponse
    {
        $this->accountService->deleteAllNotifications(auth()->user());

        return $this->successMessageResponse(message: 'Notifications deleted successfully.');
    }

    public function notifications(Request $request): JsonResponse
    {
        return $this->successResponse(
            NotificationCollection::make(
                $this->accountService->listNotifications(
                    auth()->user(),
                    (int) $request->get('per_page', 15),
                )
            ),
        );
    }

    public function updateSettings(UpdateAccountLanguageRequest $request): JsonResponse
    {
        $this->accountService->updateSettings(
            auth()->user(),
            UpdateAccountSettingsDTO::fromValidated($request->validated()),
        );

        return $this->successMessageResponse(message: 'Settings updated successfully.');
    }

    public function deleteAccount(): JsonResponse
    {
        $this->accountService->deleteAccount(auth()->user());

        return $this->successMessageResponse(message: 'Account deleted successfully.');
    }
}
