<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('verification_codes');
        Schema::dropIfExists('register_verification_codes');

        Schema::create('otps', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('purpose');
            $table->nullableUuidMorphs('subject');
            $table->string('phone')->nullable();
            $table->string('token');
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->unique(['subject_type', 'subject_id', 'purpose'], 'otps_subject_purpose_unique');
            $table->unique(['phone', 'purpose'], 'otps_phone_purpose_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('otps');

        Schema::create('verification_codes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('type');
            $table->longText('token');
            $table->nullableUuidMorphs('user');
            $table->dateTime('expire_at');
            $table->boolean('expiration_activated')->default(true);
            $table->timestamps();
        });

        Schema::create('register_verification_codes', function (Blueprint $table) {
            $table->id();
            $table->string('queryable');
            $table->string('token');
            $table->timestamp('expires_at');
            $table->timestamps();
        });
    }
};
