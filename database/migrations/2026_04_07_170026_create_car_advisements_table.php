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
        Schema::create('car_advisements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('normalized_title')->invisible()->nullable()->index();
            $table->text('description');
            $table->text('normalized_description')->invisible()->nullable()->fullText();
            $table->string('image')->nullable();
            $table->string('status');
            $table->string('operation');
            $table->string('usage_status');
            $table->morphs('user');
            $table->foreignId('car_brand_id')->constrained('car_brands')->cascadeOnDelete();
            $table->foreignId('car_type_id')->constrained('car_types')->cascadeOnDelete();
            $table->foreignId('car_category_id')->constrained('car_categories')->cascadeOnDelete();
            $table->integer('year');
            $table->integer('mileage');
            $table->string('transmission');
            $table->string('fuel_type');
            $table->string('engine_size')->nullable();
            $table->string('color');
            $table->decimal('price', 15, 2);
            $table->boolean('show_price')->default(true);
            $table->string('phone');
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('address')->nullable();
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
        Schema::dropIfExists('car_advisements');
    }
};
