<?php

use App\Models\Admin;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRedirectFilter;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationRoutes;
use Mcamara\LaravelLocalization\Middleware\LaravelLocalizationViewPath;
use Mcamara\LaravelLocalization\Middleware\LocaleSessionRedirect;
use Modules\Opportunity\Actions\Dashboard\AdminApproveOpportunityAction;
use Modules\Opportunity\Actions\Dashboard\AdminRejectOpportunityAction;
use Modules\Opportunity\Enums\OfferStatusEnum;
use Modules\Opportunity\Enums\OpportunityStatusEnum;
use Modules\Opportunity\Exceptions\OpportunityException;
use Modules\Opportunity\Http\Controllers\Api\V1\OfferController;
use Modules\Opportunity\Http\Controllers\Api\V1\OpportunityController;
use Modules\Opportunity\Http\Controllers\Dashboard\OpportunityController as DashboardOpportunityController;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityOffer;
use Modules\Opportunity\Notifications\OpportunityAdminApprovedNotification;
use Modules\Opportunity\Notifications\OpportunityAdminRejectedNotification;
use Spatie\Permission\Models\Permission;

function createOpportunityAdminApprovalAdmin(array $permissions = ['show opportunities', 'manage opportunities']): Admin
{
    foreach ($permissions as $permission) {
        Permission::firstOrCreate([
            'name' => $permission,
            'guard_name' => 'admin',
        ]);
    }

    $admin = Admin::query()->create([
        'name' => 'Opportunity Approval Admin',
        'phone' => fake()->unique()->phoneNumber(),
        'email' => fake()->unique()->safeEmail(),
        'password' => 'password',
        'language' => 'en',
    ]);

    $admin->givePermissionTo($permissions);

    return $admin;
}

function withoutOpportunityAdminApprovalLocaleMiddleware(): void
{
    test()->withoutMiddleware([
        LocaleSessionRedirect::class,
        LaravelLocalizationRedirectFilter::class,
        LaravelLocalizationRoutes::class,
        LaravelLocalizationViewPath::class,
    ]);
    test()->withoutVite();
}

test('creating an Opportunity now sets status to pending_admin, not new', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson(action([OpportunityController::class, 'store']), [
        'title' => 'Needs review before publish',
        'description' => 'Submitted opportunity awaiting admin approval.',
        'budget' => 1200,
    ])
        ->assertSuccessful()
        ->assertJsonPath('data.status.value', OpportunityStatusEnum::PendingAdmin->value);

    expect(Opportunity::query()->sole()->status)->toBe(OpportunityStatusEnum::PendingAdmin);
});

test('a pending_admin Opportunity does not appear in the public opportunities feed', function () {
    $pending = Opportunity::factory()->create([
        'status' => OpportunityStatusEnum::PendingAdmin,
        'title' => 'Pending should stay private',
    ]);
    $public = Opportunity::factory()->create([
        'status' => OpportunityStatusEnum::New,
        'title' => 'Public new opportunity',
    ]);

    $ids = collect($this->getJson(action([OpportunityController::class, 'all']))
        ->assertSuccessful()
        ->json('data.items'))
        ->pluck('id');

    expect($ids)->toContain($public->id)
        ->and($ids)->not->toContain($pending->id);
});

test('a rejected_by_admin Opportunity does not appear in the public opportunities feed', function () {
    $rejected = Opportunity::factory()->create([
        'status' => OpportunityStatusEnum::RejectedByAdmin,
    ]);
    $public = Opportunity::factory()->create([
        'status' => OpportunityStatusEnum::New,
    ]);

    $ids = collect($this->getJson(action([OpportunityController::class, 'all']))
        ->assertSuccessful()
        ->json('data.items'))
        ->pluck('id');

    expect($ids)->toContain($public->id)
        ->and($ids)->not->toContain($rejected->id);
});

test('admin approve transitions pending_admin -> new, and the opportunity now appears in the public feed', function () {
    withoutOpportunityAdminApprovalLocaleMiddleware();
    Notification::fake();

    $admin = createOpportunityAdminApprovalAdmin();
    $author = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::PendingAdmin,
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardOpportunityController::class, 'show'], $opportunity))
        ->post(action([DashboardOpportunityController::class, 'approveByAdmin'], $opportunity), [
            'notes' => 'Looks good',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($opportunity->fresh()->status)->toBe(OpportunityStatusEnum::New);

    $ids = collect($this->getJson(action([OpportunityController::class, 'all']))
        ->assertSuccessful()
        ->json('data.items'))
        ->pluck('id');

    expect($ids)->toContain($opportunity->id);
});

