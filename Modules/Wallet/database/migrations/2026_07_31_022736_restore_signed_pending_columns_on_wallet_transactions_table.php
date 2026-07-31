<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ledger rows for reverse/adjust pending holds store negative deltas
     * (see ReversePendingDebitAction / AdjustPendingAction). Those values
     * cannot be stored in unsigned columns.
     */
    public function up(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->decimal('pending_credit', 15, 2)->default(0)->change();
            $table->decimal('pending_debit', 15, 2)->default(0)->change();
        });
    }

    public function down(): void
    {
        Schema::table('wallet_transactions', function (Blueprint $table) {
            $table->unsignedBigInteger('pending_credit')->default(0)->change();
            $table->unsignedBigInteger('pending_debit')->default(0)->change();
        });
    }
};
