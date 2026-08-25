<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payout_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('operation_type');
            $table->char('operation_id', 36);
            $table->morphs('recipient');
            $table->decimal('amount', 12, 2);
            $table->string('status');
            $table->string('gateway_reference')->nullable();
            $table->foreignId('processed_by_admin_id')->nullable()->constrained('admins')->nullOnDelete();
            $table->text('failure_reason')->nullable();
            $table->timestamps();

            $table->index(['operation_type', 'operation_id']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payout_requests');
    }
};