test('admin reject requires a reason, transitions pending_admin -> rejected_by_admin', function () {
    withoutOpportunityAdminApprovalLocaleMiddleware();
    Notification::fake();

    $admin = createOpportunityAdminApprovalAdmin();
    $author = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::PendingAdmin,
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardOpportunityController::class, 'show'], $opportunity))
        ->post(action([DashboardOpportunityController::class, 'rejectByAdmin'], $opportunity), [])
        ->assertSessionHasErrors('reason');

    expect($opportunity->fresh()->status)->toBe(OpportunityStatusEnum::PendingAdmin);

    $this->actingAs($admin, 'admin')
        ->from(action([DashboardOpportunityController::class, 'show'], $opportunity))
        ->post(action([DashboardOpportunityController::class, 'rejectByAdmin'], $opportunity), [
            'reason' => 'Incomplete description',
        ])
        ->assertRedirect()
        ->assertSessionHas('success');

    expect($opportunity->fresh()->status)->toBe(OpportunityStatusEnum::RejectedByAdmin);
});

test('approve/reject are rejected with a clear error if the opportunity is not currently pending_admin', function () {
    $admin = createOpportunityAdminApprovalAdmin();
    $opportunity = Opportunity::factory()->create([
        'status' => OpportunityStatusEnum::New,
    ]);

    expect(fn () => app(AdminApproveOpportunityAction::class)
        ->handle($opportunity, null, $admin))
        ->toThrow(OpportunityException::class, 'opportunity.status_transition_not_allowed');

    expect(fn () => app(AdminRejectOpportunityAction::class)
        ->handle($opportunity->fresh(), 'Too late', null, $admin))
        ->toThrow(OpportunityException::class, 'opportunity.status_transition_not_allowed');

    expect($opportunity->fresh()->status)->toBe(OpportunityStatusEnum::New);
});

test('approve/reject require the manage opportunities permission', function () {
    withoutOpportunityAdminApprovalLocaleMiddleware();

    $admin = createOpportunityAdminApprovalAdmin(['show opportunities']);
    $opportunity = Opportunity::factory()->create([
        'status' => OpportunityStatusEnum::PendingAdmin,
    ]);

    $this->actingAs($admin, 'admin')
        ->post(action([DashboardOpportunityController::class, 'approveByAdmin'], $opportunity))
        ->assertForbidden();

    $this->actingAs($admin, 'admin')
        ->post(action([DashboardOpportunityController::class, 'rejectByAdmin'], $opportunity), [
            'reason' => 'Not allowed without manage',
        ])
        ->assertForbidden();

    expect($opportunity->fresh()->status)->toBe(OpportunityStatusEnum::PendingAdmin);
});

test('the author is notified on approval and on rejection (with reason)', function () {
    withoutOpportunityAdminApprovalLocaleMiddleware();
    Notification::fake();

    $admin = createOpportunityAdminApprovalAdmin();
    $author = User::factory()->create();

    $toApprove = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::PendingAdmin,
    ]);
    $toReject = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::PendingAdmin,
    ]);

    $this->actingAs($admin, 'admin')
        ->post(action([DashboardOpportunityController::class, 'approveByAdmin'], $toApprove))
        ->assertRedirect();

    Notification::assertSentTo($author, OpportunityAdminApprovedNotification::class, function (OpportunityAdminApprovedNotification $notification) use ($toApprove) {
        return $notification->opportunity->is($toApprove);
    });

    $this->actingAs($admin, 'admin')
        ->post(action([DashboardOpportunityController::class, 'rejectByAdmin'], $toReject), [
            'reason' => 'Policy violation',
        ])
        ->assertRedirect();

    Notification::assertSentTo($author, OpportunityAdminRejectedNotification::class, function (OpportunityAdminRejectedNotification $notification) use ($toReject) {
        return $notification->opportunity->is($toReject)
            && $notification->reason === 'Policy violation';
    });
});

