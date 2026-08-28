<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('guarantor_company_details', function (Blueprint $table) {
            $table->foreignId('requester_bank_id')->nullable()->after('requester_iban')->constrained('banks')->nullOnDelete();
            $table->foreignId('counterparty_bank_id')->nullable()->after('counterparty_iban')->constrained('banks')->nullOnDelete();
            $table->text('terms_notes')->nullable()->after('counterparty_bank_id');
        });
    }

    public function down(): void
    {
        Schema::table('guarantor_company_details', function (Blueprint $table) {
            $table->dropConstrainedForeignId('requester_bank_id');
            $table->dropConstrainedForeignId('counterparty_bank_id');
            $table->dropColumn('terms_notes');
        });
    }
};
