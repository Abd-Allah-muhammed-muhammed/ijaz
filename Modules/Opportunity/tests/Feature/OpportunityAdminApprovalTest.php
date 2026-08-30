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
use Modules\Opportunity\Http\Controllers\Api\V1\CommentController;
use Modules\Opportunity\Http\Controllers\Api\V1\OfferController;
use Modules\Opportunity\Http\Controllers\Api\V1\OpportunityController;
use Modules\Opportunity\Http\Controllers\Dashboard\OpportunityController as DashboardOpportunityController;
use Modules\Opportunity\Models\Opportunity;
use Modules\Opportunity\Models\OpportunityComment;
use Modules\Opportunity\Models\OpportunityOffer;
use Modules\Opportunity\Notifications\OpportunityAdminApprovedNotification;
use Modules\Opportunity\Notifications\OpportunityAdminRejectedNotification;
use Modules\Opportunity\Notifications\OpportunityPendingReviewNotification;
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

test('a pending_admin opportunity notifies admins with manage opportunities permission (mirrors GuarantorPendingReviewNotification)', function () {
    Notification::fake();

    $manageAdmin = createOpportunityAdminApprovalAdmin(['manage opportunities']);
    $otherManageAdmin = createOpportunityAdminApprovalAdmin(['manage opportunities']);
    $viewOnlyAdmin = createOpportunityAdminApprovalAdmin(['show opportunities']);

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson(action([OpportunityController::class, 'store']), [
        'title' => 'Needs admin eyes',
        'description' => 'Should fan out pending-review notifications.',
        'budget' => 900,
    ])->assertSuccessful();

    Notification::assertSentTo($manageAdmin, OpportunityPendingReviewNotification::class);
    Notification::assertSentTo($otherManageAdmin, OpportunityPendingReviewNotification::class);
    Notification::assertNotSentTo($viewOnlyAdmin, OpportunityPendingReviewNotification::class);
});

test('author can delete/withdraw their own pending_admin opportunity', function () {
    $author = User::factory()->create();
    Sanctum::actingAs($author);

    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::PendingAdmin,
    ]);

    $this->deleteJson(action([OpportunityController::class, 'destroy'], $opportunity))
        ->assertSuccessful()
        ->assertJsonPath('message', __('opportunity.deleted_successfully'));

    expect(Opportunity::query()->find($opportunity->id))->toBeNull();
});

test('author can delete/withdraw their own rejected_by_admin opportunity', function () {
    $author = User::factory()->create();
    Sanctum::actingAs($author);

    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::RejectedByAdmin,
    ]);

    $this->deleteJson(action([OpportunityController::class, 'destroy'], $opportunity))
        ->assertSuccessful()
        ->assertJsonPath('message', __('opportunity.deleted_successfully'));

    expect(Opportunity::query()->find($opportunity->id))->toBeNull();
});

test('author can resubmit a rejected_by_admin opportunity, transitioning it back to pending_admin', function () {
    Notification::fake();

    $author = User::factory()->create();
    Sanctum::actingAs($author);

    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::RejectedByAdmin,
        'title' => 'Fixed after rejection',
    ]);

    $this->postJson(action([OpportunityController::class, 'resubmit'], $opportunity))
        ->assertSuccessful()
        ->assertJsonPath('data.status.value', OpportunityStatusEnum::PendingAdmin->value);

    expect($opportunity->fresh()->status)->toBe(OpportunityStatusEnum::PendingAdmin);
});

test('resubmitting requires the opportunity to currently be rejected_by_admin — cannot resubmit from any other status', function () {
    $author = User::factory()->create();
    Sanctum::actingAs($author);

    foreach ([
        OpportunityStatusEnum::PendingAdmin,
        OpportunityStatusEnum::New,
        OpportunityStatusEnum::OfferAccepted,
    ] as $status) {
        $opportunity = Opportunity::factory()->create([
            'author_type' => User::class,
            'author_id' => $author->id,
            'status' => $status,
        ]);

        $this->postJson(action([OpportunityController::class, 'resubmit'], $opportunity))
            ->assertForbidden()
            ->assertJsonPath('message', __('opportunity.unauthorized'));

        expect($opportunity->fresh()->status)->toBe($status);
    }
});

