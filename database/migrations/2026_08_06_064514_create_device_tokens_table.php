<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Step 1 of 3 — schema only.
 *
 * Production sequence:
 * 1. Run this migration (create device_tokens).
 * 2. Run `php artisan device-tokens:backfill-from-player-id` (review the report).
 * 3. Confirm 100% coverage, then run the drop-player_id migration.
 *
 * Do NOT combine backfill or column drop into this file.
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
    }

    public function down(): void
    {
        Schema::dropIfExists('device_tokens');
    }
};
