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
        Schema::table('top_up_requests', function (Blueprint $table) {
            $table->index('status', 'top_up_requests_status_index');
        });

        Schema::table('withdraw_requests', function (Blueprint $table) {
            $table->index('status', 'withdraw_requests_status_index');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->index(['wallet_id', 'created_at'], 'wallet_transactions_wallet_created_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('top_up_requests', function (Blueprint $table) {
            $table->dropIndex('top_up_requests_status_index');
        });

        Schema::table('withdraw_requests', function (Blueprint $table) {
            $table->dropIndex('withdraw_requests_status_index');
        });

        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->dropIndex('wallet_transactions_wallet_created_index');
        });
    }
};
