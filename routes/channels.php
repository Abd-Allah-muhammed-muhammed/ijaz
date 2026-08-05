<?php

use App\Models\Admin;
use App\Models\Provider;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Modules\Chat\Http\Resources\ChatUserResource;
use Modules\Chat\Models\Conversation;
use Modules\Chat\Models\System;
use Modules\Marketplace\Models\Category;

Broadcast::channel('provider-{id}', static function (Provider $user, int $id) {
    return (int) $user->id === $id;
});

Broadcast::channel('user-{id}', static function (User $user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('admin-{id}', static function (Admin $user, $id) {
    return (int) $user->id === (int) $id;
});

Broadcast::channel('online', static function ($user) {
    // Presence auth must return a plain array — JsonResource objects can serialize incorrectly.
    return ChatUserResource::make($user)->resolve();
});

Broadcast::channel('public', static function ($user) {
    return true;
});
Broadcast::channel('systems.{id}', static function ($user, $id) {
    return $user instanceof Admin;
});
Broadcast::channel('chats.{chat}', static function ($user, Conversation $chat) {
    if ($chat->user1_type === System::class && $user instanceof Admin) {
        return ChatUserResource::make($user)->resolve();
    }
    if ($chat->user1()->is($user) || $chat->user2()->is($user)) {
        return ChatUserResource::make($user)->resolve();
    }

    return false;
});

Broadcast::channel('category.{category}', static function (Provider $user, Category $category) {
    return $user->categories()->where('categories.id', $category->id)->exists();
});