test('the author CAN view their own pending_admin or rejected_by_admin opportunity via show', function () {
    $author = User::factory()->create();
    Sanctum::actingAs($author);

    $pending = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::PendingAdmin,
    ]);
    $rejected = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::RejectedByAdmin,
    ]);

    $this->getJson(action([OpportunityController::class, 'show'], $pending))
        ->assertSuccessful()
        ->assertJsonPath('data.id', $pending->id);

    $this->getJson(action([OpportunityController::class, 'show'], $rejected))
        ->assertSuccessful()
        ->assertJsonPath('data.id', $rejected->id);
});

test('a non-author (authenticated or not) gets 404 viewing a pending_admin or rejected_by_admin opportunity by direct UUID/link', function () {
    $author = User::factory()->create();
    $other = User::factory()->create();

    $pending = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::PendingAdmin,
    ]);
    $rejected = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::RejectedByAdmin,
    ]);

    $this->getJson(action([OpportunityController::class, 'show'], $pending))
        ->assertNotFound();
    $this->getJson(action([OpportunityController::class, 'show'], $rejected))
        ->assertNotFound();

    Sanctum::actingAs($other);

    $this->getJson(action([OpportunityController::class, 'show'], $pending))
        ->assertNotFound();
    $this->getJson(action([OpportunityController::class, 'show'], $rejected))
        ->assertNotFound();
});

test('a non-author CAN still view a normal new/offer_accepted opportunity by direct link — regression, this restriction is status-specific, not a general lockdown', function () {
    $author = User::factory()->create();
    $other = User::factory()->create();

    $open = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::New,
    ]);
    $accepted = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::OfferAccepted,
    ]);

    $this->getJson(action([OpportunityController::class, 'show'], $open))
        ->assertSuccessful()
        ->assertJsonPath('data.id', $open->id);

    Sanctum::actingAs($other);

    $this->getJson(action([OpportunityController::class, 'show'], $accepted))
        ->assertSuccessful()
        ->assertJsonPath('data.id', $accepted->id);
});

test('existing offer/delete/renew/accept actions are completely unaffected once status is new — regression, zero changes needed there since new already meant the same thing as before', function () {
    $author = User::factory()->create();
    $offerer = User::factory()->create();

    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::New,
        'expires_at' => now()->addDays(7),
    ]);

    Sanctum::actingAs($offerer);
    $this->postJson(action([OfferController::class, 'store'], $opportunity), [
        'price' => 900,
        'description' => 'Happy to help with this.',
    ])->assertSuccessful()
        ->assertJsonPath('data.status.value', OfferStatusEnum::Pending->value);

    $offer = OpportunityOffer::query()->where('opportunity_id', $opportunity->id)->sole();

    Sanctum::actingAs($author);
    $this->postJson(action([OfferController::class, 'accept'], [
        'opportunity' => $opportunity->id,
        'offer' => $offer->id,
    ]))->assertSuccessful()
        ->assertJsonPath('data.status.value', OpportunityStatusEnum::OfferAccepted->value);

    $deletable = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::New,
    ]);

    $this->deleteJson(action([OpportunityController::class, 'destroy'], $deletable))
        ->assertSuccessful();

    expect(Opportunity::withTrashed()->find($deletable->id)?->trashed())->toBeTrue();

    $expired = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::Expired,
        'expires_at' => now()->subDay(),
    ]);

    $this->postJson(action([OpportunityController::class, 'renew'], $expired))
        ->assertSuccessful()
        ->assertJsonPath('data.status.value', OpportunityStatusEnum::New->value);
});

test('an opportunity that already existed as new before this change is completely unaffected — no migration/backfill touches existing rows', function () {
    $existing = Opportunity::factory()->create([
        'status' => OpportunityStatusEnum::New,
        'title' => 'Pre-existing public listing',
    ]);

    expect($existing->fresh()->status)->toBe(OpportunityStatusEnum::New);

    $ids = collect($this->getJson(action([OpportunityController::class, 'all']))
        ->assertSuccessful()
        ->json('data.items'))
        ->pluck('id');

    expect($ids)->toContain($existing->id);

    $this->getJson(action([OpportunityController::class, 'show'], $existing))
        ->assertSuccessful()
        ->assertJsonPath('data.status.value', OpportunityStatusEnum::New->value);
});
