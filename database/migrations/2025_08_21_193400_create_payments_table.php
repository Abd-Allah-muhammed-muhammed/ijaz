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
        Schema::create('payments', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('transaction_id')->nullable();
            $table->string('driver');
            $table->json('request')->nullable();
            $table->json('response')->nullable();
            $table->decimal('amount', 12, 2);
            $table->string('status');
            $table->string('message')->nullable();
            $table->string('url')->nullable();
            $table->morphs('user');
            $table->nullableUuidMorphs('product');
            $table->unique(['transaction_id', 'driver'], 'unique_transaction_driver');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
