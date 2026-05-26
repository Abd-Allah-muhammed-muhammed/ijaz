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
        Schema::create('institute_advisements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('normalized_title')->invisible()->nullable()->index();
            $table->text('description');
            $table->text('normalized_description')->invisible()->nullable()->fullText();
            $table->text('goals')->nullable();
            $table->text('payment_notes')->nullable();
            $table->string('image')->nullable();
            $table->string('status');
            $table->decimal('price', 15, 2)->nullable();
            $table->decimal('discounted_price', 15, 2)->nullable();
            $table->string('type');
            $table->string('study_type');
            $table->string('study_level')->nullable();
            $table->integer('days_count')->nullable();
            $table->integer('hours_count')->nullable();
            $table->string('phone')->nullable();
            $table->string('website')->nullable();
            $table->string('registration_url')->nullable();
            $table->string('course_url')->nullable();
            $table->string('quality_url')->nullable();
            $table->string('address')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->date('registration_start')->nullable();
            $table->date('registration_end')->nullable();
            $table->date('study_start')->nullable();
            $table->date('study_end')->nullable();
            $table->morphs('user');
            $table->foreignId('specialization_id')->constrained('specializations')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->json('options')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('institute_advisements');
    }
};
