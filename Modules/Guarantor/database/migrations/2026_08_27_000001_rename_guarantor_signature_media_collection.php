<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Modules\Guarantor\Models\GuarantorRequest;

return new class extends Migration
{
    /**
     * Local-dev hygiene: rename legacy GuarantorRequest media collection
     * `signature` → `requester_signature` (pre-launch; no production ceremony).
     */
    public function up(): void
    {
        DB::table('media')
            ->where('model_type', GuarantorRequest::class)
            ->where('collection_name', 'signature')
            ->update(['collection_name' => 'requester_signature']);
    }

    public function down(): void
    {
        DB::table('media')
            ->where('model_type', GuarantorRequest::class)
            ->where('collection_name', 'requester_signature')
            ->update(['collection_name' => 'signature']);
    }
};
