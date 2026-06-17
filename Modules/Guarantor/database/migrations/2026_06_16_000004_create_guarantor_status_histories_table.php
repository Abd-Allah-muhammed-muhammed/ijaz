<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guarantor_status_histories', static function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('guarantor_request_id')
                ->constrained('guarantor_requests')
                ->cascadeOnDelete();

            $table->uuidMorphs('actor');

            $table->string('from_status')->nullable();
            $table->string('to_status');

            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guarantor_status_histories');
    }
};
