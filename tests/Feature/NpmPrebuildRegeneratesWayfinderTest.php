<?php

use Illuminate\Support\Facades\Artisan;
use Modules\Chat\Enums\ChatEventEnum;
use Modules\Chat\Http\Controllers\Provider\OrderChatController;
use Modules\Orders\Http\Controllers\Dashboard\OrderController;

test('npm prebuild regenerates wayfinder and js enums before vite build', function () {
    $package = json_decode(
        (string) file_get_contents(base_path('package.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($package['scripts']['prebuild'] ?? null)
        ->toBeString()
        ->toContain('wayfinder:generate')
        ->toContain('make:js-enums');
});

test('provider and admin order chat expose typing routes', function () {
    expect(action(
        [OrderChatController::class, 'typing'],
        ['conversation' => '00000000-0000-0000-0000-000000000001'],
    ))->toContain('/typing/');

    expect(action(
        [OrderController::class, 'conversationTyping'],
        ['order' => '00000000-0000-0000-0000-000000000001'],
    ))->toContain('/conversation-typing');
});

test('generated Chat.ts ChatEventEnum.Typing is typing so Echo listens for .typing not .undefined', function () {
    expect(ChatEventEnum::Typing->value)->toBe('typing');

    Artisan::call('make:js-enums');

    $chatTs = (string) file_get_contents(resource_path('js/Enums/Chat.ts'));

    expect($chatTs)
        ->toContain('export const ChatEventEnum')
        ->toContain('Typing: "typing"');

    // Mirror the frontend template: `.${ChatEventEnum.Typing}` must become `.typing`.
    expect('.'.ChatEventEnum::Typing->value)->toBe('.typing');
});
