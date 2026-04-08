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
        Schema::create('withdraw_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->morphs('user'); // This will create user_id and user_type columns
            $table->decimal('amount', 12, 2); // Amount to be topped up
            $table->string('status');
            $table->foreignId('wallet_id')->nullable()->constrained('wallets')->nullOnDelete(); // Foreign key to the wallets table
            $table->text('admin_notes')->nullable(); // Additional notes or comments
            $table->text('user_notes')->nullable(); // Additional notes or comments
            $table->foreignId('admin_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('withdraw_requests');
    }
};
