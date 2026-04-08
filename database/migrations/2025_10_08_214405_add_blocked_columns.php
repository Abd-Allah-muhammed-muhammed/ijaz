<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dateTime('blocked_at')->nullable();
            $table->dateTime('blocked_until')->nullable();
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->dateTime('blocked_at')->nullable();
            $table->dateTime('blocked_until')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('blocked_at');
            $table->dropColumn('blocked_until');
        });

        Schema::table('providers', function (Blueprint $table) {
            $table->dropColumn('blocked_at');
            $table->dropColumn('blocked_until');
        });
    }
};
