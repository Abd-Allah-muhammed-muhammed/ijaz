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
        Schema::create('property_advisements', static function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('normalized_title')->invisible()->nullable()->index();
            $table->text('description');
            if (Schema::getConnection()->getDriverName() === 'mysql') {
                $table->text('normalized_description')->invisible()->nullable()->fullText();
            } else {
                $table->text('normalized_description')->nullable();
            }
            $table->string('image')->nullable();
            $table->string('status');
            $table->string('operation');
            $table->string('facade');
            $table->string('street_width');
            $table->string('street_type');
            $table->morphs('user');
            $table->integer('age');
            $table->integer('area');
            $table->decimal('price', 15, 2);
            $table->boolean('show_price')->default(true);
            $table->integer('bedrooms_count');
            $table->integer('bathrooms_count');
            $table->integer('halls_count');
            $table->string('phone');
            $table->string('license')->nullable()->index();
            $table->json('options')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->string('address')->nullable();
            $table->foreignId('property_type_id')->constrained('property_types')->cascadeOnDelete();
            $table->foreignId('city_id')->constrained('cities')->cascadeOnDelete();
            $table->foreignId('region_id')->constrained('regions')->cascadeOnDelete();
            $table->foreignId('category_id')->constrained('propertiy_categories')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_advisements');
    }
};
