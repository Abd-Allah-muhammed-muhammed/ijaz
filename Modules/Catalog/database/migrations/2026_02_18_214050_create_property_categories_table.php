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
        Schema::create('property_categories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()
                ->constrained('property_categories', indexName: 'property_categories_id_index')
                ->cascadeOnDelete();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('property_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('property_category_id')->constrained('property_categories')->cascadeOnDelete();
            $table->string('locale');
            $table->string('title');
            $table->string('normalized_title')->invisible()->nullable()->index();
            $table->index(['property_category_id', 'locale'], 'pro_cat_trans_pro_cat_id_locale_index');
            $table->index(['normalized_title', 'locale'], 'pro_cat_trans_normalized_title_locale_index');
            $table->unique(['property_category_id', 'locale'], 'pro_cat_trans_pro_cat_id_locale_unique');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('property_category_translations');
        Schema::dropIfExists('property_categories');
    }
};
