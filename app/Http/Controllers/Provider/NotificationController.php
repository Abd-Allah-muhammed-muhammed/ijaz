<?php

namespace App\Http\Controllers\Provider;

use App\Http\Controllers\Controller;
use App\Http\Resources\Provider\NotificationCollection;
use App\Models\Provider;
use App\Services\Account\AccountService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use MMAE\ApiResponse\Traits\HasApiResponse;

/**
 * Provider dashboard notification inbox — reuses AccountService / Notifiable DB relations.
 */
class NotificationController extends Controller
{
    use HasApiResponse;

    public function __construct(
        private readonly AccountService $accountService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->user('provider');

        return $this->successResponse(
            NotificationCollection::make(
                $this->accountService->listNotifications(
                    $provider,
                    $request->integer('per_page', 15),
                )
            ),
        );
    }

    public function unreadCount(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->user('provider');

        return $this->successResponse([
            'unread_count' => $this->accountService->unreadNotificationsCount($provider),
        ]);
    }

    public function markAsRead(Request $request, DatabaseNotification $notification): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->user('provider');
        $result = $this->accountService->markNotificationAsRead($provider, $notification);

        abort_if($result->isNotFound(), $result->statusCode, $result->message);

        return $this->successMessageResponse(message: $result->message);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->user('provider');
        $this->accountService->markAllNotificationsRead($provider);

        return $this->successMessageResponse(message: 'All notifications marked as read.');
    }
}