test('resubmitting notifies admins again, same as original creation', function () {
    Notification::fake();

    $manageAdmin = createOpportunityAdminApprovalAdmin(['manage opportunities']);
    $viewOnlyAdmin = createOpportunityAdminApprovalAdmin(['show opportunities']);

    $author = User::factory()->create();
    Sanctum::actingAs($author);

    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::RejectedByAdmin,
    ]);

    $this->postJson(action([OpportunityController::class, 'resubmit'], $opportunity))
        ->assertSuccessful();

    Notification::assertSentTo($manageAdmin, OpportunityPendingReviewNotification::class);
    Notification::assertNotSentTo($viewOnlyAdmin, OpportunityPendingReviewNotification::class);
});

test('GET .../comments on a pending_admin or rejected_by_admin opportunity is restricted to the author only, matching show\'s existing visibility rule — non-author gets 404 or empty, pick and test the safer consistent behavior', function () {
    $author = User::factory()->create();
    $stranger = User::factory()->create();

    foreach ([OpportunityStatusEnum::PendingAdmin, OpportunityStatusEnum::RejectedByAdmin] as $status) {
        $opportunity = Opportunity::factory()->create([
            'author_type' => User::class,
            'author_id' => $author->id,
            'status' => $status,
        ]);
        OpportunityComment::factory()->create([
            'opportunity_id' => $opportunity->id,
            'author_type' => User::class,
            'author_id' => $author->id,
        ]);

        auth()->forgetGuards();

        $this->getJson(action([CommentController::class, 'index'], ['opportunity' => $opportunity->id]))
            ->assertNotFound();

        Sanctum::actingAs($stranger);
        $this->getJson(action([CommentController::class, 'index'], ['opportunity' => $opportunity->id]))
            ->assertNotFound();

        Sanctum::actingAs($author);
        $this->getJson(action([CommentController::class, 'index'], ['opportunity' => $opportunity->id]))
            ->assertSuccessful()
            ->assertJsonPath('data.total', 1);
    }
});

test('POST .../comments (create) is rejected on a pending_admin or rejected_by_admin opportunity for non-authors', function () {
    $author = User::factory()->create();
    $stranger = User::factory()->create();

    foreach ([OpportunityStatusEnum::PendingAdmin, OpportunityStatusEnum::RejectedByAdmin] as $status) {
        $opportunity = Opportunity::factory()->create([
            'author_type' => User::class,
            'author_id' => $author->id,
            'status' => $status,
        ]);

        Sanctum::actingAs($stranger);
        $this->postJson(action([CommentController::class, 'store'], $opportunity), [
            'body' => 'Should not leak',
        ])->assertNotFound();

        expect(OpportunityComment::query()->where('opportunity_id', $opportunity->id)->count())->toBe(0);
    }
});

test('existing comment behavior on new/offer_accepted opportunities is completely unaffected — regression', function () {
    $opportunity = Opportunity::factory()->create([
        'status' => OpportunityStatusEnum::New,
    ]);
    OpportunityComment::factory()->count(2)->create([
        'opportunity_id' => $opportunity->id,
    ]);

    $this->getJson(action([CommentController::class, 'index'], $opportunity))
        ->assertSuccessful()
        ->assertJsonPath('data.total', 2);

    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $this->postJson(action([CommentController::class, 'store'], $opportunity), [
        'body' => 'Still works on public opportunities',
    ])->assertSuccessful()
        ->assertJsonPath('data.body', 'Still works on public opportunities');

    $accepted = Opportunity::factory()->create([
        'status' => OpportunityStatusEnum::OfferAccepted,
    ]);
    OpportunityComment::factory()->create([
        'opportunity_id' => $accepted->id,
    ]);

    $this->getJson(action([CommentController::class, 'index'], $accepted))
        ->assertSuccessful()
        ->assertJsonPath('data.total', 1);

    $this->postJson(action([CommentController::class, 'store'], $accepted), [
        'body' => 'Comment on accepted opportunity',
    ])->assertSuccessful();
});

