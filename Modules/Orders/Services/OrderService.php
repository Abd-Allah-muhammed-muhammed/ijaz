<?php

namespace Modules\Orders\Services;

use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Modules\Chat\DTOs\ChatMessageData;
use Modules\Chat\Models\ConversationMessage;
use Modules\Orders\Actions\Dashboard\BroadcastAdminOrderConversationTypingAction;
use Modules\Orders\Actions\Dashboard\CountAllOrdersAction;
use Modules\Orders\Actions\Dashboard\GetOrderStatusDistributionAction;
use Modules\Orders\Actions\Dashboard\ListDashboardHomeWindowedOrdersAction;
use Modules\Orders\Actions\Dashboard\ListDashboardOrdersAction;
use Modules\Orders\Actions\Dashboard\ListOrderConversationMessagesAction;
use Modules\Orders\Actions\Dashboard\SendAdminOrderConversationMessageAction;
use Modules\Orders\Actions\Dashboard\ShowDashboardOrderAction;
use Modules\Orders\Actions\Provider\EndProviderOrderAction;
use Modules\Orders\Actions\Provider\GetProviderHomeOrderStatsAction;
use Modules\Orders\Actions\Provider\ListProviderHomeRecommendedOrdersAction;
use Modules\Orders\Actions\Provider\ListProviderHomeWindowedOrdersAction;
use Modules\Orders\Actions\Provider\ListProviderOrderConversationsAction;
use Modules\Orders\Actions\Provider\ListProviderOrdersAction;
use Modules\Orders\Actions\Provider\ListRecommendedOrdersAction;
use Modules\Orders\Actions\Provider\ShowProviderOrderAction;
use Modules\Orders\Actions\Provider\UpdateProviderReviewAction;
use Modules\Orders\Actions\User\CreateOrderAction;
use Modules\Orders\Actions\User\DeleteOrderAction;
use Modules\Orders\Actions\User\DeleteOrderMediaAction;
use Modules\Orders\Actions\User\EditOrderAction;
use Modules\Orders\Actions\User\EndAndReviewOrderAction;
use Modules\Orders\Actions\User\ListUserOrdersAction;
use Modules\Orders\Actions\User\ShowOrderAction;
use Modules\Orders\DTOs\EndAndReviewDTO;
use Modules\Orders\DTOs\StoreOrderDTO;
use Modules\Orders\DTOs\UpdateOrderDTO;
use Modules\Orders\Models\Order;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Throwable;

class OrderService
{
    public function __construct(
        private readonly ListUserOrdersAction $listUserOrders,
        private readonly CreateOrderAction $createOrder,
        private readonly ShowOrderAction $showOrder,
        private readonly EditOrderAction $editOrder,
        private readonly DeleteOrderAction $deleteOrder,
        private readonly DeleteOrderMediaAction $deleteOrderMedia,
        private readonly EndAndReviewOrderAction $endAndReviewOrder,
        private readonly ListProviderOrdersAction $listProviderOrders,
        private readonly ListRecommendedOrdersAction $listRecommendedOrders,
        private readonly ListProviderHomeRecommendedOrdersAction $listProviderHomeRecommendedOrders,
        private readonly ListProviderHomeWindowedOrdersAction $listProviderHomeWindowedOrders,
        private readonly GetProviderHomeOrderStatsAction $getProviderHomeOrderStats,
        private readonly ListProviderOrderConversationsAction $listProviderOrderConversations,
        private readonly ShowProviderOrderAction $showProviderOrder,
        private readonly EndProviderOrderAction $endProviderOrder,
        private readonly UpdateProviderReviewAction $updateProviderReview,
        private readonly ListDashboardOrdersAction $listDashboardOrders,
        private readonly ListDashboardHomeWindowedOrdersAction $listDashboardHomeWindowedOrders,
        private readonly CountAllOrdersAction $countAllOrders,
        private readonly GetOrderStatusDistributionAction $getOrderStatusDistribution,
        private readonly ShowDashboardOrderAction $showDashboardOrder,
        private readonly ListOrderConversationMessagesAction $listConversationMessages,
        private readonly SendAdminOrderConversationMessageAction $sendAdminConversationMessage,
        private readonly BroadcastAdminOrderConversationTypingAction $broadcastAdminConversationTyping,
    ) {}

    public function listForUser(User $user, int $perPage): LengthAwarePaginator
    {
        return $this->listUserOrders->handle($user, $perPage);
    }

