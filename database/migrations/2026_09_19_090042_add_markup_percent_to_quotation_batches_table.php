<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('quotation_batches', function (Blueprint $table) {
            // Markup is entered as a percentage of the Project Budget; `markup` itself
            // keeps storing the computed peso amount so every downstream consumer
            // (contract_value calc, Payment.markup, billing docs) is unaffected.
            $table->decimal('markup_percent', 5, 2)->nullable()->after('markup');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('quotation_batches', function (Blueprint $table) {
            $table->dropColumn('markup_percent');
        });
    }
};
