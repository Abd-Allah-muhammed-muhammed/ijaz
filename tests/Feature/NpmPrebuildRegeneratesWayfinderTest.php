<?php

use Modules\Chat\Http\Controllers\Provider\OrderChatController;
use Modules\Orders\Http\Controllers\Dashboard\OrderController;

test('npm prebuild regenerates wayfinder before vite build', function () {
    $package = json_decode(
        (string) file_get_contents(base_path('package.json')),
        true,
        512,
        JSON_THROW_ON_ERROR,
    );

    expect($package['scripts']['prebuild'] ?? null)
        ->toBeString()
        ->toContain('wayfinder:generate');
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