    /**
     * @throws Throwable
     */
    public function create(User $user, StoreOrderDTO $data): Order
    {
        return $this->createOrder->handle($user, $data);
    }

    public function showForUser(Order $order, User $user): Order
    {
        return $this->showOrder->handle($order, $user);
    }

    /**
     * @throws Throwable
     */
    public function update(Order $order, User $user, UpdateOrderDTO $data): Order
    {
        return $this->editOrder->handle($order, $user, $data);
    }

    /**
     * @throws Throwable
     */
    public function delete(Order $order, User $user): void
    {
        $this->deleteOrder->handle($order, $user);
    }

    /**
     * @throws Throwable
     */
    public function deleteMedia(Order $order, Media $media, User $user): void
    {
        $this->deleteOrderMedia->handle($order, $media, $user);
    }

    /**
     * @throws Throwable
     */
    public function endAndReview(Order $order, User $user, EndAndReviewDTO $data): void
    {
        $this->endAndReviewOrder->handle($order, $user, $data);
    }

    /**
     * @param  array{status?: mixed, date_from?: mixed, date_to?: mixed, search?: mixed}  $filters
     */
    public function listForProvider(Provider $provider, array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->listProviderOrders->handle($provider, $filters, $perPage);
    }

    public function listRecommendedForProvider(Provider $provider, int $perPage): LengthAwarePaginator
    {
        return $this->listRecommendedOrders->handle($provider, $perPage);
    }

    /**
     * @return EloquentCollection<int, Order>
     */
    public function listRecommendedForProviderHome(Provider $provider, int $limit = 10): EloquentCollection
    {
        return $this->listProviderHomeRecommendedOrders->handle($provider, $limit);
    }

    /**
     * @return Collection<string, Collection<int, Order>>
     */
    public function listWindowedForProviderHome(Provider $provider): Collection
    {
        return $this->listProviderHomeWindowedOrders->handle($provider);
    }

    /**
     * @return array{totalOrders: int, totalFinishedOrders: int}
     */
    public function providerHomeStats(Provider $provider): array
    {
        return $this->getProviderHomeOrderStats->handle($provider);
    }

    public function listConversationsForProvider(Provider $provider, int $perPage = 10): LengthAwarePaginator
    {
        return $this->listProviderOrderConversations->handle($provider, $perPage);
    }

    public function showForProvider(Order $order, Provider $provider): Order
    {
        return $this->showProviderOrder->handle($order, $provider);
    }

    public function endForProvider(Order $order, ?Authenticatable $authUser): void
    {
        $this->endProviderOrder->handle($order, $authUser);
    }

    public function updateReviewForProvider(Order $order, ?Authenticatable $authUser, EndAndReviewDTO $data): void
    {
        $this->updateProviderReview->handle($order, $authUser, $data);
    }

    /**
     * @return Collection<string, Collection<int, Order>>
     */
    public function listWindowedForDashboardHome(): Collection
    {
        return $this->listDashboardHomeWindowedOrders->handle();
    }

    public function countAll(): int
    {
        return $this->countAllOrders->handle();
    }

    /**
     * @return array<string, int>
     */
    public function statusDistribution(): array
    {
        return $this->getOrderStatusDistribution->handle();
    }

    /**
     * @param  array{status?: mixed, date_from?: mixed, date_to?: mixed, search?: mixed}  $filters
     */
    public function listForDashboard(array $filters, int $perPage): LengthAwarePaginator
    {
        return $this->listDashboardOrders->handle($filters, $perPage);
    }

    /**
     * @return array<string, int>
     */
    public function dashboardStats(): array
    {
        return $this->listDashboardOrders->stats();
    }

    public function showForDashboard(Order $order): Order
    {
        return $this->showDashboardOrder->handle($order);
    }

    public function conversationMessages(Order $order, int $perPage = 15, ?string $search = null): ?LengthAwarePaginator
    {
        return $this->listConversationMessages->handle($order, $perPage, $search);
    }

    public function sendConversationMessageAsAdmin(
        Order $order,
        Admin $admin,
        ChatMessageData $data,
    ): ConversationMessage {
        return $this->sendAdminConversationMessage->handle($order, $admin, $data);
    }

    public function typingAsAdmin(Order $order, Admin $admin): void
    {
        $this->broadcastAdminConversationTyping->handle($order, $admin);
    }
}
