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
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->morphs('user');
            $table->decimal('balance', 15, 2)->default(0);
            $table->decimal('pending_credit', 15, 2)->default(0);
            $table->decimal('pending_debit', 15, 2)->default(0);
            $table->decimal('total_earning', 15, 2)->default(0);
            $table->decimal('total_spent', 15, 2)->default(0);
            $table->timestamps();
        });
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->morphs('user');
            $table->decimal('credit', 15, 2)->default(0);
            $table->decimal('debit', 15, 2)->default(0);
            $table->decimal('balance_before', 15, 2)->default(0);
            $table->decimal('balance_after', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->uuidmorphs('operation');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('wallet_transactions');
        Schema::dropIfExists('wallets');
    }
};
