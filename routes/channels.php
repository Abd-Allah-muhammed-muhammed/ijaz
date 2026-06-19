<?php

use App\Models\Admin;
use App\Models\Category;
use App\Models\Conversation;
use App\Models\Provider;
use App\Models\System;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;
use Modules\Chat\Http\Resources\ChatUserResource;

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
    return ChatUserResource::make($user);
});

Broadcast::channel('public', static function ($user) {
    return true;
});
Broadcast::channel('systems.{id}', static function ($user, $id) {
    return $user instanceof Admin;
});
Broadcast::channel('chats.{chat}', static function ($user, Conversation $chat) {
    if ($chat->user1_type === System::class && $user instanceof Admin) {
        return ChatUserResource::make($user);
    }
    if ($chat->user1()->is($user) || $chat->user2()->is($user)) {
        return ChatUserResource::make($user);
    }

    return false;
});

Broadcast::channel('category.{category}', static function (Provider $user, Category $category) {
    return $user->categories()->where('categories.id', $category->id)->exists();
});
