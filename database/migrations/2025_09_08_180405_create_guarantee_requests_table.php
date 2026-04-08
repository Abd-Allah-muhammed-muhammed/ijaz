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
        Schema::create('guarantee_requests', static function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->morphs('user');
            $table->morphs('provider');
            $table->text('description');
            $table->string('status');
            $table->decimal('amount', 10, 2);
            $table->decimal('fees', 10, 2);
            $table->decimal('total', 10, 2)->storedAs('amount + fees');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('guarantee_requests');
    }
};
