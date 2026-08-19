<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Payout\Enums\PayoutStatusEnum;
use Modules\Payout\Http\Controllers\Dashboard\PayoutRequestController;
use Modules\Payout\Models\PayoutRequest;

test('a payout can be confirmed by a different admin holding "confirm payouts" permission, moving it to completed with a gateway_reference recorded', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $maker = createPayoutDashboardAdmin(['edit withdrawRequests']);
    $checker = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($checker, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-12345',
            'proof_image' => payoutTransferProofImage(),
        ])
        ->assertRedirect(route('dashboard.payout-requests.index'))
        ->assertSessionHas('success');

    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::Completed)
        ->and($payout->gateway_reference)->toBe('BANK-TXN-12345')
        ->and($payout->processed_by_admin_id)->toBe($checker->id);
});

test('the SAME admin who triggered the payouts source operation (the withdraw approver) cannot confirm that payout — even if they hold "confirm payouts" permission', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $maker = createPayoutDashboardAdmin(['confirm payouts', 'edit withdrawRequests']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($maker, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-99999',
            'proof_image' => payoutTransferProofImage(),
        ])
        ->assertRedirect()
        ->assertSessionHas('error', __('payout.maker_cannot_confirm'));

    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::Pending)
        ->and($payout->gateway_reference)->toBeNull();
});

test('confirming a payout without "confirm payouts" permission is forbidden regardless of who the admin is', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $maker = createPayoutDashboardAdmin(['edit withdrawRequests']);
    $otherAdmin = createPayoutDashboardAdmin(['edit withdrawRequests']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($otherAdmin, 'admin')
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-00001',
            'proof_image' => payoutTransferProofImage(),
        ])
        ->assertForbidden();
});

test('confirming requires a gateway_reference — cannot mark completed without one', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $maker = createPayoutDashboardAdmin(['edit withdrawRequests']);
    $checker = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($checker, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'proof_image' => payoutTransferProofImage(),
        ])
        ->assertSessionHasErrors(['gateway_reference']);

    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::Pending);
});

test('a payout already completed cannot be confirmed again', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $maker = createPayoutDashboardAdmin(['edit withdrawRequests']);
    $checker = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Completed,
        'gateway_reference' => 'BANK-TXN-EXISTING',
        'processed_by_admin_id' => $checker->id,
    ]);

    $anotherChecker = createPayoutDashboardAdmin(['confirm payouts']);

    $this->actingAs($anotherChecker, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-RETRY',
            'proof_image' => payoutTransferProofImage(),
        ])
        ->assertRedirect()
        ->assertSessionHas('error', __('payout.already_completed'));

    expect($payout->fresh()->gateway_reference)->toBe('BANK-TXN-EXISTING');
});

test('an admin can mark a payout as failed with a required failure_reason, which allows it to be retried (confirmed) later by any eligible admin', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $maker = createPayoutDashboardAdmin(['edit withdrawRequests']);
    $checker = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($checker, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'fail'], ['payoutRequest' => $payout->id]), [
            'failure_reason' => 'Bank rejected transfer — invalid IBAN',
        ])
        ->assertRedirect(route('dashboard.payout-requests.index'))
        ->assertSessionHas('success');

    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::Failed)
        ->and($payout->failure_reason)->toBe('Bank rejected transfer — invalid IBAN');

    $this->actingAs($checker, 'admin')
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-RETRY-OK',
            'proof_image' => payoutTransferProofImage(),
        ])
        ->assertRedirect(route('dashboard.payout-requests.index'))
        ->assertSessionHas('success');

    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::Completed)
        ->and($payout->gateway_reference)->toBe('BANK-TXN-RETRY-OK')
        ->and($payout->failure_reason)->toBeNull()
        ->and($payout->processed_by_admin_id)->toBe($checker->id);
});

