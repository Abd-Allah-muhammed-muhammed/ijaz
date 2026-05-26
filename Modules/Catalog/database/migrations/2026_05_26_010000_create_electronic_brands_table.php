<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electronic_brands', function (Blueprint $table) {
            $table->id();
            $table->string('image')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('electronic_brand_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('electronic_brand_id')->constrained('electronic_brands')->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('name');
            $table->string('normalized_name')->invisible()->nullable()->index();
            $table->index(['electronic_brand_id', 'locale']);
            $table->index(['normalized_name', 'locale']);
            $table->unique(['electronic_brand_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronic_brand_translations');
        Schema::dropIfExists('electronic_brands');
    }
};
