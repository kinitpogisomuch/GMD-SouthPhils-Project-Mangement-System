<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotation_batches', function (Blueprint $table) {
            // Snapshotted the moment "Send to Client" is confirmed — never recomputed
            // live afterward, so the client-facing price can't silently drift if the
            // BOM changes later (same pattern as Payment::project_budget/markup).
            $table->decimal('project_budget', 15, 2)->nullable()->after('estimated_working_days');
            $table->decimal('markup', 15, 2)->nullable()->after('project_budget');
            $table->decimal('contract_value', 15, 2)->nullable()->after('markup');
            $table->json('quotation_files')->nullable()->after('contract_value');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_batches', function (Blueprint $table) {
            $table->dropColumn(['project_budget', 'markup', 'contract_value', 'quotation_files']);
        });
    }
};