test('rejecting an opportunity persists the reason on the opportunity record', function () {
    withoutOpportunityAdminApprovalLocaleMiddleware();
    Notification::fake();

    $admin = createOpportunityAdminApprovalAdmin();
    $author = User::factory()->create();
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::PendingAdmin,
        'rejection_reason' => null,
    ]);

    $this->actingAs($admin, 'admin')
        ->post(action([DashboardOpportunityController::class, 'rejectByAdmin'], $opportunity), [
            'reason' => 'Missing budget details',
        ])
        ->assertRedirect();

    expect($opportunity->fresh()->rejection_reason)->toBe('Missing budget details')
        ->and($opportunity->fresh()->status)->toBe(OpportunityStatusEnum::RejectedByAdmin);
});

test('the persisted rejection_reason is exposed on OpportunityResource (mobile API)', function () {
    $author = User::factory()->create();
    Sanctum::actingAs($author);

    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::RejectedByAdmin,
        'rejection_reason' => 'Policy violation',
    ]);

    $this->getJson(action([OpportunityController::class, 'show'], $opportunity))
        ->assertSuccessful()
        ->assertJsonPath('data.rejection_reason', 'Policy violation');
});

test('the persisted rejection_reason is exposed on OpportunityDashboardResource (admin Show props)', function () {
    withoutOpportunityAdminApprovalLocaleMiddleware();

    $admin = createOpportunityAdminApprovalAdmin(['show opportunities']);
    $opportunity = Opportunity::factory()->create([
        'status' => OpportunityStatusEnum::RejectedByAdmin,
        'rejection_reason' => 'Incomplete description',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardOpportunityController::class, 'show'], $opportunity))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Opportunity/Show')
            ->where('opportunity.rejection_reason', 'Incomplete description')
        );
});

test('resubmitting an opportunity clears the previous rejection_reason', function () {
    Notification::fake();

    $author = User::factory()->create();
    Sanctum::actingAs($author);

    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => $author->id,
        'status' => OpportunityStatusEnum::RejectedByAdmin,
        'rejection_reason' => 'Needs clearer title',
    ]);

    $this->postJson(action([OpportunityController::class, 'resubmit'], $opportunity))
        ->assertSuccessful()
        ->assertJsonPath('data.status.value', OpportunityStatusEnum::PendingAdmin->value)
        ->assertJsonPath('data.rejection_reason', null);

    expect($opportunity->fresh()->rejection_reason)->toBeNull();
});

test('an opportunity that was never rejected has a null rejection_reason', function () {
    $opportunity = Opportunity::factory()->create([
        'status' => OpportunityStatusEnum::New,
    ]);

    $this->getJson(action([OpportunityController::class, 'show'], $opportunity))
        ->assertSuccessful()
        ->assertJsonPath('data.rejection_reason', null);

    expect($opportunity->fresh()->rejection_reason)->toBeNull();
});

test('dashboard stats (active/ended/cancelled) are now computed server-wide via the repository, matching pending_admin/total — not limited to the current page', function () {
    withoutOpportunityAdminApprovalLocaleMiddleware();

    $admin = createOpportunityAdminApprovalAdmin(['show opportunities']);

    Opportunity::factory()->count(2)->create(['status' => OpportunityStatusEnum::New]);
    Opportunity::factory()->create(['status' => OpportunityStatusEnum::OfferAccepted]);
    Opportunity::factory()->create(['status' => OpportunityStatusEnum::InProgress]);
    Opportunity::factory()->count(3)->create(['status' => OpportunityStatusEnum::Ended]);
    Opportunity::factory()->count(2)->create(['status' => OpportunityStatusEnum::Cancelled]);
    Opportunity::factory()->count(4)->create(['status' => OpportunityStatusEnum::PendingAdmin]);

    $this->actingAs($admin, 'admin')
        ->get(action([DashboardOpportunityController::class, 'index'], ['per_page' => 1]))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/Opportunity/Index')
            ->has('rows.data', 1)
            ->where('stats.total', 13)
            ->where('stats.pending_admin', 4)
            ->where('stats.active', 4)
            ->where('stats.ended', 3)
            ->where('stats.cancelled', 2)
        );
});
