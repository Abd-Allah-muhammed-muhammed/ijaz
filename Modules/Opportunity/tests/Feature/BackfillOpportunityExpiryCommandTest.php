<?php

use App\Models\User;
use Illuminate\Support\Carbon;
use Modules\Opportunity\Models\Opportunity;

test('backfill command sets expires_at for opportunities with a null value', function () {
    Carbon::setTestNow('2026-08-05 12:00:00');

    $recent = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => User::factory(),
        'expires_at' => null,
        'created_at' => now()->subDays(2),
    ]);

    $stale = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => User::factory(),
        'expires_at' => null,
        'created_at' => now()->subDays(30),
    ]);

    $this->artisan('opportunities:backfill-expiry')
        ->assertSuccessful();

    $recent->refresh();
    $stale->refresh();

    expect($recent->expires_at->equalTo($recent->created_at->copy()->addDays(Opportunity::DEFAULT_DURATION_DAYS)))->toBeTrue()
        ->and($stale->expires_at->equalTo(now()->addDays(Opportunity::DEFAULT_DURATION_DAYS)))->toBeTrue()
        ->and($stale->expires_at->isFuture())->toBeTrue();

    Carbon::setTestNow();
});

test('backfill command does not touch opportunities that already have expires_at set', function () {
    Carbon::setTestNow('2026-08-05 12:00:00');

    $existingExpiresAt = now()->addDays(3);
    $withExpiry = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => User::factory(),
        'expires_at' => $existingExpiresAt,
    ]);

    $withoutExpiry = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => User::factory(),
        'expires_at' => null,
        'created_at' => now()->subDay(),
    ]);

    $this->artisan('opportunities:backfill-expiry')
        ->assertSuccessful();

    $withExpiry->refresh();
    $withoutExpiry->refresh();

    expect($withExpiry->expires_at->equalTo($existingExpiresAt))->toBeTrue()
        ->and($withoutExpiry->expires_at)->not->toBeNull()
        ->and($withoutExpiry->expires_at->equalTo(
            $withoutExpiry->created_at->copy()->addDays(Opportunity::DEFAULT_DURATION_DAYS)
        ))->toBeTrue();

    Carbon::setTestNow();
});

test('backfill command dry-run does not write', function () {
    $opportunity = Opportunity::factory()->create([
        'author_type' => User::class,
        'author_id' => User::factory(),
        'expires_at' => null,
    ]);

    $this->artisan('opportunities:backfill-expiry', ['--dry-run' => true])
        ->assertSuccessful();

    expect($opportunity->fresh()->expires_at)->toBeNull();
});
