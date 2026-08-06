<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Step 3 of 3 — drop users.player_id AFTER backfill is verified.
 *
 * DO NOT run this as part of the same deploy that only creates device_tokens.
 * Production sequence:
 * 1. migrate create_device_tokens
 * 2. php artisan device-tokens:backfill-from-player-id  (+ review report / count check)
 * 3. THEN migrate this file (or full migrate once backfill is complete)
 *
 * Safety: aborts if any non-null player_id has no matching device_tokens row
 * for that same User. Documentation alone is not trusted.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'player_id')) {
            return;
        }

        $userClass = (new User)->getMorphClass();

        $unmigrated = DB::table('users')
            ->whereNotNull('player_id')
            ->where('player_id', '!=', '')
            ->whereNotExists(function ($query) use ($userClass): void {
                $query->select(DB::raw(1))
                    ->from('device_tokens')
                    ->whereColumn('device_tokens.token', 'users.player_id')
                    ->where('device_tokens.tokenable_type', $userClass)
                    ->whereColumn('device_tokens.tokenable_id', 'users.id');
            })
            ->count();

        if ($unmigrated > 0) {
            throw new RuntimeException(
                "Refusing to drop users.player_id: {$unmigrated} user(s) still have a non-null player_id ".
                'with no matching device_tokens row. Run `php artisan device-tokens:backfill-from-player-id` '.
                'and verify the report before retrying this migration.'
            );
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('player_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'player_id')) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('player_id')->nullable()->after('remember_token');
        });

        // Best-effort restore: first device token per user → player_id.
        $userClass = (new User)->getMorphClass();

        if (! Schema::hasTable('device_tokens')) {
            return;
        }

        $tokens = DB::table('device_tokens')
            ->where('tokenable_type', $userClass)
            ->orderBy('id')
            ->get()
            ->unique('tokenable_id');

        foreach ($tokens as $token) {
            DB::table('users')
                ->where('id', $token->tokenable_id)
                ->update(['player_id' => $token->token]);
        }
    }
};
