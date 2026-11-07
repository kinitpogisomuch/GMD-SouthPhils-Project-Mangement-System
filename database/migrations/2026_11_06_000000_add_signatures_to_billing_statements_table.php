<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // uploaded e-signature images (URLs) shown above the Prepared by / Approved by names
        Schema::table('billing_statements', function (Blueprint $table) {
            $table->string('prepared_by_signature', 1000)->nullable()->after('prepared_by_role');
            $table->string('approved_by_signature', 1000)->nullable()->after('approved_by_role');
        });
    }

    public function down(): void
    {
        Schema::table('billing_statements', function (Blueprint $table) {
            $table->dropColumn(['prepared_by_signature', 'approved_by_signature']);
        });
    }
};
