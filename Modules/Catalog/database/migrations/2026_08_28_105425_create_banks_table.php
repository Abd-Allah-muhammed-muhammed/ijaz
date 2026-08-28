<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('banks', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('bank_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('bank_id')->constrained('banks')->cascadeOnDelete();
            $table->string('locale')->index();
            $table->string('name');
            $table->string('normalized_name')->invisible()->nullable()->index();
            $table->index(['bank_id', 'locale']);
            $table->index(['normalized_name', 'locale']);
            $table->unique(['bank_id', 'locale']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bank_translations');
        Schema::dropIfExists('banks');
    }
};
