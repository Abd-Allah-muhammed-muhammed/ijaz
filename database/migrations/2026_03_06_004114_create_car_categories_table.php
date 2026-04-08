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
        Schema::create('car_categories', function (Blueprint $table) {
            $table->id();
            $table->string('icon')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('car_categories')->onDelete('cascade');
            $table->timestamps();
        });
        Schema::create('car_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('car_category_id')->constrained('car_categories')->onDelete('cascade');
            $table->string('locale')->index();
            $table->string('title');
            $table->string('normalized_title')->invisible()->nullable()->index();
            $table->index(['car_category_id', 'locale']);
            $table->index(['normalized_title', 'locale']);
            $table->unique(['car_category_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('car_category_translations');
        Schema::dropIfExists('car_categories');
    }
};
