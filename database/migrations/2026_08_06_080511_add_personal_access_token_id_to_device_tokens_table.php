<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Links each device_tokens row to the Sanctum personal access token that
 * registered it, so logout can clear the correct FCM registration with no
 * request body from mobile.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->foreignId('personal_access_token_id')
                ->nullable()
                ->after('tokenable_id')
                ->constrained('personal_access_tokens')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('device_tokens', function (Blueprint $table) {
            $table->dropConstrainedForeignId('personal_access_token_id');
        });
    }
};
