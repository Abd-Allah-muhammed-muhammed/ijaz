<?php

use App\Models\Admin;
use App\Models\User;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Chat\Models\ConversationMessage;
use Modules\Support\Http\Controllers\Dashboard\SupportController;

function withoutDashboardLocaleMiddlewareForSupportTicketShow(): void
{
    test()->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    test()->withoutVite();
}

test('dashboard support ticket show includes chat and messages when conversation exists', function () {
    withoutDashboardLocaleMiddlewareForSupportTicketShow();

    $admin = Admin::query()->create([
        'name' => 'Dashboard Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    ['ticket' => $ticket, 'conversation' => $conversation, 'user' => $user] = createTicketSupportConversation();

    ConversationMessage::query()->create([
        'conversation_id' => $conversation->id,
        'sender_type' => User::class,
        'sender_id' => $user->getKey(),
        'receiver_type' => User::class,
        'receiver_id' => $user->getKey(),
        'content' => 'Hello from ticket user',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([SupportController::class, 'show'], ['ticket' => $ticket]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Tickets/Show')
            ->where('chat.id', $conversation->id)
            ->has('chatMessages', 1)
            ->where('chatMessages.0.content', 'Hello from ticket user')
        );
});

test('dashboard support ticket show returns empty chat messages when conversation does not exist', function () {
    withoutDashboardLocaleMiddlewareForSupportTicketShow();

    $admin = Admin::query()->create([
        'name' => 'Dashboard Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    $ticket = createTestTicketSupport();

    $this->actingAs($admin, 'admin')
        ->get(action([SupportController::class, 'show'], ['ticket' => $ticket]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Tickets/Show')
            ->where('chat', null)
            ->where('chatMessages', [])
        );
});
