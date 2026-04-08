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
        Schema::create('conversation_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('conversation_message_id')->constrained()->cascadeOnDelete();
            $table->string('type');
            $table->string('filename');
            $table->string('path');
            $table->string('store')->default('public');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('conversation_attachments');
    }
};
