<?php

use App\Models\DeviceToken;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

beforeEach(function () {
    if (! Schema::hasColumn('users', 'player_id')) {
        Schema::table('users', function (Blueprint $table) {
            $table->string('player_id')->nullable();
        });
    }
});

afterEach(function () {
    if (Schema::hasColumn('users', 'player_id')) {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('player_id');
        });
    }
});

test('backfill command dry-run reports the correct count without writing anything', function () {
    $user = User::factory()->create();
    DB::table('users')->where('id', $user->id)->update(['player_id' => 'dry-run-token']);

    $before = DeviceToken::query()->count();

    $this->artisan('device-tokens:backfill-from-player-id', ['--dry-run' => true])
        ->expectsOutputToContain('Would migrate: 1')
        ->expectsOutputToContain('Dry run — no rows written.')
        ->assertSuccessful();

    expect(DeviceToken::query()->count())->toBe($before)
        ->and(DeviceToken::query()->where('token', 'dry-run-token')->exists())->toBeFalse();
});

test('backfill command migrates player_id values into device_tokens', function () {
    $user = User::factory()->create();
    DB::table('users')->where('id', $user->id)->update([
        'player_id' => 'legacy-player-token',
        'updated_at' => now()->subDay(),
    ]);

    $this->artisan('device-tokens:backfill-from-player-id')
        ->expectsOutputToContain('Migrated: 1')
        ->assertSuccessful();

    expect(DeviceToken::query()
        ->where('tokenable_id', $user->id)
        ->where('tokenable_type', (new User)->getMorphClass())
        ->where('token', 'legacy-player-token')
        ->exists())->toBeTrue();
});

test('backfill command is safe to re-run without creating duplicates', function () {
    $user = User::factory()->create();
    DB::table('users')->where('id', $user->id)->update(['player_id' => 'rerun-token']);

    $this->artisan('device-tokens:backfill-from-player-id')->assertSuccessful();
    $this->artisan('device-tokens:backfill-from-player-id')
        ->expectsOutputToContain('Migrated: 0')
        ->expectsOutputToContain('Skipped (already migrated): 1')
        ->assertSuccessful();

    expect(DeviceToken::query()->where('token', 'rerun-token')->count())->toBe(1);
});

test('drop-player-id migration aborts safely if unmigrated player_id values remain', function () {
    $user = User::factory()->create();
    DB::table('users')->where('id', $user->id)->update(['player_id' => 'unmigrated-token']);

    DeviceToken::query()->where('token', 'unmigrated-token')->delete();

    $migration = require database_path('migrations/2026_08_06_064515_drop_player_id_from_users_table.php');

    expect(fn () => $migration->up())
        ->toThrow(\RuntimeException::class, 'Refusing to drop users.player_id');

    expect(Schema::hasColumn('users', 'player_id'))->toBeTrue()
        ->and(DB::table('users')->where('id', $user->id)->value('player_id'))->toBe('unmigrated-token');
});
