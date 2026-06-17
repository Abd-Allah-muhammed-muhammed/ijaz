<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guarantor_installments', static function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('guarantor_request_id')
                ->constrained('guarantor_requests')
                ->cascadeOnDelete();

            $table->unsignedInteger('order');
            $table->decimal('amount', 10, 2);
            $table->date('due_date');

            $table->string('status')->default('pending');

            $table->timestamp('paid_at')->nullable();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('overdue_notified_at')->nullable();

            $table->timestamps();

            $table->unique(['guarantor_request_id', 'order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guarantor_installments');
    }
};
