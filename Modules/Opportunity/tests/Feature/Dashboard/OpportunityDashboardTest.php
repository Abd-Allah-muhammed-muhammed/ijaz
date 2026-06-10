<?php

use App\Models\Admin;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Opportunity\Http\Controllers\Dashboard\CommentController as DashboardCommentController;
use Modules\Opportunity\Http\Controllers\Dashboard\OfferController as DashboardOfferController;
use Modules\Opportunity\Http\Controllers\Dashboard\OpportunityController as DashboardOpportunityController;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;
use Modules\Opportunity\Models\OpportunityOffer;
use Spatie\Permission\Models\Permission;

function createDashboardAdmin(array $permissions = ['show opportunities', 'delete opportunities']): Admin
{
    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'admin',
        ]);
    }

    $admin = Admin::query()->create([
        'name' => 'Dashboard Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    $admin->givePermissionTo($permissions);

    return $admin;
}

function withoutDashboardLocaleMiddleware(): void
{
    test()->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    test()->withoutVite();
}

test('admin with permission can view opportunities dashboard index', function () {
    withoutDashboardLocaleMiddleware();
    $admin = createDashboardAdmin(['show opportunities']);
    Opportunity::factory()->count(2)->create();

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardOpportunityController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Opportunity/Index')
            ->has('rows.data', 2)
        );
});

test('admin with permission can view opportunity dashboard show page', function () {
    withoutDashboardLocaleMiddleware();
    $admin = createDashboardAdmin(['show opportunities']);
    $opportunity = Opportunity::factory()->create();

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardOpportunityController::class, 'show'], $opportunity))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Opportunity/Show')
            ->has('opportunity')
            ->where('opportunity.id', $opportunity->id)
        );
});

test('admin with delete permission can delete opportunity from dashboard', function () {
    $admin = createDashboardAdmin(['show opportunities', 'delete opportunities']);
    $opportunity = Opportunity::factory()->create();

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardOpportunityController::class, 'index']))
        ->delete(action([DashboardOpportunityController::class, 'destroy'], $opportunity))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(Opportunity::withTrashed()->find($opportunity->id)?->trashed())->toBeTrue();
});

test('admin with delete permission can delete offer from dashboard', function () {
    $admin = createDashboardAdmin(['show opportunities', 'delete opportunities']);
    $offer = OpportunityOffer::factory()->create();

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardOpportunityController::class, 'show'], $offer->opportunity_id))
        ->delete(action([DashboardOfferController::class, 'destroy'], $offer))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(OpportunityOffer::withTrashed()->find($offer->id)?->trashed())->toBeTrue();
});

test('admin with delete permission can delete comment from dashboard', function () {
    $admin = createDashboardAdmin(['show opportunities', 'delete opportunities']);
    $comment = OpportunityComment::factory()->create();

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardOpportunityController::class, 'show'], $comment->opportunity_id))
        ->delete(action([DashboardCommentController::class, 'destroy'], $comment))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect(OpportunityComment::withTrashed()->find($comment->id)?->trashed())->toBeTrue();
});

test('admin without show permission cannot access opportunities dashboard', function () {
    withoutDashboardLocaleMiddleware();
    $admin = createDashboardAdmin([]);

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardOpportunityController::class, 'index']))
        ->assertForbidden();
});
