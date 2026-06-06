<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunity_offers', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('opportunity_id')->constrained('opportunities')->cascadeOnDelete();
            $table->morphs('author');
            $table->decimal('price', 10, 2);
            $table->text('description')->nullable();
            $table->string('status')->default('pending');
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::table('opportunities', static function (Blueprint $table) {
            $table->foreign('accepted_offer_id')
                ->references('id')
                ->on('opportunity_offers')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', static function (Blueprint $table) {
            $table->dropForeign(['accepted_offer_id']);
        });

        Schema::dropIfExists('opportunity_offers');
    }
};
