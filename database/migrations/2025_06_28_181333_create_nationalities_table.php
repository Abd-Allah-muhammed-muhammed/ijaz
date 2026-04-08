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
        Schema::create('nationalities', function (Blueprint $table) {
            $table->id();
            $table->string('icon')->nullable();
            $table->string('code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
        Schema::create('nationality_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('nationality_id')->constrained('nationalities')->onDelete('cascade');
            $table->string('locale')->index();
            $table->string('name');
            $table->string('normalized_name')->invisible()->nullable()->index();
            $table->index(['nationality_id', 'locale']);
            $table->index(['normalized_name', 'locale']);
            $table->unique(['nationality_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('nationalities');
    }
};
