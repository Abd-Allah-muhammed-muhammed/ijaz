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
        Schema::create('provider_types', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
        });
        Schema::create('provider_type_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('provider_type_id')->constrained()->onDelete('cascade');
            $table->string('locale')->index();
            $table->string('name');
            $table->index(['provider_type_id', 'locale']);
            $table->unique(['provider_type_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('provider_type_translations');
        Schema::dropIfExists('provider_types');
    }
};
