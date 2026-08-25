<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Http\Controllers\Dashboard\PayoutRequestController;
use Modules\Payout\Models\PayoutRequest;

// --- Submit ---

test('any eligible admin with "request payouts", including the original maker, can submit transfer proof, moving pending to submitted', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $maker = createPayoutDashboardAdmin(['request payouts', 'edit withdrawRequests']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($maker, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'submit'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-12345',
            'proof_image' => payoutTransferProofImage(),
        ])
        ->assertRedirect(route('dashboard.payout-requests.index'))
        ->assertSessionHas('success');

    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::Submitted)
        ->and($payout->gateway_reference)->toBe('BANK-TXN-12345')
        ->and($payout->submitted_by_admin_id)->toBe($maker->id)
        ->and($payout->failure_reason)->toBeNull()
        ->and($payout->getFirstMedia('transfer_proof'))->not->toBeNull();
});

test('submitting requires both gateway_reference and proof_image', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $admin = createPayoutDashboardAdmin(['request payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'submit'], ['payoutRequest' => $payout->id]), [
            'proof_image' => payoutTransferProofImage(),
        ])
        ->assertSessionHasErrors(['gateway_reference']);

    $this->actingAs($admin, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'submit'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-NO-PROOF',
        ])
        ->assertSessionHasErrors(['proof_image']);

    expect($payout->fresh()->status)->toBe(PayoutStatusEnum::Pending);
});

test('submitting without "request payouts" permission is forbidden', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $admin = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($admin, 'admin')
        ->put(action([PayoutRequestController::class, 'submit'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-00001',
            'proof_image' => payoutTransferProofImage(),
        ])
        ->assertForbidden();
});

test('a failed payout can be re-submitted, replacing the previous proof image', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $admin = createPayoutDashboardAdmin(['request payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Failed,
        'failure_reason' => 'Previous attempt rejected',
        'gateway_reference' => 'BANK-TXN-OLD',
    ]);

    $payout->addMedia(UploadedFile::fake()->image('old-proof.jpg', 100, 100))
        ->toMediaCollection('transfer_proof', 'public');

    $oldMediaId = $payout->getFirstMedia('transfer_proof')?->id;

    $this->actingAs($admin, 'admin')
        ->put(action([PayoutRequestController::class, 'submit'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-REPLACED-PROOF',
            'proof_image' => UploadedFile::fake()->image('new-proof.jpg', 120, 120),
        ])
        ->assertRedirect(route('dashboard.payout-requests.index'));

    $payout->refresh();
    $allMedia = $payout->getMedia('transfer_proof');

    expect($payout->status)->toBe(PayoutStatusEnum::Submitted)
        ->and($payout->gateway_reference)->toBe('BANK-TXN-REPLACED-PROOF')
        ->and($payout->failure_reason)->toBeNull()
        ->and($payout->submitted_by_admin_id)->toBe($admin->id)
        ->and($allMedia)->toHaveCount(1)
        ->and($allMedia->first()?->id)->not->toBe($oldMediaId)
        ->and($allMedia->first()?->file_name)->toBe('new-proof.jpg');
});

// --- Review approve ---

test('an admin different from submitted_by_admin_id can approve a submitted payout, moving it to completed', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $submitter = createPayoutDashboardAdmin(['request payouts']);
    $reviewer = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Submitted,
        'gateway_reference' => 'BANK-TXN-READY',
        'submitted_by_admin_id' => $submitter->id,
    ]);

    $payout->addMedia(payoutTransferProofImage())->toMediaCollection('transfer_proof', 'public');

    $this->actingAs($reviewer, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]))
        ->assertRedirect(route('dashboard.payout-requests.index'))
        ->assertSessionHas('success');

    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::Completed)
        ->and($payout->processed_by_admin_id)->toBe($reviewer->id)
        ->and($payout->failure_reason)->toBeNull()
        ->and($payout->gateway_reference)->toBe('BANK-TXN-READY');
});

test('the SAME admin who submitted the transfer proof cannot approve their own submission — even if they hold "confirm payouts"', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $submitter = createPayoutDashboardAdmin(['request payouts', 'confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Submitted,
        'gateway_reference' => 'BANK-TXN-SELF',
        'submitted_by_admin_id' => $submitter->id,
    ]);

    $this->actingAs($submitter, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]))
        ->assertRedirect()
        ->assertSessionHas('error', __('payout.submitter_cannot_review'));

    expect($payout->fresh()->status)->toBe(PayoutStatusEnum::Submitted);
});

test('approving requires "confirm payouts" permission', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $submitter = createPayoutDashboardAdmin(['request payouts']);
    $otherAdmin = createPayoutDashboardAdmin(['request payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Submitted,
        'gateway_reference' => 'BANK-TXN-PERM',
        'submitted_by_admin_id' => $submitter->id,
    ]);

    $this->actingAs($otherAdmin, 'admin')
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]))
        ->assertForbidden();
});

