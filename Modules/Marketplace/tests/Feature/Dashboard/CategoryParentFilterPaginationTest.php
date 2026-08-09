<?php

use Modules\Marketplace\Http\Controllers\Dashboard\CategoryController;
use Modules\Marketplace\Models\Category;

/**
 * Documents why the Admin Categories subcategory badge must drop `page` when setting
 * parent_id: prams is $request->all() (includes page), and an out-of-range page for the
 * filtered child set returns an empty table ("No matching records found").
 */
test('filtering categories by parent_id on page 1 returns children while an out-of-range page is empty', function (): void {
    withoutGeoDashboardLocaleMiddleware();
    $admin = createGeoDashboardAdmin(['show categories']);

    $parent = Category::factory()->create();
    $childA = Category::factory()->create(['parent_id' => $parent->id]);
    $childB = Category::factory()->create(['parent_id' => $parent->id]);
    Category::factory()->create(['parent_id' => null]);

    $this->actingAs($admin, 'admin')
        ->get(action([CategoryController::class, 'index'], [
            'parent_id' => $parent->id,
            'per_page' => 10,
            'page' => 1,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Categories/Index')
            ->has('rows.data', 2)
            ->where('prams.parent_id', (string) $parent->id)
            ->where('prams.page', '1')
            ->where('rows.data.0.id', $childA->id)
            ->where('rows.data.1.id', $childB->id)
        );

    $this->actingAs($admin, 'admin')
        ->get(action([CategoryController::class, 'index'], [
            'parent_id' => $parent->id,
            'per_page' => 10,
            'page' => 2,
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Categories/Index')
            ->has('rows.data', 0)
            ->where('prams.parent_id', (string) $parent->id)
            ->where('prams.page', '2')
        );
});
