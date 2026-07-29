<?php

use Modules\Orders\Http\Controllers\Dashboard\OrderController;
use Modules\Orders\Models\Order;

test('admin can search orders by title', function (): void {
    withoutOrdersLocaleMiddleware();

    $admin = createOrdersAdmin();

    $matching = Order::factory()->create([
        'title' => 'UniquePlumbingRepairRequest',
    ]);

    Order::factory()->create([
        'title' => 'UnrelatedElectricalJob',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([OrderController::class, 'index'], ['search' => 'UniquePlumbingRepairRequest']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Orders/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $matching->id)
        );
});
