<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guarantor_requests', static function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->string('type');

            $table->uuidMorphs('requester');
            $table->uuidMorphs('counterparty');

            $table->string('title');
            $table->text('description');
            $table->decimal('amount', 10, 2);
            $table->decimal('fees', 10, 2)->default(10);

            if (DB::getDriverName() !== 'sqlite') {
                $table->decimal('total', 10, 2)->storedAs('amount + fees');
            } else {
                $table->decimal('total', 10, 2)->default(0);
            }

            $table->string('status')->default('pending_admin');

            $table->string('project_type')->nullable();
            $table->string('requester_signature')->nullable();

            $table->text('cancellation_reason')->nullable();
            $table->text('admin_notes')->nullable();

            $table->timestamp('overdue_at')->nullable();
            $table->timestamp('ended_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamp('refunded_at')->nullable();

            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guarantor_requests');
    }
};
