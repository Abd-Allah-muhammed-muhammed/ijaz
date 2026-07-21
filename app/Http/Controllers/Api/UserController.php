<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\UpdateSettingsRequest;
use App\Http\Resources\Api\V1\NotificationCollection;
use App\Models\Provider;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use MMAE\ApiResponse\Traits\HasApiResponse;
use Modules\Chat\Models\ConversationMessage;

#[Group('Users')]
class UserController extends Controller
{
    use HasApiResponse;

    /**
     * Get counts of unread notifications and unread messages for the authenticated user.
     */
    public function counts(): JsonResponse
    {
        /**
         * @var User | Provider $user
         */
        $user = auth()->user();

        return $this->successResponse([
            'unread_notifications_count' => $user->unreadNotifications()->count(),
            'unread_messages_count' => ConversationMessage::query()
                ->whereMorphedTo('receiver', $user)
                ->whereNull('read_at')
                ->count(),
        ]);
    }

    public function markAllNotificationsAsRead(): JsonResponse
    {
        $user = auth()->user();
        $user->unreadNotifications()->update(
            ['read_at' => now()]
        );

        return $this->successMessageResponse(message: 'All notifications marked as read.');
    }

    public function markAsRead(DatabaseNotification $notification): JsonResponse
    {
        abort_unless(
            $notification->notifiable()->is(auth()->user()),
            404,
            'Notification not found or already read.'
        );
        if ($notification->read_at) {
            return $this->successMessageResponse(message: 'Notification already marked as read.');
        }
        $notification->markAsRead();

        return $this->successMessageResponse(message: 'Notification marked as read.');
    }

    public function deleteNotification(DatabaseNotification $notification): JsonResponse
    {
        abort_unless(
            $notification->notifiable()->is(auth()->user()),
            404,
            'Notification not found'
        );
        $notification->delete();

        return $this->successMessageResponse(message: 'Notification deleted successfully.');
    }

    public function deleteAllNotification(): JsonResponse
    {
        auth()->user()->notifications()->delete();

        return $this->successMessageResponse(message: 'Notifications deleted successfully.');
    }

    public function notifications(Request $request)
    {
        $user = auth()->user();

        return $this->successResponse(
            NotificationCollection::make(
                $user
                    ->notifications()
                    ->latest()
                    ->paginate($request->get('per_page', 15))
            ),
        );
    }

    public function updateSettings(UpdateSettingsRequest $request): JsonResponse
    {
        $user = auth()->user();
        $data = $request->validated();
        $user->update($data);

        return $this->successMessageResponse(message: 'Settings updated successfully.');
    }

    public function deleteAccount()
    {
        $user = auth()->user();

        $user->update([
            'status' => 'deleted',
        ]);

        $user->tokens()->delete(); // Delete previous login token

        return $this->successMessageResponse(message: 'Account deleted successfully.');
    }
}
