<?php

use Illuminate\Routing\Route as IlluminateRoute;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\Provider\OrderChatController;
use Modules\Chat\Infrastructure\Events\ChatUpdatedEvent;
use Modules\Chat\Infrastructure\Events\NewMessageEvent;

beforeEach(function () {
    withoutOrdersLocaleMiddleware();
});

function providerOrderChatRoute(string $method, string $uriSuffix): IlluminateRoute
{
    $route = collect(Route::getRoutes())->first(
        fn ($r) => in_array($method, $r->methods(), true)
            && str_ends_with($r->uri(), $uriSuffix)
            && str_contains($r->getActionName(), OrderChatController::class)
    );

    expect($route)->not->toBeNull("Expected Provider OrderChat route ending in [{$uriSuffix}]");

    return $route;
}

/**
 * Regression lock for Chat RouteServiceProvider omitting the `web` group.
 * Without StartSession/cookies/CSRF, auth:provider always sees a guest on XHR
 * and returns 401 — even when the Provider is logged in on Inertia pages.
 */
test('provider can load an order chat conversation without a 401 (session middleware present)', function () {
    $route = providerOrderChatRoute('GET', 'chat/orders/{conversation}');

    expect($route->gatherMiddleware())->toContain('web');

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);

    $this->actingAs($provider, 'provider')
        ->getJson(action([OrderChatController::class, 'show'], ['conversation' => $conversation->id]))
        ->assertSuccessful()
        ->assertJsonStructure(['data' => ['items', 'paginate']]);
});

test('provider can send a chat message without a 401', function () {
    $route = providerOrderChatRoute('POST', 'chat/orders/send/{conversation}');

    expect($route->gatherMiddleware())->toContain('web');

    Bus::fake();
    Event::fake([NewMessageEvent::class, ChatUpdatedEvent::class]);

    ['user' => $user, 'provider' => $provider, 'order' => $order] = createOrderWithParticipants();
    $conversation = createOrderConversation($user, $provider, $order);
    $conversation->load(['user1', 'user2']);

    $this->actingAs($provider, 'provider')
        ->postJson(action([OrderChatController::class, 'send'], ['conversation' => $conversation->id]), [
            'content' => 'Provider session chat send',
        ])
        ->assertSuccessful()
        ->assertJsonPath('data.content', 'Provider session chat send');
});