test('confirming a payout requires a proof_image file, not just a gateway_reference', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $maker = createPayoutDashboardAdmin(['edit withdrawRequests']);
    $checker = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($checker, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-NO-PROOF',
        ])
        ->assertSessionHasErrors(['proof_image']);

    $payout->refresh();

    expect($payout->status)->toBe(PayoutStatusEnum::Pending)
        ->and($payout->getMedia('transfer_proof'))->toBeEmpty();
});

test('confirming a payout without proof_image is rejected with a validation error', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $maker = createPayoutDashboardAdmin(['edit withdrawRequests']);
    $checker = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($checker, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-MISSING-FILE',
        ])
        ->assertSessionHasErrors(['proof_image']);
});

test('confirming a payout stores the proof image on the transfer_proof media collection', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $maker = createPayoutDashboardAdmin(['edit withdrawRequests']);
    $checker = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($checker, 'admin')
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-WITH-PROOF',
            'proof_image' => payoutTransferProofImage(),
        ])
        ->assertRedirect(route('dashboard.payout-requests.index'));

    $payout->refresh();
    $media = $payout->getFirstMedia('transfer_proof');

    expect($media)->not->toBeNull()
        ->and($media->collection_name)->toBe('transfer_proof')
        ->and($media->disk)->toBe('public');
});

test('a retried confirm (after a failed attempt) replaces the previous proof image, not appends', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $maker = createPayoutDashboardAdmin(['edit withdrawRequests']);
    $checker = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Failed,
        'failure_reason' => 'Previous attempt rejected',
    ]);

    $payout->addMedia(UploadedFile::fake()->image('old-proof.jpg', 100, 100))
        ->toMediaCollection('transfer_proof', 'public');

    $oldMediaId = $payout->getFirstMedia('transfer_proof')?->id;

    $this->actingAs($checker, 'admin')
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-REPLACED-PROOF',
            'proof_image' => UploadedFile::fake()->image('new-proof.jpg', 120, 120),
        ])
        ->assertRedirect(route('dashboard.payout-requests.index'));

    $payout->refresh();
    $allMedia = $payout->getMedia('transfer_proof');

    expect($allMedia)->toHaveCount(1)
        ->and($allMedia->first()?->id)->not->toBe($oldMediaId)
        ->and($allMedia->first()?->file_name)->toBe('new-proof.jpg');
});

test('the dashboard index can filter/list completed payouts', function () {
    withoutPayoutDashboardLocaleMiddleware();

    $admin = createPayoutDashboardAdmin(['confirm payouts']);

    $pending = PayoutRequest::factory()->create(['status' => PayoutStatusEnum::Pending]);
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
            ->has('rows.data', 2)
            ->where('rows.data', fn ($rows) => collect($rows)->pluck('id')->sort()->values()->all()
                === collect([$pending->id, $failed->id])->sort()->values()->all())
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

test('non-image / oversized files are rejected by validation (mimes + max size, mirroring Chat\'s limits)', function () {
    withoutPayoutDashboardLocaleMiddleware();
    Storage::fake('public');

    $maker = createPayoutDashboardAdmin(['edit withdrawRequests']);
    $checker = createPayoutDashboardAdmin(['confirm payouts']);

    $payout = PayoutRequest::factory()->create([
        'maker_admin_id' => $maker->id,
        'status' => PayoutStatusEnum::Pending,
    ]);

    $this->actingAs($checker, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-BAD-FILE',
            'proof_image' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ])
        ->assertSessionHasErrors(['proof_image']);

    $this->actingAs($checker, 'admin')
        ->from(action([PayoutRequestController::class, 'index']))
        ->put(action([PayoutRequestController::class, 'confirm'], ['payoutRequest' => $payout->id]), [
            'gateway_reference' => 'BANK-TXN-TOO-LARGE',
            'proof_image' => UploadedFile::fake()->image('huge.jpg')->size(6000),
        ])
        ->assertSessionHasErrors(['proof_image']);
});
