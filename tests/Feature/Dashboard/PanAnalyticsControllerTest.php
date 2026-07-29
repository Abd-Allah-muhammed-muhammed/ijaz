<?php

use App\Models\Admin;
use Illuminate\Support\Facades\DB;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Spatie\Permission\Models\Permission;

function withoutPanAnalyticsLocaleMiddleware(): void
{
    test()->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    test()->withoutVite();
}

function createPanAnalyticsAdmin(array $permissions = [
    'show panAnalytics',
    'export panAnalytics',
    'delete panAnalytics',
]): Admin
{
    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'admin',
        ]);
    }

    $admin = Admin::query()->create([
        'name' => 'Pan Analytics Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    $admin->givePermissionTo($permissions);

    return $admin;
}

function seedPanAnalyticsRows(): void
{
    DB::table('pan_analytics')->insert([
        [
            'name' => 'home-page',
            'impressions' => 100,
            'hovers' => 20,
            'clicks' => 10,
        ],
        [
            'name' => 'submit-btn',
            'impressions' => 50,
            'hovers' => 15,
            'clicks' => 40,
        ],
        [
            'name' => 'checkout-form-step',
            'impressions' => 30,
            'hovers' => 5,
            'clicks' => 5,
        ],
        [
            'name' => 'logo-icon',
            'impressions' => 20,
            'hovers' => 0,
            'clicks' => 1,
        ],
    ]);
}

beforeEach(function () {
    withoutPanAnalyticsLocaleMiddleware();
});

test('pan analytics index returns correct summary/categories/top10', function () {
    $admin = createPanAnalyticsAdmin();
    seedPanAnalyticsRows();

    $this->actingAs($admin, 'admin')
        ->get(route('dashboard.pan-analytics.index'))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/PanAnalytics/Index')
            ->where('summary.total_impressions', 200)
            ->where('summary.total_hovers', 40)
            ->where('summary.total_clicks', 56)
            ->where('summary.overall_engagement_rate', 48)
            ->where('categories.page', 1)
            ->where('categories.button', 1)
            ->where('categories.form', 1)
            ->where('categories.other', 1)
            ->has('topElements', 4)
            ->where('topElements.0.name', 'submit-btn')
            ->where('funnelData.impressions', 200)
            ->where('funnelData.hovers', 40)
            ->where('funnelData.clicks', 56)
        );
});

test('pan analytics export streams correct csv', function () {
    $admin = createPanAnalyticsAdmin();
    seedPanAnalyticsRows();

    $response = $this->actingAs($admin, 'admin')
        ->post(route('dashboard.pan-analytics.export'))
        ->assertSuccessful();

    $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $csv = $response->streamedContent();

    expect($csv)
        ->toContain('ID,"Element Name",Category,Impressions,Hovers,Clicks,"Engagement Rate (%)","Click Rate (%)"')
        ->toContain('home-page,page,100,20,10')
        ->toContain('submit-btn,button,50,15,40')
        ->toContain('checkout-form-step,form,30,5,5')
        ->toContain('logo-icon,other,20,0,1');
});
