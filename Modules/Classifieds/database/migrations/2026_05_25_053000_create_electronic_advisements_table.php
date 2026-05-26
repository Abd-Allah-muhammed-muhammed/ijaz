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
        Schema::create('electronic_advisements', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('normalized_title')->invisible()->nullable()->index();
            $table->text('description');
            $table->text('normalized_description')->invisible()->nullable()->fullText();
            $table->string('image')->nullable();
            $table->string('status');
            $table->string('condition');
            $table->string('color')->nullable();
            $table->decimal('price', 15, 2);
            $table->boolean('show_price')->default(true);
            $table->string('phone')->nullable();
            $table->string('address')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->morphs('user');
            $table->foreignId('device_category_id')->constrained('device_categories')->cascadeOnDelete();
            $table->foreignId('electronic_brand_id')->nullable()->constrained('electronic_brands')->nullOnDelete();
            $table->string('model_name')->nullable();
            $table->string('storage')->nullable();
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
        Schema::dropIfExists('electronic_advisements');
    }
};
