<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedTinyInteger('dispute_user_percentage')->nullable()->after('cancellation_reason');
            $table->decimal('dispute_user_amount', 12, 2)->nullable()->after('dispute_user_percentage');
            $table->decimal('dispute_provider_amount', 12, 2)->nullable()->after('dispute_user_amount');
        });

        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->string('from_status')->nullable()->after('status');
            $table->nullableMorphs('actor');
            $table->string('actor_name')->nullable()->after('actor_type');
            $table->string('reason', 1000)->nullable()->after('actor_name');
            $table->text('notes')->nullable()->after('reason');
        });
    }

    public function down(): void
    {
        Schema::table('order_status_histories', function (Blueprint $table) {
            $table->dropMorphs('actor');
            $table->dropColumn(['from_status', 'actor_name', 'reason', 'notes']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn([
                'dispute_user_percentage',
                'dispute_user_amount',
                'dispute_provider_amount',
            ]);
        });
    }
};
