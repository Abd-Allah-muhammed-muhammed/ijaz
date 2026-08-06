<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Creates polymorphic device_tokens, backfills from users.player_id automatically,
 * then drops player_id.
 *
 * Rollback: recreates player_id and restores the first token per user (best-effort).
 * Prefer a single migration so deploy + migrate is one atomic step for mobile
 * (no dual-read period required).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_tokens', function (Blueprint $table) {
            $table->id();
            $table->morphs('tokenable');
            $table->string('token', 512)->unique();
            $table->string('platform')->nullable();
            $table->string('device_name')->nullable();
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();
        });

        if (Schema::hasColumn('users', 'player_id')) {
            $now = now();
            $userClass = (new User)->getMorphClass();

            DB::table('users')
                ->whereNotNull('player_id')
                ->where('player_id', '!=', '')
                ->orderBy('id')
                ->chunkById(200, function ($users) use ($userClass, $now): void {
                    $rows = [];

                    foreach ($users as $user) {
                        $rows[] = [
                            'tokenable_type' => $userClass,
                            'tokenable_id' => $user->id,
                            'token' => $user->player_id,
                            'platform' => null,
                            'device_name' => null,
                            'last_used_at' => $user->updated_at ?? $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    // Ignore duplicates if the same FCM token appears on multiple users —
                    // first wins; later registerDeviceToken will reassign ownership.
                    DB::table('device_tokens')->insertOrIgnore($rows);
                });

            Schema::table('users', function (Blueprint $table) {
                $table->dropColumn('player_id');
            });
        }
    }

    public function down(): void
    {
        if (! Schema::hasColumn('users', 'player_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('player_id')->nullable()->after('remember_token');
            });
        }

        $userClass = (new User)->getMorphClass();

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

        Schema::dropIfExists('device_tokens');
    }
};