test('a completed payout cannot be approved again', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $submitter = createPayoutDashboardAdmin(['request payouts']);
    $reviewer = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Completed,
        'gateway_reference' => 'BANK-TXN-EXISTING',
        'submitted_by_admin_id' => $submitter->id,
        'processed_by_admin_id' => $reviewer->id,
    ]);

    $anotherReviewer = createPayoutDashboardAdmin(['confirm payouts']);

    $this->actingAs($anotherReviewer, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]))
        ->assertRedirect()
        ->assertSessionHas('error', __('payout.already_completed'));

    expect($payout->fresh()->processed_by_admin_id)->toBe($reviewer->id);
});

// --- Review reject ---

test('an admin different from submitted_by_admin_id can reject a submitted payout with a required reason, moving it to failed', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $submitter = createPayoutDashboardAdmin(['request payouts']);
    $reviewer = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Submitted,
        'gateway_reference' => 'BANK-TXN-REJECT',
        'submitted_by_admin_id' => $submitter->id,
    ]);

    $this->actingAs($reviewer, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'reject'], ['payoutRequest' => $payout->id]), [
            'failure_reason' => 'Receipt amount does not match payout',
        ])
        ->assertRedirect(route('dashboard.payout-requests.index'))
        ->assertSessionHas('success');

    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::Failed)
        ->and($payout->failure_reason)->toBe('Receipt amount does not match payout');
});

test('the SAME admin who submitted cannot reject their own submission', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $submitter = createPayoutDashboardAdmin(['request payouts', 'confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Submitted,
        'gateway_reference' => 'BANK-TXN-SELF-REJECT',
        'submitted_by_admin_id' => $submitter->id,
    ]);

    $this->actingAs($submitter, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'reject'], ['payoutRequest' => $payout->id]), [
            'failure_reason' => 'Trying to reject own submission',
        ])
        ->assertRedirect()
        ->assertSessionHas('error', __('payout.submitter_cannot_review'));

    expect($payout->fresh()->status)->toBe(PayoutStatusEnum::Submitted);
});

// --- Direct fail ---

test('an admin with "confirm payouts" can directly fail a pending payout (no submission yet) with a required reason, with no submitter/maker restriction', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $maker = createPayoutDashboardAdmin(['confirm payouts', 'edit withdrawRequests']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($maker, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'fail'], ['payoutRequest' => $payout->id]), [
            'failure_reason' => 'Bank rejected transfer — invalid IBAN',
        ])
        ->assertRedirect(route('dashboard.payout-requests.index'))
        ->assertSessionHas('success');

    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::Failed)
        ->and($payout->failure_reason)->toBe('Bank rejected transfer — invalid IBAN');
});

// --- Index / resource regressions ---

test('the dashboard index can filter/list submitted and completed payouts; default queue includes pending, submitted, and failed', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $admin = createPayoutDashboardAdmin(['confirm payouts']);

    $pending = PayoutRequest::factory()->create(['status' => PayoutStatusEnum::Pending]);
    $submitted = PayoutRequest::factory()->create(['status' => PayoutStatusEnum::Submitted]);
    $failed = PayoutRequest::factory()->create(['status' => PayoutStatusEnum::Failed]);
    $completed = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Completed,
        'gateway_reference' => 'BANK-TXN-DONE',
    ]);

    $this->actingAs($admin, 'admin')
        ->get(action([PayoutRequestController::class, 'index']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/PayoutRequests/Index')
            ->has('rows.data', 3)
            ->where('rows.data', fn ($rows) => collect($rows)->pluck('id')->sort()->values()->all()
                === collect([$pending->id, $submitted->id, $failed->id])->sort()->values()->all())
        );

    $this->actingAs($admin, 'admin')
        ->get(action([PayoutRequestController::class, 'index'], ['status' => 'submitted']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/PayoutRequests/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $submitted->id)
        );

    $this->actingAs($admin, 'admin')
        ->get(action([PayoutRequestController::class, 'index'], ['status' => 'completed']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->component('Dashboard/PayoutRequests/Index')
            ->has('rows.data', 1)
            ->where('rows.data.0.id', $completed->id)
        );
});

test('a completed payout row exposes a proof image URL in the resource response', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $admin = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Completed,
        'gateway_reference' => 'BANK-TXN-PROOF-URL',
    ]);

    $payout->addMedia(UploadedFile::fake()->image('proof.jpg', 80, 80))
        ->toMediaCollection('transfer_proof', 'public');

    $this->actingAs($admin, 'admin')
        ->get(action([PayoutRequestController::class, 'index'], ['status' => 'completed']))
        ->assertSuccessful()
        ->assertInertia(fn ($page) => $page
            ->where('rows.data.0.transfer_proof_url', fn ($url) => is_string($url) && $url !== '')
        );
});

test('non-image / oversized files are rejected by submit validation (mimes + max size, mirroring Chat\'s limits)', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $admin = createPayoutDashboardAdmin(['request payouts']);

    $payout = PayoutRequest::factory()->create([
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($admin, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'submit'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-BAD-FILE',
            'proof_image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors(['proof_image']);

    $this->actingAs($admin, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'submit'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-TOO-LARGE',
            'proof_image' => UploadedFile::fake()->image('huge.jpg')->size(6000),
        ])
        ->assertSessionHasErrors(['proof_image']);
});
