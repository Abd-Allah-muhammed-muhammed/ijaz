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
        // disalbel foreign key checks
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        Schema::create('orders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('expected_time')->nullable();
            $table->decimal('budget_start', 12, 2);
            $table->decimal('budget_end', 12, 2);
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained()->noActionOnDelete();
            $table->decimal('price', 12, 2)->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('order_status_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignuuid('order_id')->constrained()->cascadeOnDelete();
            $table->string('status');
            $table->timestamps();
        });

        Schema::create('order_offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignuuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('provider_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('category_id')->constrained()->noActionOnDelete();
            $table->decimal('price', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('status');
            $table->timestamps();
        });
        Schema::create('order_offers_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignuuid('order_id')->constrained()->cascadeOnDelete();
            $table->foreignuuid('order_offer_id')->constrained()->cascadeOnDelete();
            $table->decimal('price', 12, 2)->nullable();
            $table->text('description')->nullable();
            $table->string('status');
            $table->timestamps();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreignuuid('accepted_offer_id')->nullable()->constrained('order_offers')->cascadeOnDelete();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
