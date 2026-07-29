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
        Schema::create('otp_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary(); // this IS the verification_id returned to clients
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('purpose'); // login | register — reuse OtpPurposeEnum values
            $table->unsignedTinyInteger('attempts_count')->default(0);
            $table->unsignedTinyInteger('max_attempts')->default(5);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index(['user_id', 'purpose']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('otp_sessions');
    }
};
