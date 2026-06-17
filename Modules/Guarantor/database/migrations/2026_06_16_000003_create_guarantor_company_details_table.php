<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guarantor_company_details', static function (Blueprint $table) {
            $table->uuid('id')->primary();

            $table->foreignUuid('guarantor_request_id')
                ->constrained('guarantor_requests')
                ->cascadeOnDelete()
                ->unique();

            $table->string('company_name');
            $table->string('commercial_register');
            $table->foreignId('region_id')->nullable()->constrained('regions')->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained('cities')->nullOnDelete();

            $table->string('authorized_name');
            $table->string('authorized_id_number');
            $table->string('authorization_type');

            $table->text('requester_account_holder');
            $table->text('requester_iban');

            $table->text('counterparty_account_holder');
            $table->text('counterparty_iban')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guarantor_company_details');
    }
};
