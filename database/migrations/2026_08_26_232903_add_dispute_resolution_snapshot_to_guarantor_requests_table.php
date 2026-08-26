<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guarantor_requests', static function (Blueprint $table) {
            $table->unsignedTinyInteger('dispute_requester_percentage')->nullable()->after('cancellation_reason');
            $table->decimal('dispute_requester_amount', 12, 2)->nullable()->after('dispute_requester_percentage');
            $table->decimal('dispute_counterparty_amount', 12, 2)->nullable()->after('dispute_requester_amount');
        });
    }

    public function down(): void
    {
        Schema::table('guarantor_requests', static function (Blueprint $table) {
            $table->dropColumn([
                'dispute_requester_percentage',
                'dispute_requester_amount',
                'dispute_counterparty_amount',
            ]);
        });
    }
};
