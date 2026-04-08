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
        Schema::create('job_offers', static function (Blueprint $table) {
            $table->id();
            $table->morphs('user');
            $table->string('title');
            $table->text('description')->nullable();
            $table->decimal('budget_start', 10, 2);
            $table->decimal('budget_end', 10, 2);
            $table->dateTime('expired_at')->index();
            $table->string('contact_number');
            $table->foreignId('city_id')->constrained()->cascadeOnDelete();
            $table->foreignId('region_id')->constrained()->cascadeOnDelete();
            $table->foreignId('nationality_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_offers');
    }
};
