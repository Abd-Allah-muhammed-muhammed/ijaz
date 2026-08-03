<?php

use App\Models\Provider;
use App\Models\User;
use Modules\Chat\Models\Conversation;
use Modules\Orders\Enums\OrderStatusEnum;
use Modules\Orders\Http\Controllers\Provider\ProviderChatIndexController;
use Modules\Orders\Models\Order;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

it('renders provider chat index with empty conversations', function () {
    $provider = createWalletProvider();

    $this->actingAs($provider, 'provider')
        ->get(action(ProviderChatIndexController::class))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Chat/Index')
            ->has('rows.data', 0)
            ->where('current_conversation', null)
        );
});

it('wraps current_conversation in ConversationResource shape when selected', function () {
    $provider = createWalletProvider();
    $client = User::factory()->create();
    $order = Order::factory()->create([
        'provider_id' => $provider->id,
        'user_id' => $client->id,
        'status' => OrderStatusEnum::InProgress,
    ]);

    $conversation = Conversation::query()->create([
        'user1_id' => $provider->getKey(),
        'user1_type' => Provider::class,
        'user2_id' => $client->getKey(),
        'user2_type' => User::class,
        'operation_id' => $order->id,
        'operation_type' => Order::class,
    ]);

    $this->actingAs($provider, 'provider')
        ->get(action(ProviderChatIndexController::class, ['conversation' => $conversation->id]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Provider/Chat/Index')
            ->has('rows.data', 1)
            ->has('current_conversation', fn ($prop) => $prop
                ->where('id', $conversation->id)
                ->has('user1')
                ->has('user2')
                ->etc()
            )
        );
});
