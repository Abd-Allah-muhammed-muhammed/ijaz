<?php

use App\Enums\Order\OrderStatusEnum;
use App\Enums\Providers\ProviderStatusEnum;
use Modules\Marketplace\Models\Category;
use App\Models\Order;
use App\Models\Provider;
use Modules\Marketplace\Models\ProviderType;
use App\Models\User;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\System;
use Modules\Support\Enums\TicketSupportStatusEnum;
use Modules\Support\Models\TicketSupport;

function ensureSystemExists(): System
{
    return System::query()->firstOrCreate(
        ['id' => 1],
        ['name' => 'System', 'online' => false],
    );
}

function createTestCategory(): Category
{
    $category = Category::query()->create([
        'icon' => 'categories/test.png',
    ]);
    $category->translateOrNew(app()->getLocale())->title = 'Test Category';
    $category->save();

    return $category;
}

function createTestProvider(): Provider
{
    $providerType = ProviderType::query()->create([
        'image' => 'provider-types/test.png',
    ]);
    $providerType->translateOrNew(app()->getLocale())->name = 'Test Provider Type';
    $providerType->save();

    return Provider::query()->create([
        'name' => fake()->name(),
        'iban' => 'SA'.fake()->unique()->numerify('################'),
        'logo' => 'providers/test.png',
        'password' => bcrypt('password'),
        'provider_type_id' => $providerType->id,
        'status' => ProviderStatusEnum::Approved,
    ]);
}

function createMemberConversation(User $user1, User $user2): Conversation
{
    return Conversation::query()->create([
        'user1_id' => $user1->getKey(),
        'user1_type' => User::class,
        'user2_id' => $user2->getKey(),
        'user2_type' => User::class,
    ]);
}

function createOrderWithParticipants(?User $user = null, ?Provider $provider = null): array
{
    $user ??= User::factory()->create();
    $provider ??= createTestProvider();

    $order = Order::query()->create([
        'title' => fake()->sentence(),
        'description' => fake()->paragraph(),
        'user_id' => $user->getKey(),
        'provider_id' => $provider->getKey(),
        'category_id' => createTestCategory()->getKey(),
        'budget_start' => 100,
        'budget_end' => 500,
        'status' => OrderStatusEnum::New,
    ]);

    return compact('user', 'provider', 'order');
}

function createOrderConversation(User $user, Provider $provider, Order $order): Conversation
{
    return Conversation::query()->create([
        'user1_id' => $user->getKey(),
        'user1_type' => User::class,
        'user2_id' => $provider->getKey(),
        'user2_type' => Provider::class,
        'operation_type' => Order::class,
        'operation_id' => $order->getKey(),
    ]);
}

function createTicketSupportConversation(?User $user = null): array
{
    ensureSystemExists();
    $user ??= User::factory()->create();
    $ticket = TicketSupport::query()->create([
        'user_type' => User::class,
        'user_id' => $user->getKey(),
        'title' => fake()->sentence(),
        'message' => fake()->paragraph(),
        'status' => TicketSupportStatusEnum::Pending,
    ]);

    $conversation = Conversation::query()->create([
        'user1_id' => 1,
        'user1_type' => System::class,
        'user2_id' => $user->getKey(),
        'user2_type' => User::class,
        'operation_type' => TicketSupport::class,
        'operation_id' => $ticket->getKey(),
    ]);

    return compact('user', 'ticket', 'conversation');
}

function createTestTicketSupport(?User $user = null): TicketSupport
{
    $user ??= User::factory()->create();

    return TicketSupport::query()->create([
        'user_type' => User::class,
        'user_id' => $user->getKey(),
        'title' => fake()->sentence(),
        'message' => fake()->paragraph(),
        'status' => TicketSupportStatusEnum::Pending,
    ]);
}
