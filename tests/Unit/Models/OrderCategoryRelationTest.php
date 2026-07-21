<?php

use App\Enums\CategoryFeesTypeEnum;
use App\Models\Order;
use Modules\Marketplace\Models\Category;
use Modules\Marketplace\Models\Skill;

/**
 * Regression lock: Order↔Category/Skill relations must resolve to Marketplace
 * models after extraction (was App\Models\Category Class-not-found).
 */
it('resolves order category and skills to marketplace models', function () {
    $category = Category::factory()->create([
        'fees' => 25.0,
        'fees_type' => CategoryFeesTypeEnum::FIXED,
    ]);

    $skill = Skill::query()->create([
        'category_id' => $category->id,
        'translations' => [
            'en' => ['title' => 'Order Skill EN'],
            'ar' => ['title' => 'Order Skill AR'],
            'ur' => ['title' => 'Order Skill UR'],
            'hi' => ['title' => 'Order Skill HI'],
        ],
    ]);

    $order = Order::factory()->create(['category_id' => $category->id]);
    $order->skills()->attach($skill->id);

    $order->load(['category', 'skills']);

    expect($order->category)->toBeInstanceOf(Category::class)
        ->and($order->skills->first())->toBeInstanceOf(Skill::class)
        ->and($order->skills->first()->id)->toBe($skill->id);
});

it('computes category getFees via order relation for fixed and percentage fees', function () {
    $fixed = Category::factory()->create([
        'fees' => 40.0,
        'fees_type' => CategoryFeesTypeEnum::FIXED,
    ]);

    $percentage = Category::factory()->create([
        'fees' => 10.0,
        'fees_type' => CategoryFeesTypeEnum::PERCENTAGE,
    ]);

    $fixedOrder = Order::factory()->create(['category_id' => $fixed->id]);
    $percentageOrder = Order::factory()->create(['category_id' => $percentage->id]);

    expect($fixedOrder->category->getFees(200.0))->toBe(40.0)
        ->and($percentageOrder->category->getFees(200.0))->toBe(20.0);
});

it('inherits getFees from parent when fees_type is inherited', function () {
    $parent = Category::factory()->create([
        'fees' => 15.0,
        'fees_type' => CategoryFeesTypeEnum::FIXED,
    ]);

    $child = Category::factory()->create([
        'parent_id' => $parent->id,
        'fees' => 0,
        'fees_type' => CategoryFeesTypeEnum::INHERITED,
    ]);

    $order = Order::factory()->create(['category_id' => $child->id]);

    expect($order->category->getFees(100.0))->toBe(15.0);
});
