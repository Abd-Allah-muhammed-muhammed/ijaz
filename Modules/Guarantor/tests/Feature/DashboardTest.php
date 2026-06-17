<?php

use App\Models\Admin;
use Illuminate\Support\Facades\Notification;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Guarantor\Enums\GuarantorStatusEnum;
use Modules\Guarantor\Enums\GuarantorTypeEnum;
use Modules\Guarantor\Enums\InstallmentStatusEnum;
use Modules\Guarantor\Http\Controllers\Dashboard\GuarantorController as DashboardGuarantorController;
use Modules\Guarantor\Models\GuarantorInstallment;
use Modules\Guarantor\Models\GuarantorRequest;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Notification::fake();
});

function createGuarantorDashboardAdmin(array $permissions = ['show guarantors', 'manage guarantors']): Admin
{
    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'admin',
        ]);
    }

    $admin = Admin::query()->create([
        'name' => 'Guarantor Dashboard Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    $admin->givePermissionTo($permissions);

    return $admin;
}

function withoutGuarantorDashboardLocaleMiddleware(): void
{
    test()->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    test()->withoutVite();
}

test('admin can list guarantors', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin(['show guarantors']);
    GuarantorRequest::factory()->count(2)->create();

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardGuarantorController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Guarantor/Index')
            ->has('rows.data', 2)
            ->has('stats')
        );
});

test('admin can view guarantor details', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin(['show guarantors']);
    $guarantorRequest = GuarantorRequest::factory()->create();

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardGuarantorController::class, 'show'], $guarantorRequest))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Guarantor/Show')
            ->has('guarantorRequest')
            ->where('guarantorRequest.id', $guarantorRequest->id)
        );
});

test('admin can filter by status', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin(['show guarantors']);

    GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::New]);
    $inProgress = GuarantorRequest::factory()->inProgress()->create(['title' => 'Filter Status Target']);

    $this->actingAs($admin, 'admin')
        ->get(route('dashboard.guarantor.index', [
            'status' => GuarantorStatusEnum::InProgress->value,
            'search' => 'Filter Status Target',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $inProgress->id)
        );
});

test('admin can filter by type', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin(['show guarantors']);

    GuarantorRequest::factory()->create(['type' => GuarantorTypeEnum::Individual]);
    $company = GuarantorRequest::factory()->company()->create(['title' => 'Filter Type Target']);

    $this->actingAs($admin, 'admin')
        ->get(route('dashboard.guarantor.index', [
            'type' => GuarantorTypeEnum::Company->value,
            'search' => 'Filter Type Target',
        ]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $company->id)
        );
});

test('admin can change status with reason', function () {
    $admin = createGuarantorDashboardAdmin(['show guarantors', 'manage guarantors']);
    $guarantorRequest = GuarantorRequest::factory()->create(['status' => GuarantorStatusEnum::New]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardGuarantorController::class, 'show'], $guarantorRequest))
        ->post(action([DashboardGuarantorController::class, 'updateStatus'], $guarantorRequest), [
            'status' => GuarantorStatusEnum::Approved->value,
            'reason' => 'Approved by admin review',
            'notes' => 'Verified documents',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($guarantorRequest->fresh()->status)->toBe(GuarantorStatusEnum::Approved);
});

test('admin can release installment', function () {
    $admin = createGuarantorDashboardAdmin(['show guarantors', 'manage guarantors']);
    $guarantorRequest = GuarantorRequest::factory()->company()->inProgress()->create(['amount' => 1000, 'fees' => 10]);
    $installment = GuarantorInstallment::factory()->for($guarantorRequest, 'guarantorRequest')->paid()->create([
        'order' => 1,
        'amount' => 500,
    ]);

    $requester = $guarantorRequest->requester;
    $requester->wallet->update(['pending_credit' => 500, 'balance' => 0]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardGuarantorController::class, 'show'], $guarantorRequest))
        ->post(action([DashboardGuarantorController::class, 'releaseInstallment'], [
            'guarantorRequest' => $guarantorRequest,
            'installment' => $installment,
        ]))
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($installment->fresh()->status)->toBe(InstallmentStatusEnum::Released);
});

test('admin can delete guarantor request', function () {
    $admin = createGuarantorDashboardAdmin(['show guarantors']);
    $guarantorRequest = GuarantorRequest::factory()->create();

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardGuarantorController::class, 'index']))
        ->delete(action([DashboardGuarantorController::class, 'destroy'], $guarantorRequest))
        ->assertRedirect(route('dashboard.guarantor.index'))
        ->assertSessionHas('success');

    expect(GuarantorRequest::withTrashed()->find($guarantorRequest->id)?->trashed())->toBeTrue();
});

test('non-admin cannot access dashboard', function () {
    withoutGuarantorDashboardLocaleMiddleware();
    $admin = createGuarantorDashboardAdmin([]);

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardGuarantorController::class, 'index']))
        ->assertForbidden();
});
