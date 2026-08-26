<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guarantor_installments', function (Blueprint $table) {
            $table->timestamp('escalated_at')->nullable()->after('overdue_notified_at');
        });
    }

    public function down(): void
    {
        Schema::table('guarantor_installments', function (Blueprint $table) {
            $table->dropColumn('escalated_at');
        });
    }
};
