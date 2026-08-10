<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Http\Resources\Dashboard\NotificationCollection;
use App\Models\Admin;
use App\Services\Account\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use MMAE\ApiResponse\Traits\HasApiResponse;

/**
 * Admin dashboard notification inbox — mirrors Provider NotificationController,
 * reusing AccountService / Notifiable DB relations. Broadcasts arrive on admin-{id}.
 */
class NotificationController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly AccountService $accountService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');

        return $this->successResponse(
            NotificationCollection::make(
                $this->accountService->listNotifications(
                    $admin,
                    $request->integer('per_page', 15),
                )
            ),
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');

        return $this->successResponse([
            'unread_count' => $this->accountService->unreadNotificationsCount($admin),
        ]);
    }

    public function markAsRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');
        $result = $this->accountService->markNotificationAsRead($admin, $notification);

        abort_if($result->isNotFound(), $result->statusCode, $result->message);

        return $this->successMessageResponse(message: $result->message);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        /** @var Admin $admin */
        $admin = $request->user('admin');
        $this->accountService->markAllNotificationsRead($admin);

        return $this->successMessageResponse(message: 'All notifications marked as read.');
    }
}
